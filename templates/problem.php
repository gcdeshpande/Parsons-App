<section class="problem-arena">
    <header class="problem-hero">
        <a class="back-link" href="index.php?page=track&amp;track=<?= urlencode($track['id']) ?>">← Back to <?= htmlspecialchars($track['name']) ?></a>
        <div class="hero-meta">
            <span class="language-tag large"><?= htmlspecialchars($track['language']) ?></span>
            <span class="difficulty-pill difficulty-<?= strtolower($problem['difficulty']) ?>"><?= htmlspecialchars($problem['difficulty']) ?></span>
        </div>
        <h1><?= htmlspecialchars($problem['title']) ?></h1>
        <p class="synopsis"><?= htmlspecialchars($problem['synopsis']) ?></p>
        <dl class="problem-stats">
            <div>
                <dt>XP Reward</dt>
                <dd><?= number_format($problem['xp_reward']) ?> XP</dd>
            </div>
            <div>
                <dt>Fragments</dt>
                <dd><?= $problem['solution_count'] ?> required · <?= $problem['distractor_count'] ?> distractors</dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd>
                    <?php if ($problem['solved']): ?>
                        <span class="badge success">Completed</span>
                    <?php else: ?>
                        <span class="badge neutral">Unsolved</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
        <?php if (!$canPlay): ?>
            <div class="enroll-alert">
                <strong>Enroll required.</strong>
                <p>Join this track from the track page to attempt the live puzzle.</p>
            </div>
        <?php endif; ?>
    </header>

    <section class="parsons-play" data-parsons data-submit-url="index.php?page=problem&amp;track=<?= urlencode($track['id']) ?>&amp;id=<?= $problem['id'] ?>" data-can-play="<?= $canPlay ? 'true' : 'false' ?>" data-problem-id="<?= $problem['id'] ?>" data-solved="<?= $problem['solved'] ? 'true' : 'false' ?>">
        <div class="parsons-columns">
            <article class="parsons-column">
                <header>
                    <h2>Fragment pool</h2>
                    <p>Drag code lines into the solution canvas. Distractors look convincing—double check before placing.</p>
                </header>
                <div class="fragment-list" data-fragment-palette>
                    <?php foreach ($problem['fragments'] as $index => $fragment): ?>
                        <button
                            type="button"
                            class="fragment-card"
                            data-fragment-id="<?= $fragment['id'] ?>"
                            data-indent="<?= $fragment['indent_level'] ?>"
                            data-original-order="<?= $index ?>"
                            draggable="<?= $canPlay ? 'true' : 'false' ?>"
                            <?= $canPlay ? '' : 'disabled' ?>
                        >
                            <span class="line-index" aria-hidden="true">⋮</span>
                            <span class="line-code" style="--indent: <?= (int) $fragment['indent_level'] ?>"><?= htmlspecialchars($fragment['content']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="parsons-column">
                <header>
                    <h2>Solution canvas</h2>
                    <p>Arrange the lines into a runnable sequence. You must use all required fragments and avoid distractors.</p>
                </header>
                <div class="fragment-list solution" data-fragment-canvas>
                    <p class="drop-hint">Drop fragments here in order from top to bottom.</p>
                </div>
            </article>
        </div>

        <footer class="parsons-controls">
            <button class="btn primary" data-check <?= $canPlay ? '' : 'disabled' ?>>Check solution</button>
            <button class="btn secondary" data-reset <?= $canPlay ? '' : 'disabled' ?>>Reset</button>
            <button class="btn ghost" data-shuffle <?= $canPlay ? '' : 'disabled' ?>>Shuffle</button>
        </footer>

        <section class="parsons-feedback" data-feedback role="status" aria-live="polite"></section>
    </section>
</section>
