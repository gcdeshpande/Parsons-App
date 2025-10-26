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
            <th>XP</th>
            <th>Streak</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $index => $entry): ?>
            <tr>
                <td>#<?= $index + 1 ?></td>
                <td><?= htmlspecialchars($entry['name']) ?></td>
                <td><?= $entry['xp'] ?></td>
                <td><?= $entry['streak'] ?> days</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
