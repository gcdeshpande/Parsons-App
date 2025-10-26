<?php
require __DIR__ . '/../src/bootstrap.php';

$page = $_GET['page'] ?? 'home';

function render(string $template, array $data = []): void
{
    extract($data);
    $view = $template;
    include __DIR__ . '/../templates/layout.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($page === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            add_flash('error', 'Enter both username and password to log in.');
            header('Location: index.php?page=login');
            exit;
        }

        $user = authenticate_user($username, $password);
        if ($user) {
            login_user($user);
            add_flash('success', 'Welcome back!');
            header('Location: index.php?page=dashboard');
            exit;
        }

        add_flash('error', 'Invalid credentials. Please try again.');
        header('Location: index.php?page=login');
        exit;
    }

    if ($page === 'register') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if ($password !== $confirm) {
            add_flash('error', 'Passwords do not match.');
            header('Location: index.php?page=register');
            exit;
        }

        $result = register_user($username, $password);
        if ($result['success']) {
            $user = [
                'id' => $result['user']['id'],
                'username' => $result['user']['username'],
                'role' => $result['user']['role'],
            ];
            login_user($user);
            add_flash('success', $result['message']);
            header('Location: index.php?page=dashboard');
            exit;
        }

        add_flash('error', $result['message']);
        header('Location: index.php?page=register');
        exit;
    }

    if ($page === 'enroll' && isset($_POST['track_id'])) {
        require_login();
        enroll_track($_POST['track_id']);
        header('Location: index.php?page=dashboard');
        exit;
    }

    if ($page === 'admin-track-create') {
        require_admin();
        $result = create_track_from_request($_POST);
        add_flash($result['success'] ? 'success' : 'error', $result['message']);
        header('Location: index.php?page=dashboard');
        exit;
    }

    if ($page === 'admin-problem-create') {
        require_admin();
        $result = create_problem_from_request($_POST);
        add_flash($result['success'] ? 'success' : 'error', $result['message']);
        header('Location: index.php?page=dashboard');
        exit;
    }

    if ($page === 'admin-daily-challenge') {
        require_admin();
        $problemId = (int) ($_POST['problem_id'] ?? 0);
        $date = trim($_POST['challenge_date'] ?? date('Y-m-d'));
        $title = trim($_POST['title'] ?? 'Daily Challenge');
        $description = trim($_POST['description'] ?? 'Take on today’s featured puzzle.');
        $xpBonus = max(0, (int) ($_POST['xp_bonus'] ?? 0));

        if ($problemId > 0) {
            admin_set_daily_challenge($date, $problemId, $title === '' ? 'Daily Challenge' : $title, $description === '' ? 'Take on today’s featured puzzle.' : $description, $xpBonus);
            add_flash('success', 'Daily challenge updated.');
        } else {
            add_flash('error', 'Select a puzzle to feature as the daily challenge.');
        }

        header('Location: index.php?page=dashboard');
        exit;
    }

    if ($page === 'admin-fragment-bulk') {
        require_admin();
        $result = bulk_import_problem_fragments($_FILES['fragment_csv'] ?? null);
        add_flash($result['success'] ? 'success' : 'error', $result['message']);
        header('Location: index.php?page=dashboard');
        exit;
    }

    if ($page === 'admin-daily-bulk') {
        require_admin();
        $result = bulk_import_daily_challenges($_FILES['daily_csv'] ?? null);
        add_flash($result['success'] ? 'success' : 'error', $result['message']);
        header('Location: index.php?page=dashboard');
        exit;
    }

    if ($page === 'problem') {
        require_login();
        header('Content-Type: application/json');

        $trackId = $_GET['track'] ?? '';
        $problemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($trackId === '' || $problemId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid puzzle request.']);
            exit;
        }

        if (!is_admin() && !is_enrolled($trackId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Enroll in this track to attempt the puzzle.']);
            exit;
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Malformed submission payload.']);
            exit;
        }

        $fragments = $payload['fragments'] ?? [];
        if (!is_array($fragments)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Submission must include fragments.']);
            exit;
        }

        $user = current_user();
        $result = grade_problem_attempt($trackId, $problemId, $fragments, $user['name']);
        echo json_encode($result);
        exit;
    }
}

if ($page === 'logout') {
    logout_user();
    header('Location: index.php');
    exit;
}

$currentUser = current_user();
$dailyChallenge = get_daily_challenge($currentUser['name'] ?? null);
$tracks = load_tracks();
$leaderboards = load_leaderboards();

switch ($page) {
    case 'login':
        render('login.php', compact('tracks'));
        break;
    case 'register':
        render('register.php', compact('tracks'));
        break;
    case 'dashboard':
        require_login();
        $user = $currentUser;
        $enrolledTracks = array_filter($tracks, fn($track) => in_array($track['id'], $_SESSION['enrollments'] ?? [], true));
        $overallProgress = user_overall_progress($user['name']);
        $problemOptions = is_admin() ? list_all_problems() : [];
        render('dashboard.php', compact('tracks', 'enrolledTracks', 'leaderboards', 'user', 'overallProgress', 'dailyChallenge', 'problemOptions'));
        break;
    case 'track':
        $trackId = $_GET['track'] ?? '';
        $track = get_track($trackId);
        if (!$track) {
            render('404.php', []);
            break;
        }
        $isEnrolled = in_array($trackId, $_SESSION['enrollments'] ?? [], true);
        render('track.php', compact('track', 'isEnrolled', 'leaderboards'));
        break;
    case 'problem':
        require_login();
        $trackId = $_GET['track'] ?? '';
        $problemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $track = get_track($trackId);
        if (!$track || $problemId <= 0) {
            render('404.php', []);
            break;
        }
        $user = $currentUser;
        $problem = get_problem_with_fragments($trackId, $problemId, $user['name']);
        if (!$problem) {
            render('404.php', []);
            break;
        }
        $isEnrolled = is_enrolled($trackId);
        $canPlay = $isEnrolled || is_admin();
        $scripts = ['assets/problem.js'];
        render('problem.php', compact('track', 'problem', 'isEnrolled', 'canPlay', 'scripts'));
        break;
    case 'leaderboard':
        $trackId = $_GET['track'] ?? '';
        $track = get_track($trackId);
        if (!$track) {
            render('404.php');
            break;
        }
        $entries = $leaderboards[$trackId] ?? [];
        render('leaderboard.php', compact('track', 'entries'));
        break;
    default:
        render('home.php', compact('tracks', 'leaderboards', 'dailyChallenge'));
        break;
}
