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
    <button class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="primary-navigation">☰</button>
    <nav class="main-nav" id="primary-navigation" data-nav>
        <a href="index.php">Home</a>
        <a href="index.php#daily">Daily challenge</a>
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
    <?php $flashes = consume_flashes(); ?>
    <?php if ($flashes): ?>
        <div class="flash-stack" role="status" aria-live="polite">
            <?php foreach ($flashes as $flash): ?>
                <div class="flash flash-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php include __DIR__ . '/' . $view; ?>
</main>

<footer class="app-footer">
    <p>Built for code puzzlers. Track your streaks, level up your languages.</p>
</footer>

<?php $scripts = $scripts ?? []; ?>
<?php foreach ($scripts as $script): ?>
    <script src="<?= htmlspecialchars($script) ?>" defer></script>
<?php endforeach; ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var navToggle = document.querySelector('[data-nav-toggle]');
        var nav = document.querySelector('[data-nav]');
        if (navToggle && nav) {
            navToggle.addEventListener('click', function () {
                var expanded = navToggle.getAttribute('aria-expanded') === 'true';
                navToggle.setAttribute('aria-expanded', String(!expanded));
                nav.classList.toggle('is-open', !expanded);
            });
        }

        document.querySelectorAll('.progress-bar[data-progress]').forEach(function (bar) {
            var fill = bar.querySelector('.progress-fill');
            if (!fill) {
                return;
            }
            var pct = Number(bar.getAttribute('data-progress')) || 0;
            fill.style.width = pct + '%';
        });
    });
</script>
</body>
</html>
