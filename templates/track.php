<section class="track-detail">
    <header class="track-hero">
        <span class="language-tag large"><?= htmlspecialchars($track['language']) ?></span>
        <h1><?= htmlspecialchars($track['name']) ?></h1>
        <p><?= htmlspecialchars($track['description']) ?></p>
        <ul class="track-meta">
            <li><strong><?= $track['problem_count'] ?></strong> Parsons puzzles</li>
            <li><strong><?= $track['xp_per_problem'] ?></strong> XP each</li>
            <li><strong><?= htmlspecialchars($track['difficulty']) ?></strong> tier</li>
        </ul>
        <?php if (!$isEnrolled && current_user()): ?>
            <form action="index.php?page=enroll" method="post" class="inline">
                <input type="hidden" name="track_id" value="<?= htmlspecialchars($track['id']) ?>">
                <button class="btn primary">Enroll in this track</button>
            </form>
        <?php elseif (!$isEnrolled): ?>
            <a class="btn primary" href="index.php?page=login">Log in to enroll</a>
        <?php else: ?>
            <span class="status-tag enrolled">You are enrolled</span>
        <?php endif; ?>
    </header>

    <section class="track-themes">
        <h2>Quest log</h2>
        <div class="theme-grid">
            <?php foreach ($track['problem_themes'] as $theme): ?>
                <article class="theme-card">
                    <h3><?= htmlspecialchars($theme) ?></h3>
                    <p>Fix scrambled solutions focused on <?= htmlspecialchars(strtolower($theme)) ?> fundamentals.</p>
                    <span class="xp-tag">+<?= $track['xp_per_problem'] ?> XP per solve</span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="track-leaderboard">
        <header>
            <h2><?= htmlspecialchars($track['language']) ?> leaderboard</h2>
            <p>Top players dominating this language track.</p>
        </header>
        <ol class="leaderboard-list">
            <?php foreach (($leaderboards[$track['id']] ?? []) as $index => $entry): ?>
                <li>
                    <span class="placement">#<?= $index + 1 ?></span>
                    <div class="player-info">
                        <strong><?= htmlspecialchars($entry['name']) ?></strong>
                        <span><?= $entry['streak'] ?> day streak</span>
                    </div>
                    <span class="xp"><?= $entry['xp'] ?> XP</span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
</section>
