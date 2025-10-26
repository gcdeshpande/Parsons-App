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
