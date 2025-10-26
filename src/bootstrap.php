<?php

session_start();

const DATA_PATH = __DIR__ . '/../data';

function load_tracks(): array
{
    $file = DATA_PATH . '/tracks.json';
    if (!file_exists($file)) {
        return [];
    }

    $json = file_get_contents($file);
    return json_decode($json, true) ?? [];
}

function load_leaderboards(): array
{
    $file = DATA_PATH . '/leaderboards.json';
    if (!file_exists($file)) {
        return [];
    }

    $json = file_get_contents($file);
    return json_decode($json, true) ?? [];
}

function get_track(string $trackId): ?array
{
    foreach (load_tracks() as $track) {
        if ($track['id'] === $trackId) {
            return $track;
        }
    }

    return null;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function enroll_track(string $trackId): void
{
    if (!isset($_SESSION['enrollments'])) {
        $_SESSION['enrollments'] = [];
    }

    if (!in_array($trackId, $_SESSION['enrollments'], true)) {
        $_SESSION['enrollments'][] = $trackId;
    }
}

function enrollment_progress(string $trackId): array
{
    $track = get_track($trackId);
    if (!$track) {
        return ['completed' => 0, 'total' => 0, 'percentage' => 0];
    }

    $completed = $_SESSION['progress'][$trackId] ?? random_int(1, (int) max(1, floor($track['problem_count'] / 2)));
    $completed = min($completed, (int) $track['problem_count']);
    $total = (int) $track['problem_count'];
    $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

    return [
        'completed' => $completed,
        'total' => $total,
        'percentage' => $percentage,
    ];
}

function ensure_progress_seeded(): void
{
    if (!isset($_SESSION['progress_seeded'])) {
        foreach (load_tracks() as $track) {
            $_SESSION['progress'][$track['id']] = random_int(0, (int) $track['problem_count']);
        }
        $_SESSION['progress_seeded'] = true;
    }
}

ensure_progress_seeded();
