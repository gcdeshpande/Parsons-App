<section class="hero">
    <div class="hero-copy">
        <h1>Turn Parsons problems into epic quests</h1>
        <p>Create your avatar, enroll in language tracks, and climb the leaderboard with every puzzle you solve.</p>
        <div class="hero-actions">
            <a class="btn primary" href="index.php?page=login">Start your run</a>
            <a class="btn ghost" href="#tracks">Browse tracks</a>
        </div>
    </div>
    <div class="hero-art">
        <p class="caption">Solve. Drag. Celebrate. Repeat.</p>
    </div>
</section>

<?php if ($dailyChallenge): ?>
<section id="daily" class="daily-callout">
    <div class="daily-header">
        <span class="label">Daily challenge · <?= htmlspecialchars($dailyChallenge['track_name']) ?></span>
        <h2><?= htmlspecialchars($dailyChallenge['title']) ?></h2>
    </div>
    <p class="daily-description"><?= htmlspecialchars($dailyChallenge['description']) ?></p>
    <dl class="daily-meta">
        <div>
            <dt>Reward</dt>
            <dd><?= number_format($dailyChallenge['total_xp']) ?> XP</dd>
        </div>
        <div>
            <dt>Players cleared</dt>
            <dd><?= number_format($dailyChallenge['completed_players']) ?></dd>
        </div>
    </dl>
    <div class="daily-actions">
        <?php if (current_user()): ?>
            <?php if ($dailyChallenge['completed']): ?>
                <span class="badge success">Completed</span>
                <a class="btn ghost" href="index.php?page=problem&amp;track=<?= urlencode($dailyChallenge['track_id']) ?>&amp;id=<?= $dailyChallenge['problem_id'] ?>">Replay puzzle</a>
            <?php else: ?>
                <a class="btn primary" href="index.php?page=problem&amp;track=<?= urlencode($dailyChallenge['track_id']) ?>&amp;id=<?= $dailyChallenge['problem_id'] ?>">Play daily challenge</a>
            <?php endif; ?>
        <?php else: ?>
            <a class="btn primary" href="index.php?page=login">Log in to play</a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

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
                            <span class="xp"><?= $entry['solved'] ?> solved · <?= number_format($entry['xp']) ?> XP</span>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <a class="btn ghost" href="index.php?page=leaderboard&amp;track=<?= urlencode($track['id']) ?>">Full leaderboard</a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
