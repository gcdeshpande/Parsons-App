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
        $role = $_POST['role'] ?? 'player';

        if ($username !== '') {
            $_SESSION['user'] = [
                'name' => $username,
                'role' => $role,
            ];
            if (!isset($_SESSION['enrollments'])) {
                $_SESSION['enrollments'] = [];
            }
            header('Location: index.php?page=dashboard');
            exit;
        }
    }

    if ($page === 'enroll' && isset($_POST['track_id'])) {
        require_login();
        enroll_track($_POST['track_id']);
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
    session_destroy();
    header('Location: index.php');
    exit;
}

$tracks = load_tracks();
$leaderboards = load_leaderboards();

switch ($page) {
    case 'login':
        render('login.php', compact('tracks'));
        break;
    case 'dashboard':
        require_login();
        $user = current_user();
        $enrolledTracks = array_filter($tracks, fn($track) => in_array($track['id'], $_SESSION['enrollments'] ?? [], true));
        render('dashboard.php', compact('tracks', 'enrolledTracks', 'leaderboards', 'user'));
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
        $user = current_user();
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
        render('home.php', compact('tracks', 'leaderboards'));
        break;
}
