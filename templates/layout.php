<?php /** @var string $view */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parsons Playgrounds</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header class="app-header">
    <div class="brand">
        <a href="index.php" class="logo">Parsons Playgrounds</a>
        <span class="tagline">Assemble code. Unlock achievements.</span>
    </div>
    <nav class="main-nav">
        <a href="index.php">Home</a>
        <a href="index.php?page=dashboard">Dashboard</a>
        <a href="index.php?page=leaderboard&track=php">Leaderboards</a>
        <?php if (current_user()): ?>
            <a href="index.php?page=logout" class="logout">Log out</a>
        <?php else: ?>
            <a href="index.php?page=login" class="cta">Log in</a>
        <?php endif; ?>
    </nav>
</header>

<main class="content">
    <?php include __DIR__ . '/' . $view; ?>
</main>

<footer class="app-footer">
    <p>Built for code puzzlers. Track your streaks, level up your languages.</p>
</footer>

<script>
    document.querySelectorAll('[data-progress]').forEach(function (bar) {
        const pct = bar.getAttribute('data-progress');
        bar.querySelector('.progress-fill').style.width = pct + '%';
    });
</script>
</body>
</html>
