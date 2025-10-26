<section class="dashboard">
    <header class="dashboard-header">
        <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
        <p><?= is_admin() ? 'Monitor the arena and guide your players.' : 'Your progress path is glowing bright. Keep your streak alive!'; ?></p>
        <div class="streak-pill">
            <span>Lifetime completion</span>
            <strong><?= $overallProgress['completionPercent'] ?>%</strong>
        </div>
    </header>

    <section class="progress-overview">
        <article class="summary-card">
            <h2>Total XP</h2>
            <p><?= number_format($overallProgress['xp']) ?> XP banked</p>
        </article>
        <article class="summary-card">
            <h2>Puzzles mastered</h2>
            <p><?= $overallProgress['solved'] ?> of <?= $overallProgress['totalProblems'] ?> puzzles</p>
        </article>
        <article class="summary-card">
            <h2>Tracks engaged</h2>
            <p><?= $overallProgress['tracks'] ?> tracks explored</p>
        </article>
        <article class="summary-card">
            <h2>Next milestone</h2>
            <p><?= $overallProgress['nextMilestone'] ?> puzzles for the next badge</p>
        </article>
    </section>

    <?php if ($dailyChallenge): ?>
        <section class="daily-panel">
            <div>
                <span class="label">Daily challenge · <?= htmlspecialchars($dailyChallenge['track_name']) ?></span>
                <h2><?= htmlspecialchars($dailyChallenge['title']) ?></h2>
                <p><?= htmlspecialchars($dailyChallenge['description']) ?></p>
                <div class="daily-stats">
                    <span><?= number_format($dailyChallenge['total_xp']) ?> XP reward</span>
                    <span><?= number_format($dailyChallenge['completed_players']) ?> finishers</span>
                </div>
            </div>
            <div class="daily-actions">
                <?php if ($dailyChallenge['completed']): ?>
                    <span class="badge success">Completed</span>
                    <a class="btn ghost" href="index.php?page=problem&amp;track=<?= urlencode($dailyChallenge['track_id']) ?>&amp;id=<?= $dailyChallenge['problem_id'] ?>">Replay puzzle</a>
                <?php else: ?>
                    <a class="btn primary" href="index.php?page=problem&amp;track=<?= urlencode($dailyChallenge['track_id']) ?>&amp;id=<?= $dailyChallenge['problem_id'] ?>">Play now</a>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="daily-panel empty">
            <div>
                <span class="label">Daily challenge</span>
                <h2>All clear!</h2>
                <p>You’ve conquered every featured puzzle available right now. Check back soon for a fresh challenge.</p>
            </div>
        </section>
    <?php endif; ?>

    <?php if (is_admin()): ?>
        <section class="admin-overview">
            <h2>Admin command center</h2>
            <div class="stat-grid">
                <?php foreach ($tracks as $track): ?>
                    <?php $entries = $leaderboards[$track['id']] ?? []; ?>
                    <div class="stat-card">
                        <h3><?= htmlspecialchars($track['language']) ?> League</h3>
                        <p><?= count($entries) ?> ranked players</p>
                        <p>Total potential XP: <?= number_format($track['total_xp'] ?? ($track['xp_per_problem'] * $track['problem_count'])) ?></p>
                        <?php if ($entries): ?>
                            <div class="top-player">
                                <span>Top player</span>
                                <strong><?= htmlspecialchars($entries[0]['name']) ?></strong>
                                <span class="xp"><?= number_format($entries[0]['xp']) ?> XP · <?= $entries[0]['solved'] ?> solves</span>
                            </div>
                        <?php endif; ?>
                        <a class="btn ghost" href="index.php?page=leaderboard&amp;track=<?= urlencode($track['id']) ?>">Manage leaderboard</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-management">
            <div class="management-column">
                <h3>Create a new track</h3>
                <form action="index.php?page=admin-track-create" method="post" class="stacked-form">
                    <label>
                        <span>Track name</span>
                        <input type="text" name="name" required>
                    </label>
                    <label>
                        <span>Language</span>
                        <input type="text" name="language" required>
                    </label>
                    <label>
                        <span>Difficulty label</span>
                        <input type="text" name="difficulty" placeholder="Intermediate">
                    </label>
                    <label>
                        <span>XP per problem</span>
                        <input type="number" name="xp_per_problem" min="10" step="5" value="60">
                    </label>
                    <label>
                        <span>Description</span>
                        <textarea name="description" rows="3" required></textarea>
                    </label>
                    <label>
                        <span>Badges (comma separated)</span>
                        <input type="text" name="badges" placeholder="Trailblazer, Architect, Maestro">
                    </label>
                    <label>
                        <span>Themes (comma separated)</span>
                        <input type="text" name="themes" placeholder="Algorithms, Patterns, Tooling">
                    </label>
                    <label>
                        <span>Custom track ID (optional)</span>
                        <input type="text" name="track_id" placeholder="custom-track">
                    </label>
                    <button class="btn primary" type="submit">Create track</button>
                </form>
            </div>
            <div class="management-column">
                <h3>Add a puzzle</h3>
                <form action="index.php?page=admin-problem-create" method="post" class="stacked-form">
                    <label>
                        <span>Track</span>
                        <select name="track_id" required>
                            <option value="" disabled selected>Select track</option>
                            <?php foreach ($tracks as $track): ?>
                                <option value="<?= htmlspecialchars($track['id']) ?>"><?= htmlspecialchars($track['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Title</span>
                        <input type="text" name="title" required>
                    </label>
                    <label>
                        <span>Synopsis</span>
                        <textarea name="synopsis" rows="2" required></textarea>
                    </label>
                    <label>
                        <span>Difficulty</span>
                        <input type="text" name="difficulty" placeholder="Bronze">
                    </label>
                    <label>
                        <span>XP reward</span>
                        <input type="number" name="xp_reward" min="10" step="5" value="60">
                    </label>
                    <label>
                        <span>Focus area</span>
                        <input type="text" name="focus" placeholder="Async control">
                    </label>
                    <label>
                        <span>Solution fragments (one per line, indent with spaces to set nesting)</span>
                        <textarea name="solution_fragments" rows="6" required></textarea>
                    </label>
                    <label>
                        <span>Distractor fragments (optional, one per line)</span>
                        <textarea name="distractor_fragments" rows="4"></textarea>
                    </label>
                    <button class="btn secondary" type="submit">Add puzzle</button>
                </form>
            </div>
            <div class="management-column">
                <h3>Feature the daily challenge</h3>
                <form action="index.php?page=admin-daily-challenge" method="post" class="stacked-form">
                    <label>
                        <span>Date</span>
                        <input type="date" name="challenge_date" value="<?= date('Y-m-d') ?>">
                    </label>
                    <label>
                        <span>Puzzle to feature</span>
                        <select name="problem_id" required>
                            <option value="" disabled selected>Select puzzle</option>
                            <?php foreach ($problemOptions as $option): ?>
                                <option value="<?= $option['id'] ?>"><?= htmlspecialchars($option['track_name'] . ' · ' . $option['title'] . ' (' . $option['difficulty'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Headline</span>
                        <input type="text" name="title" placeholder="Daily Challenge">
                    </label>
                    <label>
                        <span>Teaser</span>
                        <textarea name="description" rows="3" placeholder="Spotlight what makes this puzzle special."></textarea>
                    </label>
                    <label>
                        <span>Bonus XP</span>
                        <input type="number" name="xp_bonus" min="0" step="5" value="25">
                    </label>
                    <button class="btn ghost" type="submit">Update daily challenge</button>
                </form>
            </div>
            <div class="management-column">
                <h3>Bulk upload fragments</h3>
                <form action="index.php?page=admin-fragment-bulk" method="post" class="stacked-form" enctype="multipart/form-data">
                    <label>
                        <span>CSV file</span>
                        <input type="file" name="fragment_csv" accept=".csv" required>
                    </label>
                    <p class="form-hint">Columns: <code>problem_id,content,indent_level,is_distractor,sort_order</code></p>
                    <button class="btn secondary" type="submit">Upload fragments</button>
                </form>
            </div>
            <div class="management-column">
                <h3>Bulk upload daily challenges</h3>
                <form action="index.php?page=admin-daily-bulk" method="post" class="stacked-form" enctype="multipart/form-data">
                    <label>
                        <span>CSV file</span>
                        <input type="file" name="daily_csv" accept=".csv" required>
                    </label>
                    <p class="form-hint">Columns: <code>challenge_date,problem_id,title,description,xp_bonus</code></p>
                    <button class="btn secondary" type="submit">Upload schedule</button>
                </form>
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
                        <span><?= number_format($progress['xp']) ?> XP earned</span>
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
