<section class="hero">
    <div class="hero-copy">
        <h1>Turn Parsons problems into epic quests</h1>
        <p>Create your avatar, enroll in language tracks, and climb the leaderboard with every puzzle you solve.</p>
        <div class="hero-actions">
            <a class="btn primary" href="index.php?page=login">Start your run</a>
            <a class="btn ghost" href="#tracks">Browse tracks</a>
        </div>
    </div>
    <div class="hero-xp-card">
        <p class="label">Daily Quest</p>
        <h2>Fix the rogue loop</h2>
        <p>Repair a scrambled for-loop to earn +120 XP.</p>
        <div class="xp-burst">XP +120</div>
    </div>
</section>

<section id="tracks" class="tracks-grid">
    <?php foreach ($tracks as $track): ?>
        <article class="track-card">
            <header>
                <span class="language-tag"><?= htmlspecialchars($track['language']) ?></span>
                <h2><?= htmlspecialchars($track['name']) ?></h2>
            </header>
            <p><?= htmlspecialchars($track['description']) ?></p>
            <ul class="track-meta">
                <li><strong><?= $track['problem_count'] ?></strong> puzzles</li>
                <li><strong><?= $track['xp_per_problem'] ?></strong> XP each</li>
                <li><strong><?= htmlspecialchars($track['difficulty']) ?></strong> tier</li>
            </ul>
            <div class="badge-row">
                <?php foreach ($track['badges'] as $badge): ?>
                    <span class="badge">🏅 <?= htmlspecialchars($badge) ?></span>
                <?php endforeach; ?>
            </div>
            <a class="btn secondary" href="index.php?page=track&amp;track=<?= urlencode($track['id']) ?>">View quests</a>
        </article>
    <?php endforeach; ?>
</section>

<section class="leaderboard-snapshot">
    <header>
        <h2>Hall of fame</h2>
        <p>Separate ladders keep competition fierce in every language.</p>
    </header>
    <div class="leaderboard-rows">
        <?php foreach ($tracks as $track): ?>
            <div class="leaderboard-card">
                <h3><?= htmlspecialchars($track['language']) ?> League</h3>
                <ol>
                    <?php foreach (($leaderboards[$track['id']] ?? []) as $index => $entry): ?>
                        <li>
                            <span class="placement">#<?= $index + 1 ?></span>
                            <span class="player"><?= htmlspecialchars($entry['name']) ?></span>
                            <span class="xp"><?= $entry['xp'] ?> XP</span>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <a class="btn ghost" href="index.php?page=leaderboard&amp;track=<?= urlencode($track['id']) ?>">Full leaderboard</a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
