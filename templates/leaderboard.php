<section class="leaderboard-page">
    <header>
        <h1><?= htmlspecialchars($track['language']) ?> leaderboard</h1>
        <p>See how puzzlers rank in the <?= htmlspecialchars($track['name']) ?> track.</p>
    </header>
    <table class="leaderboard-table">
        <thead>
        <tr>
            <th>Rank</th>
            <th>Player</th>
            <th>Solved</th>
            <th>Perfect clears</th>
            <th>XP</th>
            <th>Last clear</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $index => $entry): ?>
            <tr>
                <td>#<?= $index + 1 ?></td>
                <td><?= htmlspecialchars($entry['name']) ?></td>
                <td><?= $entry['solved'] ?></td>
                <td><?= $entry['perfect_runs'] ?></td>
                <td><?= number_format($entry['xp']) ?></td>
                <td><?= $entry['last_completed'] ? date('M j, Y', strtotime($entry['last_completed'])) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
