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
                        <span><?= $entry['solved'] ?> solves · <?= $entry['perfect_runs'] ?> perfect</span>
                    </div>
                    <span class="xp"><?= number_format($entry['xp']) ?> XP</span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="problem-list">
        <header>
            <h2>Challenge roster</h2>
            <p><?= $track['problem_count'] ?> handcrafted Parsons problems spanning beginner to mythic complexity.</p>
        </header>
        <div class="table-scroll">
            <table class="problem-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Quest</th>
                    <th>Focus</th>
                    <th>Difficulty</th>
                    <th>XP</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($track['problems'] as $index => $problem): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($problem['title']) ?></strong>
                            <p><?= htmlspecialchars($problem['synopsis']) ?></p>
                        </td>
                        <td><?= htmlspecialchars($problem['focus']) ?></td>
                        <td><span class="difficulty-pill difficulty-<?= strtolower($problem['difficulty']) ?>"><?= htmlspecialchars($problem['difficulty']) ?></span></td>
                        <td><?= number_format($problem['xp_reward']) ?></td>
                        <td class="problem-actions">
                            <div class="action-stack">
                                <?php if (!empty($problem['solved'])): ?>
                                    <span class="badge success">Solved</span>
                                <?php endif; ?>
                                <?php if ($isEnrolled || is_admin()): ?>
                                    <a class="btn mini" href="index.php?page=problem&amp;track=<?= urlencode($track['id']) ?>&amp;id=<?= $problem['id'] ?>">
                                        <?= !empty($problem['solved']) ? 'Replay' : 'Play' ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge ghost">Enroll to play</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
