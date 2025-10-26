<section class="dashboard">
    <header class="dashboard-header">
        <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
        <p><?= is_admin() ? 'Monitor the arena and guide your players.' : 'Your progress path is glowing bright. Keep your streak alive!'; ?></p>
        <div class="streak-pill">
            <span>Current streak</span>
            <strong><?= random_int(1, 7) ?> days</strong>
        </div>
    </header>

    <?php if (is_admin()): ?>
        <section class="admin-overview">
            <h2>Admin command center</h2>
            <div class="stat-grid">
                <?php foreach ($tracks as $track): ?>
                    <?php $entries = $leaderboards[$track['id']] ?? []; ?>
                    <div class="stat-card">
                        <h3><?= htmlspecialchars($track['language']) ?> League</h3>
                        <p><?= count($entries) ?> ranked players</p>
                        <p>Total potential XP: <?= $track['xp_per_problem'] * $track['problem_count'] ?></p>
                        <?php if ($entries): ?>
                            <div class="top-player">
                                <span>Top player</span>
                                <strong><?= htmlspecialchars($entries[0]['name']) ?></strong>
                                <span class="xp"><?= $entries[0]['xp'] ?> XP</span>
                            </div>
                        <?php endif; ?>
                        <a class="btn ghost" href="index.php?page=leaderboard&amp;track=<?= urlencode($track['id']) ?>">Manage leaderboard</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="enrollments">
        <div class="section-heading">
            <h2><?= is_admin() ? 'Active tracks' : 'Your enrolled tracks' ?></h2>
            <p><?= is_admin() ? 'Preview challenges and problem themes for each language track.' : 'Track your XP earnings and badges earned so far.' ?></p>
        </div>
        <div class="track-progress-grid">
            <?php $list = is_admin() ? $tracks : $enrolledTracks; ?>
            <?php if (!$list): ?>
                <p class="empty-state">You have not enrolled yet. Browse tracks below to get started.</p>
            <?php endif; ?>
            <?php foreach ($list as $track): ?>
                <?php $progress = enrollment_progress($track['id']); ?>
                <article class="progress-card" data-progress="<?= $progress['percentage'] ?>">
                    <header>
                        <h3><?= htmlspecialchars($track['name']) ?></h3>
                        <span class="language-tag small"><?= htmlspecialchars($track['language']) ?></span>
                    </header>
                    <p><?= htmlspecialchars($track['description']) ?></p>
                    <div class="progress-bar" data-progress="<?= $progress['percentage'] ?>">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-stats">
                        <span><?= $progress['completed'] ?> / <?= $progress['total'] ?> puzzles solved</span>
                        <span><?= $track['xp_per_problem'] * $progress['completed'] ?> XP earned</span>
                    </div>
                    <footer>
                        <div class="badges-inline">
                            <?php foreach ($track['badges'] as $badge): ?>
                                <span class="badge small">🏆 <?= htmlspecialchars($badge) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <a class="btn secondary" href="index.php?page=track&amp;track=<?= urlencode($track['id']) ?>">View quest log</a>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!is_admin()): ?>
        <section class="available-tracks">
            <div class="section-heading">
                <h2>Available language tracks</h2>
                <p>Pick another challenge to expand your toolkit.</p>
            </div>
            <div class="available-grid">
                <?php foreach ($tracks as $track): ?>
                    <?php $enrolled = in_array($track['id'], $_SESSION['enrollments'] ?? [], true); ?>
                    <article class="available-card">
                        <h3><?= htmlspecialchars($track['name']) ?></h3>
                        <p><?= htmlspecialchars($track['description']) ?></p>
                        <ul class="track-meta">
                            <li><?= $track['problem_count'] ?> puzzles</li>
                            <li><?= $track['xp_per_problem'] ?> XP each</li>
                        </ul>
                        <?php if ($enrolled): ?>
                            <span class="status-tag enrolled">Enrolled</span>
                        <?php else: ?>
                            <form action="index.php?page=enroll" method="post">
                                <input type="hidden" name="track_id" value="<?= htmlspecialchars($track['id']) ?>">
                                <button class="btn primary" type="submit">Enroll now</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>
