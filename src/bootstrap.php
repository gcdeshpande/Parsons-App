<?php

session_start();

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_NAME') ?: 'parsons_app';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);

    try {
        $pdo = new PDO("$dsn;dbname=$database", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        if ((int) $exception->getCode() !== 1049) {
            throw $exception;
        }

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $database));
        $pdo = null;

        return db();
    }

    initialize_database($pdo);

    return $pdo;
}

function initialize_database(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM("admin", "player") NOT NULL DEFAULT "player",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS tracks (
            id VARCHAR(64) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            language VARCHAR(100) NOT NULL,
            difficulty VARCHAR(100) NOT NULL,
            xp_per_problem INT NOT NULL,
            description TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS track_badges (
            track_id VARCHAR(64) NOT NULL,
            badge VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            CONSTRAINT fk_track_badges_track FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS track_themes (
            track_id VARCHAR(64) NOT NULL,
            theme VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            CONSTRAINT fk_track_themes_track FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS problems (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            track_id VARCHAR(64) NOT NULL,
            title VARCHAR(255) NOT NULL,
            synopsis TEXT NOT NULL,
            difficulty VARCHAR(100) NOT NULL,
            xp_reward INT NOT NULL,
            focus VARCHAR(255) NOT NULL,
            CONSTRAINT fk_problems_track FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS problem_fragments (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            problem_id INT UNSIGNED NOT NULL,
            content TEXT NOT NULL,
            indent_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
            is_distractor TINYINT(1) NOT NULL DEFAULT 0,
            sort_order TINYINT UNSIGNED NULL,
            CONSTRAINT fk_fragments_problem FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS results (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            track_id VARCHAR(64) NOT NULL,
            problem_id INT UNSIGNED NOT NULL,
            player_name VARCHAR(255) NOT NULL,
            status VARCHAR(100) NOT NULL,
            score INT NOT NULL,
            completed_at DATETIME NOT NULL,
            CONSTRAINT fk_results_track FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE,
            CONSTRAINT fk_results_problem FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS daily_challenges (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            challenge_date DATE NOT NULL UNIQUE,
            problem_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            xp_bonus INT NOT NULL DEFAULT 0,
            CONSTRAINT fk_daily_challenges_problem FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS daily_attempts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            challenge_id INT UNSIGNED NOT NULL,
            player_name VARCHAR(255) NOT NULL,
            completed_at DATETIME NOT NULL,
            UNIQUE KEY daily_attempt_unique (challenge_id, player_name),
            CONSTRAINT fk_daily_attempts_challenge FOREIGN KEY (challenge_id) REFERENCES daily_challenges(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    seed_database($pdo);
}

function seed_database(PDO $pdo): void
{
    ensure_default_admin($pdo);

    $trackCount = (int) $pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn();
    if ($trackCount === 0) {
        $tracks = [
        'php' => [
            'name' => 'PHP Wizardry',
            'language' => 'PHP',
            'difficulty' => 'Intermediate',
            'xp_per_problem' => 60,
            'description' => 'Debug and reorder classic PHP snippets that power dynamic web experiences.',
            'badges' => ['Array Alchemist', 'Session Sorcerer', 'PDO Paladin'],
            'themes' => ['Forms', 'Database', 'APIs'],
        ],
        'python' => [
            'name' => 'Python Quest',
            'language' => 'Python',
            'difficulty' => 'Beginner',
            'xp_per_problem' => 45,
            'description' => 'Wrangle data, tame functions, and compose elegant scripts in Python.',
            'badges' => ['List Wrangler', 'Decorator Defender', 'Async Adventurer'],
            'themes' => ['Data Structures', 'Web', 'Automation'],
        ],
        'javascript' => [
            'name' => 'JavaScript Odyssey',
            'language' => 'JavaScript',
            'difficulty' => 'Advanced',
            'xp_per_problem' => 75,
            'description' => 'Rebuild interactive front-end flows with event-driven puzzles and asynchronous twists.',
            'badges' => ['DOM Dynamo', 'Promise Pathfinder', 'Testing Tactician'],
            'themes' => ['DOM', 'Testing', 'Tooling'],
        ],
    ];

    $problemDecks = [
        'php' => [
            ['title' => 'Echo Chamber Calibration', 'synopsis' => 'Reorder echo statements to rebuild an onboarding banner.', 'difficulty' => 'Bronze', 'xp' => 40, 'focus' => 'Output sequencing'],
            ['title' => 'Cookie Quest', 'synopsis' => 'Restore a login cookie helper that is missing guards and order.', 'difficulty' => 'Bronze', 'xp' => 45, 'focus' => 'Sessions & cookies'],
            ['title' => 'Array Forge', 'synopsis' => 'Piece together a shopping cart merger that respects priorities.', 'difficulty' => 'Bronze', 'xp' => 45, 'focus' => 'Array operations'],
            ['title' => 'Form Wardens', 'synopsis' => 'Rebuild CSRF token validation around a contact form.', 'difficulty' => 'Silver', 'xp' => 55, 'focus' => 'Form security'],
            ['title' => 'Routing Ritual', 'synopsis' => 'Assemble a minimal router handling GET and POST flows.', 'difficulty' => 'Silver', 'xp' => 55, 'focus' => 'Routing'],
            ['title' => 'PDO Initiation', 'synopsis' => 'Restore PDO connection bootstrapping with prepared statements.', 'difficulty' => 'Silver', 'xp' => 60, 'focus' => 'Database connections'],
            ['title' => 'JSON Familiar', 'synopsis' => 'Summon a JSON API responder that returns pagination meta.', 'difficulty' => 'Silver', 'xp' => 60, 'focus' => 'API responses'],
            ['title' => 'Collection Smithing', 'synopsis' => 'Repair a pipeline that maps, filters, and reduces inventory.', 'difficulty' => 'Gold', 'xp' => 65, 'focus' => 'Higher-order functions'],
            ['title' => 'Cache Binding', 'synopsis' => 'Recreate a PSR-16 cache decorator with fallback logic.', 'difficulty' => 'Gold', 'xp' => 70, 'focus' => 'Caching'],
            ['title' => 'Blade Glyphs', 'synopsis' => 'Patch a Blade template puzzle with loops and conditionals.', 'difficulty' => 'Gold', 'xp' => 70, 'focus' => 'Templating'],
            ['title' => 'Event Conductor', 'synopsis' => 'Restore an event dispatcher that wires listeners in priority.', 'difficulty' => 'Platinum', 'xp' => 75, 'focus' => 'Events'],
            ['title' => 'Middleware Maze', 'synopsis' => 'Rebuild middleware stacking for authentication and throttling.', 'difficulty' => 'Platinum', 'xp' => 75, 'focus' => 'Middleware'],
            ['title' => 'Queue Catalyst', 'synopsis' => 'Untangle a job dispatcher ensuring idempotent retries.', 'difficulty' => 'Platinum', 'xp' => 80, 'focus' => 'Queues'],
            ['title' => 'Testing Gauntlet', 'synopsis' => 'Arrange PHPUnit setup/teardown to isolate filesystem fixtures.', 'difficulty' => 'Platinum', 'xp' => 80, 'focus' => 'Testing'],
            ['title' => 'Localization Loom', 'synopsis' => 'Reconstruct translation loading with fallback locales.', 'difficulty' => 'Diamond', 'xp' => 85, 'focus' => 'Localization'],
            ['title' => 'Stream Sentry', 'synopsis' => 'Repair a stream wrapper that encrypts uploads on the fly.', 'difficulty' => 'Diamond', 'xp' => 90, 'focus' => 'Streams'],
            ['title' => 'Observer Nexus', 'synopsis' => 'Reconfigure Eloquent observers to keep audit trails intact.', 'difficulty' => 'Diamond', 'xp' => 90, 'focus' => 'ORM events'],
            ['title' => 'Macro Forge', 'synopsis' => 'Rebuild a Collection macro registry with late binding.', 'difficulty' => 'Diamond', 'xp' => 95, 'focus' => 'Macros'],
            ['title' => 'GraphQL Gateway', 'synopsis' => 'Restore field resolvers mixing synchronous and async fetches.', 'difficulty' => 'Mythic', 'xp' => 100, 'focus' => 'GraphQL'],
            ['title' => 'Octane Overdrive', 'synopsis' => 'Sequence coroutine tasks for a Swoole-powered worker.', 'difficulty' => 'Mythic', 'xp' => 105, 'focus' => 'Concurrency'],
            ['title' => 'Policy Citadel', 'synopsis' => 'Reassemble authorization policies with nested abilities.', 'difficulty' => 'Mythic', 'xp' => 105, 'focus' => 'Authorization'],
            ['title' => 'Telemetry Beacon', 'synopsis' => 'Rewire PSR-3 logging with contextual processors.', 'difficulty' => 'Legendary', 'xp' => 110, 'focus' => 'Observability'],
            ['title' => 'Livewire Lattice', 'synopsis' => 'Fix interwoven Livewire component lifecycle hooks.', 'difficulty' => 'Legendary', 'xp' => 115, 'focus' => 'Real-time UI'],
            ['title' => 'Hexagonal Hearth', 'synopsis' => 'Reconstruct ports and adapters for a payments module.', 'difficulty' => 'Legendary', 'xp' => 120, 'focus' => 'Architecture'],
            ['title' => 'Serverless Sigils', 'synopsis' => 'Sequence an async queue running on FaaS with cold-start guards.', 'difficulty' => 'Legendary', 'xp' => 125, 'focus' => 'Serverless']
        ],
        'python' => [
            ['title' => 'List Labyrinth', 'synopsis' => 'Restore list comprehension order to build scoreboards.', 'difficulty' => 'Bronze', 'xp' => 35, 'focus' => 'Comprehensions'],
            ['title' => 'Tuple Relay', 'synopsis' => 'Reorder tuple unpacking for coordinate transforms.', 'difficulty' => 'Bronze', 'xp' => 35, 'focus' => 'Unpacking'],
            ['title' => 'String Sigil', 'synopsis' => 'Fix f-string formatting inside a CLI reporter.', 'difficulty' => 'Bronze', 'xp' => 40, 'focus' => 'Formatting'],
            ['title' => 'Dict Foundry', 'synopsis' => 'Repair dictionary merging for feature flags.', 'difficulty' => 'Bronze', 'xp' => 40, 'focus' => 'Dictionaries'],
            ['title' => 'Path Ritual', 'synopsis' => 'Rebuild pathlib usage for cross-platform file syncing.', 'difficulty' => 'Silver', 'xp' => 45, 'focus' => 'Filesystem'],
            ['title' => 'Requests Revival', 'synopsis' => 'Restore API calls with retry and timeout handling.', 'difficulty' => 'Silver', 'xp' => 45, 'focus' => 'HTTP clients'],
            ['title' => 'Generator Grove', 'synopsis' => 'Reassemble a generator pipeline with lazy evaluation.', 'difficulty' => 'Silver', 'xp' => 50, 'focus' => 'Generators'],
            ['title' => 'Testing Tundra', 'synopsis' => 'Fix pytest fixtures to isolate database state.', 'difficulty' => 'Silver', 'xp' => 50, 'focus' => 'Testing'],
            ['title' => 'Async Meadows', 'synopsis' => 'Repair asyncio gather usage with cancellation guards.', 'difficulty' => 'Gold', 'xp' => 55, 'focus' => 'Asyncio'],
            ['title' => 'DataClass Forge', 'synopsis' => 'Reorder dataclass field definitions with defaults.', 'difficulty' => 'Gold', 'xp' => 55, 'focus' => 'Data classes'],
            ['title' => 'Decorator Drift', 'synopsis' => 'Restore decorator stacking for tracing and caching.', 'difficulty' => 'Gold', 'xp' => 60, 'focus' => 'Decorators'],
            ['title' => 'CLI Constellation', 'synopsis' => 'Rebuild click command groups with shared options.', 'difficulty' => 'Gold', 'xp' => 60, 'focus' => 'CLI'],
            ['title' => 'NumPy Nexus', 'synopsis' => 'Fix NumPy array broadcasting for feature scaling.', 'difficulty' => 'Platinum', 'xp' => 65, 'focus' => 'Numerics'],
            ['title' => 'Pandas Pavilion', 'synopsis' => 'Repair chained DataFrame operations for cohort analysis.', 'difficulty' => 'Platinum', 'xp' => 70, 'focus' => 'Data wrangling'],
            ['title' => 'Model Menagerie', 'synopsis' => 'Rebuild scikit-learn pipelines with parameter grids.', 'difficulty' => 'Platinum', 'xp' => 70, 'focus' => 'ML pipelines'],
            ['title' => 'Celery Citadel', 'synopsis' => 'Reorder Celery task signatures to propagate context.', 'difficulty' => 'Platinum', 'xp' => 75, 'focus' => 'Task queues'],
            ['title' => 'FastAPI Flux', 'synopsis' => 'Restore dependency injection for background jobs.', 'difficulty' => 'Diamond', 'xp' => 80, 'focus' => 'Web frameworks'],
            ['title' => 'Security Sanctum', 'synopsis' => 'Rebuild password hashing and timing-safe comparisons.', 'difficulty' => 'Diamond', 'xp' => 80, 'focus' => 'Security'],
            ['title' => 'Streaming Summit', 'synopsis' => 'Recreate async generators streaming CSV exports.', 'difficulty' => 'Diamond', 'xp' => 85, 'focus' => 'Streaming IO'],
            ['title' => 'Type Halls', 'synopsis' => 'Restore mypy plugin hooks for custom types.', 'difficulty' => 'Diamond', 'xp' => 85, 'focus' => 'Typing'],
            ['title' => 'Orchestration Oracle', 'synopsis' => 'Rebuild Airflow DAG dependencies with sensors.', 'difficulty' => 'Legendary', 'xp' => 90, 'focus' => 'Workflow orchestration'],
            ['title' => 'Graph Gateway', 'synopsis' => 'Reconstruct GraphQL resolvers with dataloaders.', 'difficulty' => 'Legendary', 'xp' => 95, 'focus' => 'APIs'],
            ['title' => 'Microservice Mirage', 'synopsis' => 'Repair gRPC client stubs with retries and deadlines.', 'difficulty' => 'Legendary', 'xp' => 95, 'focus' => 'Microservices'],
            ['title' => 'Concurrency Crucible', 'synopsis' => 'Reorder trio nursery tasks with graceful shutdown.', 'difficulty' => 'Mythic', 'xp' => 100, 'focus' => 'Concurrency'],
            ['title' => 'Quantum Queue', 'synopsis' => 'Rebuild message batching for a Kafka consumer group.', 'difficulty' => 'Mythic', 'xp' => 105, 'focus' => 'Event streaming']
        ],
        'javascript' => [
            ['title' => 'DOM Foundations', 'synopsis' => 'Restore DOM creation order for a quest tracker.', 'difficulty' => 'Bronze', 'xp' => 45, 'focus' => 'DOM APIs'],
            ['title' => 'Event Ember', 'synopsis' => 'Rebuild event delegation on a dynamic leaderboard.', 'difficulty' => 'Bronze', 'xp' => 45, 'focus' => 'Events'],
            ['title' => 'Fetch Forge', 'synopsis' => 'Fix fetch promise chains for quest enrollment.', 'difficulty' => 'Bronze', 'xp' => 50, 'focus' => 'Promises'],
            ['title' => 'Module Mosaic', 'synopsis' => 'Reorder ES modules to hydrate a dashboard.', 'difficulty' => 'Silver', 'xp' => 55, 'focus' => 'Modules'],
            ['title' => 'State Springs', 'synopsis' => 'Repair a reducer managing XP counters.', 'difficulty' => 'Silver', 'xp' => 55, 'focus' => 'State management'],
            ['title' => 'Animation Atrium', 'synopsis' => 'Rebuild requestAnimationFrame loops for rewards.', 'difficulty' => 'Silver', 'xp' => 60, 'focus' => 'Animations'],
            ['title' => 'Form Familiar', 'synopsis' => 'Recreate controlled inputs in a React form.', 'difficulty' => 'Silver', 'xp' => 60, 'focus' => 'React basics'],
            ['title' => 'Hook Havoc', 'synopsis' => 'Reorder custom hooks orchestrating websocket data.', 'difficulty' => 'Gold', 'xp' => 65, 'focus' => 'React hooks'],
            ['title' => 'Testing Tides', 'synopsis' => 'Restore Jest test suites mocking fetch layers.', 'difficulty' => 'Gold', 'xp' => 65, 'focus' => 'Testing'],
            ['title' => 'Router Run', 'synopsis' => 'Rebuild nested routes with lazy loading.', 'difficulty' => 'Gold', 'xp' => 70, 'focus' => 'Routing'],
            ['title' => 'Accessibility Aerie', 'synopsis' => 'Repair ARIA attributes in a modal wizard.', 'difficulty' => 'Gold', 'xp' => 70, 'focus' => 'Accessibility'],
            ['title' => 'TypeScript Tether', 'synopsis' => 'Restore generics in a data-fetching hook.', 'difficulty' => 'Platinum', 'xp' => 75, 'focus' => 'TypeScript'],
            ['title' => 'Node Nexus', 'synopsis' => 'Rebuild Express middleware ordering with async handlers.', 'difficulty' => 'Platinum', 'xp' => 80, 'focus' => 'Node.js'],
            ['title' => 'Stream Sanctuary', 'synopsis' => 'Repair Node streams piping analytics events.', 'difficulty' => 'Platinum', 'xp' => 80, 'focus' => 'Streams'],
            ['title' => 'Webpack Workshop', 'synopsis' => 'Reconstruct bundler configuration with code splitting.', 'difficulty' => 'Platinum', 'xp' => 85, 'focus' => 'Tooling'],
            ['title' => 'GraphQL Grove', 'synopsis' => 'Fix Apollo client cache normalization ordering.', 'difficulty' => 'Diamond', 'xp' => 90, 'focus' => 'GraphQL'],
            ['title' => 'SSR Spire', 'synopsis' => 'Rebuild Next.js data fetching waterfall.', 'difficulty' => 'Diamond', 'xp' => 90, 'focus' => 'Server-side rendering'],
            ['title' => 'WebSocket Ward', 'synopsis' => 'Restore socket reconnection with exponential backoff.', 'difficulty' => 'Diamond', 'xp' => 95, 'focus' => 'Real-time'],
            ['title' => 'Worker Watch', 'synopsis' => 'Reorder web worker initialization for image processing.', 'difficulty' => 'Diamond', 'xp' => 95, 'focus' => 'Web workers'],
            ['title' => 'Performance Pinnacle', 'synopsis' => 'Rebuild profiling hooks to trim render cost.', 'difficulty' => 'Legendary', 'xp' => 100, 'focus' => 'Performance'],
            ['title' => 'Security Sanctum', 'synopsis' => 'Restore CSP headers and sanitize HTML.', 'difficulty' => 'Legendary', 'xp' => 105, 'focus' => 'Security'],
            ['title' => 'Microfrontier', 'synopsis' => 'Reassemble module federation bootstrap order.', 'difficulty' => 'Legendary', 'xp' => 105, 'focus' => 'Microfrontends'],
            ['title' => 'Edge Enclave', 'synopsis' => 'Fix an edge function handling streaming responses.', 'difficulty' => 'Legendary', 'xp' => 110, 'focus' => 'Edge computing'],
            ['title' => 'Compiler Crucible', 'synopsis' => 'Restore Babel plugin ordering for experimental syntax.', 'difficulty' => 'Mythic', 'xp' => 115, 'focus' => 'Compilers'],
            ['title' => 'Isomorphic Inference', 'synopsis' => 'Rebuild shared validation running on client and server.', 'difficulty' => 'Mythic', 'xp' => 120, 'focus' => 'Isomorphic code']
        ],
    ];

        foreach ($tracks as $id => $track) {
            $stmt = $pdo->prepare('INSERT INTO tracks (id, name, language, difficulty, xp_per_problem, description) VALUES (:id, :name, :language, :difficulty, :xp, :description)');
            $stmt->execute([
                ':id' => $id,
                ':name' => $track['name'],
            ':language' => $track['language'],
            ':difficulty' => $track['difficulty'],
            ':xp' => $track['xp_per_problem'],
            ':description' => $track['description'],
        ]);

        $badgeStmt = $pdo->prepare('INSERT INTO track_badges (track_id, badge, sort_order) VALUES (:track_id, :badge, :sort_order)');
        foreach ($track['badges'] as $sort => $badge) {
            $badgeStmt->execute([
                ':track_id' => $id,
                ':badge' => $badge,
                ':sort_order' => $sort,
            ]);
        }

        $themeStmt = $pdo->prepare('INSERT INTO track_themes (track_id, theme, sort_order) VALUES (:track_id, :theme, :sort_order)');
        foreach ($track['themes'] as $sort => $theme) {
            $themeStmt->execute([
                ':track_id' => $id,
                ':theme' => $theme,
                ':sort_order' => $sort,
            ]);
        }
    }

        $problemStmt = $pdo->prepare('INSERT INTO problems (track_id, title, synopsis, difficulty, xp_reward, focus) VALUES (:track_id, :title, :synopsis, :difficulty, :xp, :focus)');
        $fragmentStmt = $pdo->prepare('INSERT INTO problem_fragments (problem_id, content, indent_level, is_distractor, sort_order) VALUES (:problem_id, :content, :indent, :distractor, :sort_order)');
        $problemIdBuckets = [];
        foreach ($problemDecks as $trackId => $problems) {
            foreach ($problems as $problem) {
                $problemStmt->execute([
                    ':track_id' => $trackId,
                    ':title' => $problem['title'],
                    ':synopsis' => $problem['synopsis'],
                    ':difficulty' => $problem['difficulty'],
                    ':xp' => $problem['xp'],
                    ':focus' => $problem['focus'],
                ]);
                $problemId = (int) $pdo->lastInsertId();
                $problemIdBuckets[$trackId][] = ['id' => $problemId, 'xp' => $problem['xp']];

                $fragments = generate_fragments_for_problem($trackId, $problem, count($problemIdBuckets[$trackId]) - 1);
                foreach ($fragments as $fragment) {
                    $fragmentStmt->execute([
                        ':problem_id' => $problemId,
                        ':content' => $fragment['content'],
                        ':indent' => $fragment['indent_level'],
                        ':distractor' => $fragment['is_distractor'] ? 1 : 0,
                        ':sort_order' => $fragment['sort_order'],
                    ]);
                }
            }
        }

        $leaderboardSeeds = [
        'php' => [
            ['name' => 'AstraLambda', 'solved' => 24, 'perfect' => 9],
            ['name' => 'CacheKnight', 'solved' => 21, 'perfect' => 7],
            ['name' => 'SessionSage', 'solved' => 19, 'perfect' => 6],
            ['name' => 'OctaneOracle', 'solved' => 17, 'perfect' => 5],
            ['name' => 'BladeDancer', 'solved' => 15, 'perfect' => 4],
        ],
        'python' => [
            ['name' => 'ByteBard', 'solved' => 25, 'perfect' => 11],
            ['name' => 'AsyncRanger', 'solved' => 22, 'perfect' => 9],
            ['name' => 'PandasPaladin', 'solved' => 20, 'perfect' => 8],
            ['name' => 'CelerySentinel', 'solved' => 18, 'perfect' => 6],
            ['name' => 'QuantumQuill', 'solved' => 16, 'perfect' => 5],
        ],
        'javascript' => [
            ['name' => 'PromisePilot', 'solved' => 23, 'perfect' => 10],
            ['name' => 'HookHunter', 'solved' => 21, 'perfect' => 8],
            ['name' => 'DOMDruid', 'solved' => 20, 'perfect' => 7],
            ['name' => 'EdgeExplorer', 'solved' => 18, 'perfect' => 6],
            ['name' => 'ModuleMystic', 'solved' => 17, 'perfect' => 5],
        ],
    ];

        $resultStmt = $pdo->prepare('INSERT INTO results (track_id, problem_id, player_name, status, score, completed_at) VALUES (:track, :problem, :player, :status, :score, :completed_at)');
        $now = new DateTimeImmutable('now');

        foreach ($leaderboardSeeds as $trackId => $players) {
            $problems = $problemIdBuckets[$trackId] ?? [];
            foreach ($players as $index => $player) {
                $solvedProblems = array_slice($problems, 0, min($player['solved'], count($problems)));
                foreach ($solvedProblems as $solvedIndex => $problemMeta) {
                    $status = $solvedIndex < $player['perfect'] ? 'perfect' : 'completed';
                    $date = $now->sub(new DateInterval('P' . ($solvedIndex + ($index * 2)) . 'D'));
                    $resultStmt->execute([
                        ':track' => $trackId,
                        ':problem' => $problemMeta['id'],
                        ':player' => $player['name'],
                        ':status' => $status,
                        ':score' => $problemMeta['xp'],
                        ':completed_at' => $date->format('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }

    ensure_daily_challenge_schedule($pdo);
}

function ensure_default_admin(PDO $pdo): void
{
    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminCount > 0) {
        return;
    }

    $username = 'admin';
    $passwordHash = password_hash('AdminPass123!', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)');
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => $passwordHash,
        ':role' => 'admin',
    ]);
}

function ensure_daily_challenge_schedule(PDO $pdo): void
{
    $challengeCount = (int) $pdo->query('SELECT COUNT(*) FROM daily_challenges')->fetchColumn();
    if ($challengeCount >= 21) {
        return;
    }

    $problems = $pdo->query('SELECT p.id, p.title, p.track_id, p.xp_reward, p.difficulty, p.focus, t.name AS track_name FROM problems p JOIN tracks t ON t.id = p.track_id ORDER BY p.xp_reward DESC, p.id')->fetchAll(PDO::FETCH_ASSOC);
    if (!$problems) {
        return;
    }

    $existingDates = $pdo->query('SELECT challenge_date FROM daily_challenges')->fetchAll(PDO::FETCH_COLUMN);
    $occupied = [];
    foreach ($existingDates as $date) {
        $occupied[$date] = true;
    }

    $start = new DateTimeImmutable('today');
    $offset = 0;
    $inserted = 0;
    $stmt = $pdo->prepare('INSERT INTO daily_challenges (challenge_date, problem_id, title, description, xp_bonus) VALUES (:date, :problem, :title, :description, :bonus) ON DUPLICATE KEY UPDATE problem_id = VALUES(problem_id), title = VALUES(title), description = VALUES(description), xp_bonus = VALUES(xp_bonus)');

    foreach ($problems as $problem) {
        while (isset($occupied[$start->add(new DateInterval('P' . $offset . 'D'))->format('Y-m-d')])) {
            $offset++;
            if ($offset > count($problems) + 60) {
                break 2;
            }
        }

        $targetDate = $start->add(new DateInterval('P' . $offset . 'D'));
        $title = sprintf('Daily: %s', $problem['title']);
        $description = sprintf('Crack this %s %s quest from %s.', strtolower($problem['difficulty']), strtolower($problem['focus']), $problem['track_name']);
        $xpBonus = max(20, (int) round($problem['xp_reward'] * 0.3));

        $stmt->execute([
            ':date' => $targetDate->format('Y-m-d'),
            ':problem' => $problem['id'],
            ':title' => $title,
            ':description' => $description,
            ':bonus' => $xpBonus,
        ]);

        $occupied[$targetDate->format('Y-m-d')] = true;
        $offset++;
        $inserted++;

        if ($inserted >= 21) {
            break;
        }
    }
}

function load_tracks(): array
{
    $pdo = db();
    $stmt = $pdo->query('SELECT t.id, t.name, t.language, t.difficulty, t.xp_per_problem, t.description, COUNT(p.id) AS problem_count, COALESCE(SUM(p.xp_reward), 0) AS total_xp FROM tracks t LEFT JOIN problems p ON p.track_id = t.id GROUP BY t.id ORDER BY t.language');
    $tracks = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $tracks[$row['id']] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'language' => $row['language'],
            'difficulty' => $row['difficulty'],
            'xp_per_problem' => (int) $row['xp_per_problem'],
            'description' => $row['description'],
            'problem_count' => (int) $row['problem_count'],
            'total_xp' => (int) $row['total_xp'],
            'badges' => [],
            'problem_themes' => [],
        ];
    }

    if (!$tracks) {
        return [];
    }

    $badgeStmt = $pdo->query('SELECT track_id, badge FROM track_badges ORDER BY track_id, sort_order');
    foreach ($badgeStmt->fetchAll(PDO::FETCH_ASSOC) as $badgeRow) {
        $tracks[$badgeRow['track_id']]['badges'][] = $badgeRow['badge'];
    }

    $themeStmt = $pdo->query('SELECT track_id, theme FROM track_themes ORDER BY track_id, sort_order');
    foreach ($themeStmt->fetchAll(PDO::FETCH_ASSOC) as $themeRow) {
        $tracks[$themeRow['track_id']]['problem_themes'][] = $themeRow['theme'];
    }

    return array_values($tracks);
}

function get_track(string $trackId): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT t.id, t.name, t.language, t.difficulty, t.xp_per_problem, t.description, COUNT(p.id) AS problem_count, COALESCE(SUM(p.xp_reward), 0) AS total_xp FROM tracks t LEFT JOIN problems p ON p.track_id = t.id WHERE t.id = :id');
    $stmt->execute([':id' => $trackId]);
    $track = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$track) {
        return null;
    }

    $track['xp_per_problem'] = (int) $track['xp_per_problem'];
    $track['problem_count'] = (int) $track['problem_count'];
    $track['total_xp'] = (int) $track['total_xp'];

    $badgeStmt = $pdo->prepare('SELECT badge FROM track_badges WHERE track_id = :id ORDER BY sort_order');
    $badgeStmt->execute([':id' => $trackId]);
    $track['badges'] = array_map(fn($row) => $row['badge'], $badgeStmt->fetchAll(PDO::FETCH_ASSOC));

    $themeStmt = $pdo->prepare('SELECT theme FROM track_themes WHERE track_id = :id ORDER BY sort_order');
    $themeStmt->execute([':id' => $trackId]);
    $track['problem_themes'] = array_map(fn($row) => $row['theme'], $themeStmt->fetchAll(PDO::FETCH_ASSOC));

    $problemStmt = $pdo->prepare('SELECT id, title, synopsis, difficulty, xp_reward, focus FROM problems WHERE track_id = :track ORDER BY xp_reward, id');
    $problemStmt->execute([':track' => $trackId]);
    $solved = [];
    $user = current_user();
    if ($user) {
        $solved = completed_problem_ids($trackId, $user['name']);
    }

    $track['problems'] = array_map(function ($row) use ($solved) {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'synopsis' => $row['synopsis'],
            'difficulty' => $row['difficulty'],
            'xp_reward' => (int) $row['xp_reward'],
            'focus' => $row['focus'],
            'solved' => in_array((int) $row['id'], $solved, true),
        ];
    }, $problemStmt->fetchAll(PDO::FETCH_ASSOC));

    $track['problem_count'] = count($track['problems']);

    return $track;
}

function load_leaderboards(): array
{
    $pdo = db();
    $sql = "SELECT track_id, player_name, COUNT(DISTINCT problem_id) AS solved, SUM(score) AS xp, SUM(CASE WHEN status = 'perfect' THEN 1 ELSE 0 END) AS perfect_runs, MAX(completed_at) AS last_completed FROM results GROUP BY track_id, player_name ORDER BY track_id, xp DESC, last_completed DESC";
    $stmt = $pdo->query($sql);

    $leaderboards = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $leaderboards[$row['track_id']][] = [
            'name' => $row['player_name'],
            'xp' => (int) $row['xp'],
            'solved' => (int) $row['solved'],
            'perfect_runs' => (int) $row['perfect_runs'],
            'last_completed' => $row['last_completed'],
        ];
    }

    return $leaderboards;
}

function find_user_by_username(string $username): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :username');
    $stmt->execute([':username' => $username]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function register_user(string $username, string $password): array
{
    $username = trim($username);
    if ($username === '' || !preg_match('/^[A-Za-z0-9_\-]{3,32}$/', $username)) {
        return ['success' => false, 'message' => 'Choose a username using 3-32 letters, numbers, underscores, or dashes.'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Use a password with at least 8 characters.'];
    }

    if (find_user_by_username($username)) {
        return ['success' => false, 'message' => 'That username is already taken. Pick another handle.'];
    }

    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, "player")');
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    $user = [
        'id' => (int) $pdo->lastInsertId(),
        'username' => $username,
        'role' => 'player',
    ];

    return ['success' => true, 'message' => 'Registration complete! Welcome to the arena.', 'user' => $user];
}

function authenticate_user(string $username, string $password): ?array
{
    $user = find_user_by_username($username);
    if (!$user) {
        return null;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return null;
    }

    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['username'],
        'role' => $user['role'],
    ];
    $_SESSION['enrollments'] = [];
}

function logout_user(): void
{
    session_regenerate_id(true);
    unset($_SESSION['user'], $_SESSION['enrollments']);
}

function current_user(): ?array
{
    if (!isset($_SESSION['user'])) {
        return null;
    }

    $sessionUser = $_SESSION['user'];
    if (!isset($sessionUser['id'])) {
        logout_user();
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = :id');
    $stmt->execute([':id' => $sessionUser['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        logout_user();
        return null;
    }

    $user = [
        'id' => (int) $row['id'],
        'name' => $row['username'],
        'role' => $row['role'],
    ];
    $_SESSION['user'] = $user;

    return $user;
}

function is_admin(): bool
{
    $user = current_user();
    return $user ? $user['role'] === 'admin' : false;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Admin access required.');
    }
}

function add_flash(string $type, string $message): void
{
    if (!isset($_SESSION['flashes'])) {
        $_SESSION['flashes'] = [];
    }

    $_SESSION['flashes'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consume_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return $flashes;
}

function enroll_track(string $trackId): void
{
    if (!isset($_SESSION['enrollments'])) {
        $_SESSION['enrollments'] = [];
    }

    if (!in_array($trackId, $_SESSION['enrollments'], true)) {
        $_SESSION['enrollments'][] = $trackId;
    }
}

function is_enrolled(string $trackId): bool
{
    return in_array($trackId, $_SESSION['enrollments'] ?? [], true);
}

function enrollment_progress(string $trackId): array
{
    $pdo = db();
    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM problems WHERE track_id = :track');
    $totalStmt->execute([':track' => $trackId]);
    $total = (int) $totalStmt->fetchColumn();

    if ($total === 0) {
        return ['completed' => 0, 'total' => 0, 'percentage' => 0, 'xp' => 0];
    }

    if (is_admin()) {
        $summaryStmt = $pdo->prepare('SELECT COUNT(DISTINCT problem_id) AS solved, COALESCE(SUM(score), 0) AS xp FROM results WHERE track_id = :track');
        $summaryStmt->execute([':track' => $trackId]);
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: ['solved' => 0, 'xp' => 0];
        $completed = (int) $summary['solved'];
        $xp = (int) $summary['xp'];
    } else {
        $user = current_user();
        if (!$user) {
            return ['completed' => 0, 'total' => $total, 'percentage' => 0, 'xp' => 0];
        }

        $progressStmt = $pdo->prepare('SELECT COUNT(DISTINCT problem_id) AS solved, COALESCE(SUM(score), 0) AS xp FROM results WHERE track_id = :track AND player_name = :player');
        $progressStmt->execute([
            ':track' => $trackId,
            ':player' => $user['name'],
        ]);
        $progress = $progressStmt->fetch(PDO::FETCH_ASSOC) ?: ['solved' => 0, 'xp' => 0];
        $completed = (int) $progress['solved'];
        $xp = (int) $progress['xp'];
    }

    $percentage = min(100, (int) round(($completed / $total) * 100));

    return [
        'completed' => min($completed, $total),
        'total' => $total,
        'percentage' => $percentage,
        'xp' => $xp,
    ];
}

function user_overall_progress(string $playerName): array
{
    $pdo = db();
    $summaryStmt = $pdo->prepare('SELECT COUNT(DISTINCT problem_id) AS solved, COALESCE(SUM(score), 0) AS xp, COUNT(DISTINCT track_id) AS tracks FROM results WHERE player_name = :player');
    $summaryStmt->execute([':player' => $playerName]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: ['solved' => 0, 'xp' => 0, 'tracks' => 0];

    $nextMilestone = ((int) ceil(max(1, $summary['solved']) / 10)) * 10;

    $totalProblems = (int) db()->query('SELECT COUNT(*) FROM problems')->fetchColumn();
    $percentage = $totalProblems > 0 ? min(100, (int) round(((int) $summary['solved'] / $totalProblems) * 100)) : 0;

    return [
        'solved' => (int) $summary['solved'],
        'xp' => (int) $summary['xp'],
        'tracks' => (int) $summary['tracks'],
        'nextMilestone' => max(10, $nextMilestone),
        'totalProblems' => $totalProblems,
        'completionPercent' => $percentage,
    ];
}

function record_result(string $trackId, int $problemId, string $playerName, string $status, int $score): bool
{
    $pdo = db();
    $existing = $pdo->prepare('SELECT id FROM results WHERE problem_id = :problem AND player_name = :player LIMIT 1');
    $existing->execute([
        ':problem' => $problemId,
        ':player' => $playerName,
    ]);

    if ($existing->fetchColumn()) {
        return false;
    }

    $stmt = $pdo->prepare('INSERT INTO results (track_id, problem_id, player_name, status, score, completed_at) VALUES (:track, :problem, :player, :status, :score, :completed_at)');
    $stmt->execute([
        ':track' => $trackId,
        ':problem' => $problemId,
        ':player' => $playerName,
        ':status' => $status,
        ':score' => $score,
        ':completed_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
    ]);

    return true;
}

function completed_problem_ids(string $trackId, string $playerName): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT problem_id FROM results WHERE track_id = :track AND player_name = :player');
    $stmt->execute([
        ':track' => $trackId,
        ':player' => $playerName,
    ]);

    return array_values(array_unique(array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'problem_id'))));
}

function has_player_completed_problem(int $problemId, string $playerName): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT 1 FROM results WHERE problem_id = :problem AND player_name = :player LIMIT 1');
    $stmt->execute([
        ':problem' => $problemId,
        ':player' => $playerName,
    ]);

    return (bool) $stmt->fetchColumn();
}

function get_problem_with_fragments(string $trackId, int $problemId, ?string $playerName = null): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, track_id, title, synopsis, difficulty, xp_reward, focus FROM problems WHERE id = :id AND track_id = :track');
    $stmt->execute([
        ':id' => $problemId,
        ':track' => $trackId,
    ]);

    $problem = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$problem) {
        return null;
    }

    $fragmentStmt = $pdo->prepare('SELECT id, content, indent_level, is_distractor, sort_order FROM problem_fragments WHERE problem_id = :id');
    $fragmentStmt->execute([':id' => $problemId]);
    $fragmentRows = $fragmentStmt->fetchAll(PDO::FETCH_ASSOC);

    $fragments = array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'content' => $row['content'],
            'indent_level' => (int) $row['indent_level'],
            'is_distractor' => (bool) $row['is_distractor'],
            'sort_order' => $row['sort_order'] !== null ? (int) $row['sort_order'] : null,
        ];
    }, $fragmentRows);

    $shuffled = $fragments;
    shuffle($shuffled);

    $solutionCount = count(array_filter($fragments, static fn(array $fragment): bool => !$fragment['is_distractor']));

    $problemData = [
        'id' => (int) $problem['id'],
        'track_id' => $problem['track_id'],
        'title' => $problem['title'],
        'synopsis' => $problem['synopsis'],
        'difficulty' => $problem['difficulty'],
        'xp_reward' => (int) $problem['xp_reward'],
        'focus' => $problem['focus'],
        'fragments' => $shuffled,
        'solution_count' => $solutionCount,
        'distractor_count' => count($fragments) - $solutionCount,
        'solved' => false,
    ];

    if ($playerName) {
        $problemData['solved'] = has_player_completed_problem((int) $problem['id'], $playerName);
    }

    return $problemData;
}

function grade_problem_attempt(string $trackId, int $problemId, array $submittedFragmentIds, string $playerName): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, xp_reward FROM problems WHERE id = :id AND track_id = :track');
    $stmt->execute([
        ':id' => $problemId,
        ':track' => $trackId,
    ]);

    $problem = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$problem) {
        return ['success' => false, 'message' => 'Unknown puzzle.'];
    }

    $fragmentStmt = $pdo->prepare('SELECT id, sort_order, is_distractor FROM problem_fragments WHERE problem_id = :id');
    $fragmentStmt->execute([':id' => $problemId]);
    $rows = $fragmentStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        return ['success' => false, 'message' => 'Puzzle fragments are missing.'];
    }

    $fragmentMap = [];
    $solutionCount = 0;
    foreach ($rows as $row) {
        $fragmentMap[(int) $row['id']] = [
            'sort_order' => $row['sort_order'] !== null ? (int) $row['sort_order'] : null,
            'is_distractor' => (bool) $row['is_distractor'],
        ];
        if (!(bool) $row['is_distractor']) {
            $solutionCount++;
        }
    }

    $submitted = array_values(array_map('intval', $submittedFragmentIds));
    if (!$submitted) {
        return ['success' => false, 'message' => 'Drag code lines into the solution area to submit.'];
    }

    if (count($submitted) !== count(array_unique($submitted))) {
        return ['success' => false, 'message' => 'Each fragment can only be used once.'];
    }

    foreach ($submitted as $fragmentId) {
        if (!isset($fragmentMap[$fragmentId])) {
            return ['success' => false, 'message' => 'One of the fragments is invalid.'];
        }
    }

    if (count($submitted) < $solutionCount) {
        return ['success' => false, 'message' => 'You are missing required lines.'];
    }

    if (count($submitted) > $solutionCount) {
        return ['success' => false, 'message' => 'A distractor slipped into your solution.'];
    }

    $ordersByPosition = [];
    foreach ($submitted as $fragmentId) {
        $fragment = $fragmentMap[$fragmentId];
        if ($fragment['is_distractor'] || $fragment['sort_order'] === null) {
            return ['success' => false, 'message' => 'A distractor slipped into your solution.'];
        }
        $ordersByPosition[] = $fragment['sort_order'];
    }

    $expectedOrder = range(1, $solutionCount);
    if ($ordersByPosition !== $expectedOrder) {
        return ['success' => false, 'message' => 'The sequence is out of order. Try again!'];
    }

    $xpReward = (int) $problem['xp_reward'];
    $xpBonus = 0;
    $totalXp = $xpReward;
    $alreadySolved = has_player_completed_problem($problemId, $playerName);

    if (!$alreadySolved) {
        [$xpBonus, $challengeId] = resolve_daily_bonus_for_completion($problemId, $playerName);
        $totalXp += $xpBonus;
        $recorded = record_result($trackId, $problemId, $playerName, 'perfect', $totalXp);
        if ($recorded && $challengeId) {
            mark_daily_completion($challengeId, $playerName);
        }
    }

    $message = $alreadySolved ? 'Puzzle already mastered — XP was previously awarded.' : 'Legendary! You assembled the puzzle flawlessly.';
    if (!$alreadySolved && $xpBonus > 0) {
        $message .= ' Daily bonus unlocked!';
    }

    return [
        'success' => true,
        'message' => $message,
        'xp' => $totalXp,
        'xp_bonus' => $xpBonus,
        'alreadySolved' => $alreadySolved,
    ];
}

function get_daily_challenge(?string $playerName = null, ?DateTimeInterface $date = null): ?array
{
    $pdo = db();
    $today = $date ? $date->format('Y-m-d') : (new DateTimeImmutable('today'))->format('Y-m-d');

    if ($playerName) {
        $challenge = fetch_random_unsolved_challenge($pdo, $playerName, $today);

        if (!$challenge) {
            $challenge = fetch_daily_challenge_row($pdo, $today);
            if (!$challenge) {
                $challenge = auto_seed_daily_challenge($pdo, $today);
            }

            if (!$challenge || has_completed_daily_challenge((int) $challenge['id'], $playerName)) {
                return null;
            }
        }

        $challenge['completed_players'] = fetch_daily_completion_count($pdo, (int) $challenge['id']);

        return format_daily_challenge_payload($challenge, false);
    }

    $challenge = fetch_daily_challenge_row($pdo, $today);
    if (!$challenge) {
        $challenge = auto_seed_daily_challenge($pdo, $today);
    }

    if (!$challenge) {
        return null;
    }

    $challenge['completed_players'] = fetch_daily_completion_count($pdo, (int) $challenge['id']);

    return format_daily_challenge_payload($challenge, false);
}

function format_daily_challenge_payload(array $challenge, bool $completed): array
{
    return [
        'id' => (int) $challenge['id'],
        'date' => $challenge['challenge_date'],
        'problem_id' => (int) $challenge['problem_id'],
        'track_id' => $challenge['track_id'],
        'track_name' => $challenge['track_name'],
        'title' => $challenge['title'],
        'description' => $challenge['description'],
        'xp_reward' => (int) $challenge['xp_reward'],
        'xp_bonus' => (int) $challenge['xp_bonus'],
        'total_xp' => (int) $challenge['xp_reward'] + (int) $challenge['xp_bonus'],
        'problem_title' => $challenge['problem_title'],
        'completed' => $completed,
        'completed_players' => (int) ($challenge['completed_players'] ?? 0),
    ];
}

function fetch_daily_challenge_row(PDO $pdo, string $date): ?array
{
    $stmt = $pdo->prepare('SELECT dc.*, p.track_id, p.title AS problem_title, p.xp_reward, t.name AS track_name FROM daily_challenges dc JOIN problems p ON p.id = dc.problem_id JOIN tracks t ON t.id = p.track_id WHERE challenge_date = :date');
    $stmt->execute([':date' => $date]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function fetch_random_unsolved_challenge(PDO $pdo, string $playerName, string $today): ?array
{
    $sql = 'SELECT dc.*, p.track_id, p.title AS problem_title, p.xp_reward, t.name AS track_name
            FROM daily_challenges dc
            JOIN problems p ON p.id = dc.problem_id
            JOIN tracks t ON t.id = p.track_id
            LEFT JOIN daily_attempts da ON da.challenge_id = dc.id AND da.player_name = :player
            WHERE da.id IS NULL
            ORDER BY CASE
                WHEN dc.challenge_date = :today THEN 0
                WHEN dc.challenge_date > :today THEN 1
                ELSE 2
            END, RAND()
            LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':player' => $playerName,
        ':today' => $today,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function auto_seed_daily_challenge(PDO $pdo, string $date): ?array
{
    $problemId = $pdo->query('SELECT id FROM problems ORDER BY RAND() LIMIT 1')->fetchColumn();
    if (!$problemId) {
        return null;
    }

    $problemStmt = $pdo->prepare('SELECT p.id, p.title, p.synopsis, p.track_id, p.xp_reward, p.difficulty, p.focus, t.name AS track_name FROM problems p JOIN tracks t ON t.id = p.track_id WHERE p.id = :problem');
    $problemStmt->execute([':problem' => $problemId]);
    $problem = $problemStmt->fetch(PDO::FETCH_ASSOC);
    if (!$problem) {
        return null;
    }

    $title = sprintf('Daily: %s', $problem['title']);
    $description = sprintf('Master this %s %s challenge from %s.', strtolower($problem['difficulty']), strtolower($problem['focus']), $problem['track_name']);
    $xpBonus = max(20, (int) round($problem['xp_reward'] * 0.3));

    upsert_daily_challenge(new DateTimeImmutable($date), (int) $problem['id'], $title, $description, $xpBonus);

    return fetch_daily_challenge_row($pdo, $date);
}

function upsert_daily_challenge(DateTimeInterface $date, int $problemId, string $title, string $description, int $xpBonus = 0): void
{
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO daily_challenges (challenge_date, problem_id, title, description, xp_bonus) VALUES (:date, :problem, :title, :description, :bonus) ON DUPLICATE KEY UPDATE problem_id = VALUES(problem_id), title = VALUES(title), description = VALUES(description), xp_bonus = VALUES(xp_bonus)');
    $stmt->execute([
        ':date' => $date->format('Y-m-d'),
        ':problem' => $problemId,
        ':title' => $title,
        ':description' => $description,
        ':bonus' => $xpBonus,
    ]);
}

function mark_daily_completion(int $challengeId, string $playerName): void
{
    $pdo = db();
    $attemptStmt = $pdo->prepare('INSERT IGNORE INTO daily_attempts (challenge_id, player_name, completed_at) VALUES (:challenge, :player, :completed_at)');
    $attemptStmt->execute([
        ':challenge' => $challengeId,
        ':player' => $playerName,
        ':completed_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
    ]);
}

function has_completed_daily_challenge(int $challengeId, string $playerName): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT 1 FROM daily_attempts WHERE challenge_id = :challenge AND player_name = :player LIMIT 1');
    $stmt->execute([
        ':challenge' => $challengeId,
        ':player' => $playerName,
    ]);

    return (bool) $stmt->fetchColumn();
}

function fetch_daily_challenge_by_id(PDO $pdo, int $challengeId): ?array
{
    $stmt = $pdo->prepare('SELECT dc.*, p.track_id, p.title AS problem_title, p.xp_reward, t.name AS track_name FROM daily_challenges dc JOIN problems p ON p.id = dc.problem_id JOIN tracks t ON t.id = p.track_id WHERE dc.id = :id');
    $stmt->execute([':id' => $challengeId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function fetch_daily_completion_count(PDO $pdo, int $challengeId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM daily_attempts WHERE challenge_id = :challenge');
    $stmt->execute([':challenge' => $challengeId]);

    return (int) $stmt->fetchColumn();
}

function resolve_daily_bonus_for_completion(int $problemId, string $playerName): array
{
    $pdo = db();
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');

    $sql = 'SELECT dc.id, dc.xp_bonus
            FROM daily_challenges dc
            LEFT JOIN daily_attempts da ON da.challenge_id = dc.id AND da.player_name = :player
            WHERE dc.problem_id = :problem AND da.id IS NULL
            ORDER BY CASE
                WHEN dc.challenge_date = :today THEN 0
                WHEN dc.challenge_date > :today THEN 1
                ELSE 2
            END, dc.challenge_date ASC
            LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':player' => $playerName,
        ':problem' => $problemId,
        ':today' => $today,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return [0, null];
    }

    return [(int) $row['xp_bonus'], (int) $row['id']];
}

function admin_set_daily_challenge(string $date, int $problemId, string $title, string $description, int $xpBonus): void
{
    $normalized = DateTimeImmutable::createFromFormat('Y-m-d', $date) ?: new DateTimeImmutable('today');
    upsert_daily_challenge($normalized, $problemId, $title, $description, $xpBonus);
}

function generate_fragments_for_problem(string $trackId, array $problem, int $problemIndex): array
{
    return match ($trackId) {
        'php' => generate_php_fragments($problem, $problemIndex),
        'python' => generate_python_fragments($problem, $problemIndex),
        'javascript' => generate_javascript_fragments($problem, $problemIndex),
        default => [],
    };
}

function generate_php_fragments(array $problem, int $problemIndex): array
{
    $xp = (int) ($problem['xp'] ?? $problem['xp_reward'] ?? 0);
    $title = var_export($problem['title'], true);
    $focus = var_export($problem['focus'], true);
    $difficulty = var_export($problem['difficulty'], true);
    $stepsVar = php_variable($problem['focus'], 'steps');
    $refinedVar = php_variable($problem['focus'], 'refined');
    $ledgerVar = php_variable($problem['focus'], 'ledger');

    $solution = [
        ['content' => sprintf('$quest = ["title" => %s];', $title), 'indent_level' => 0],
        ['content' => sprintf('$quest["focus"] = %s;', $focus), 'indent_level' => 0],
        ['content' => sprintf('$quest["difficulty"] = %s;', $difficulty), 'indent_level' => 0],
        ['content' => sprintf('%s = calibrate_steps($quest, %d);', $stepsVar, $xp), 'indent_level' => 0],
        ['content' => sprintf('%s = [];', $refinedVar), 'indent_level' => 0],
        ['content' => sprintf('foreach (%s as $step) {', $stepsVar), 'indent_level' => 0],
        ['content' => sprintf('%s[] = polish_step($step);', $refinedVar), 'indent_level' => 1],
        ['content' => '}', 'indent_level' => 0],
        ['content' => sprintf('%s = sync_ledgers(%s);', $ledgerVar, $refinedVar), 'indent_level' => 0],
        ['content' => sprintf('$quest["steps"] = %s;', $ledgerVar), 'indent_level' => 0],
        ['content' => 'return $quest;', 'indent_level' => 0],
    ];

    if ($problemIndex % 4 === 0) {
        $solution = array_merge(
            array_slice($solution, 0, 4),
            [
                ['content' => sprintf('if (empty(%s)) {', $stepsVar), 'indent_level' => 0],
                ['content' => sprintf('%s = ignite_fallbacks($quest);', $stepsVar), 'indent_level' => 1],
                ['content' => '}', 'indent_level' => 0],
            ],
            array_slice($solution, 4)
        );
    }

    $distractors = [
        ['content' => sprintf('shuffle(%s);', $refinedVar), 'indent_level' => 0],
        ['content' => sprintf('return array_sum(%s);', $stepsVar), 'indent_level' => 0],
        ['content' => sprintf('%s = array_reverse(%s);', $ledgerVar, $stepsVar), 'indent_level' => 0],
    ];

    return assign_fragment_order($solution, $distractors);
}

function generate_python_fragments(array $problem, int $problemIndex): array
{
    $xp = (int) ($problem['xp'] ?? $problem['xp_reward'] ?? 0);
    $focusLiteral = json_encode($problem['focus'], JSON_UNESCAPED_UNICODE);
    $titleLiteral = json_encode($problem['title'], JSON_UNESCAPED_UNICODE);
    $difficultyLiteral = json_encode($problem['difficulty'], JSON_UNESCAPED_UNICODE);
    $funcName = 'assemble_' . python_identifier($problem['focus'], 'quest');
    $stepsVar = python_identifier($problem['focus'], 'steps');
    $refinedVar = python_identifier($problem['focus'], 'refined');

    $solution = [
        ['content' => sprintf('def %s():', $funcName), 'indent_level' => 0],
        ['content' => sprintf('quest = {"title": %s, "focus": %s}', $titleLiteral, $focusLiteral), 'indent_level' => 1],
        ['content' => sprintf('quest["difficulty"] = %s', $difficultyLiteral), 'indent_level' => 1],
        ['content' => sprintf('%s = calibrate_steps(quest, xp=%d)', $stepsVar, $xp), 'indent_level' => 1],
        ['content' => sprintf('%s: list[str] = []', $refinedVar), 'indent_level' => 1],
        ['content' => sprintf('for step in %s:', $stepsVar), 'indent_level' => 1],
        ['content' => sprintf('%s.append(polish_step(step))', $refinedVar), 'indent_level' => 2],
        ['content' => sprintf('quest["steps"] = ledger_sync(%s)', $refinedVar), 'indent_level' => 1],
        ['content' => 'return quest', 'indent_level' => 1],
    ];

    if ($problemIndex % 3 === 1) {
        $solution = array_merge(
            array_slice($solution, 0, 5),
            [
                ['content' => sprintf('if not %s:', $stepsVar), 'indent_level' => 1],
                ['content' => sprintf('%s = bootstrap_fallbacks(quest)', $stepsVar), 'indent_level' => 2],
            ],
            array_slice($solution, 5)
        );
    }

    $distractors = [
        ['content' => sprintf('return sorted(%s)', $stepsVar), 'indent_level' => 1],
        ['content' => sprintf('%s.sort(reverse=True)', $refinedVar), 'indent_level' => 2],
        ['content' => sprintf('quest["steps"] = list(reversed(%s))', $stepsVar), 'indent_level' => 1],
    ];

    return assign_fragment_order($solution, $distractors);
}

function generate_javascript_fragments(array $problem, int $problemIndex): array
{
    $xp = (int) ($problem['xp'] ?? $problem['xp_reward'] ?? 0);
    $titleLiteral = json_encode($problem['title'], JSON_UNESCAPED_UNICODE);
    $focusLiteral = json_encode($problem['focus'], JSON_UNESCAPED_UNICODE);
    $difficultyLiteral = json_encode($problem['difficulty'], JSON_UNESCAPED_UNICODE);
    $functionName = js_function_name($problem['focus'], 'quest');

    $solution = [
        ['content' => sprintf('export function %s() {', $functionName), 'indent_level' => 0],
        ['content' => 'const quest = {', 'indent_level' => 1],
        ['content' => sprintf('title: %s,', $titleLiteral), 'indent_level' => 2],
        ['content' => sprintf('focus: %s,', $focusLiteral), 'indent_level' => 2],
        ['content' => sprintf('difficulty: %s,', $difficultyLiteral), 'indent_level' => 2],
        ['content' => '};', 'indent_level' => 1],
        ['content' => sprintf('const steps = calibrateSteps(quest, { xp: %d });', $xp), 'indent_level' => 1],
        ['content' => 'const refined = [];', 'indent_level' => 1],
        ['content' => 'for (const step of steps) {', 'indent_level' => 1],
        ['content' => 'refined.push(polishStep(step));', 'indent_level' => 2],
        ['content' => '}', 'indent_level' => 1],
        ['content' => 'quest.steps = syncLedger(refined);', 'indent_level' => 1],
        ['content' => 'return quest;', 'indent_level' => 1],
        ['content' => '}', 'indent_level' => 0],
    ];

    if ($problemIndex % 5 === 2) {
        $solution = array_merge(
            array_slice($solution, 0, 7),
            [
                ['content' => 'if (!steps.length) {', 'indent_level' => 1],
                ['content' => 'bootstrapFallbacks(quest, steps);', 'indent_level' => 2],
                ['content' => '}', 'indent_level' => 1],
            ],
            array_slice($solution, 7)
        );
    }

    $distractors = [
        ['content' => 'return steps.sort();', 'indent_level' => 1],
        ['content' => 'refined.reverse();', 'indent_level' => 2],
        ['content' => 'quest.steps = steps.reverse();', 'indent_level' => 1],
    ];

    return assign_fragment_order($solution, $distractors);
}

function assign_fragment_order(array $solutionLines, array $distractorLines): array
{
    $fragments = [];
    $order = 1;
    foreach ($solutionLines as $line) {
        $fragments[] = [
            'content' => $line['content'],
            'indent_level' => $line['indent_level'],
            'is_distractor' => false,
            'sort_order' => $order++,
        ];
    }

    foreach ($distractorLines as $line) {
        $fragments[] = [
            'content' => $line['content'],
            'indent_level' => $line['indent_level'],
            'is_distractor' => true,
            'sort_order' => null,
        ];
    }

    return $fragments;
}

function php_variable(string $focus, string $suffix): string
{
    $base = preg_replace('/[^a-z0-9]+/i', '', ucwords($focus));
    if ($base === '') {
        $base = 'Quest';
    }

    return '$' . lcfirst($base) . ucfirst($suffix);
}

function python_identifier(string $focus, string $suffix): string
{
    $base = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $focus));
    $base = trim($base, '_');
    if ($base === '') {
        $base = $suffix;
    }
    if (!preg_match('/^[a-z_]/', $base)) {
        $base = '_' . $base;
    }

    return $base . '_' . $suffix;
}

function js_function_name(string $focus, string $suffix): string
{
    $base = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $focus));
    $words = array_values(array_filter(explode(' ', $base)));
    if (!$words) {
        $words = [$suffix];
    }

    $identifier = array_shift($words);
    foreach ($words as $word) {
        $identifier .= ucfirst($word);
    }

    $identifier .= ucfirst($suffix);

    if (!preg_match('/^[a-z_]/i', $identifier)) {
        $identifier = lcfirst($suffix);
    }

    return $identifier;
}

function slugify(string $value): string
{
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
    if ($slug === '') {
        $slug = 'track-' . bin2hex(random_bytes(2));
    }

    return $slug;
}

function ensure_unique_track_id(string $desired): string
{
    $pdo = db();
    $base = $desired;
    $attempt = $desired;
    $counter = 2;
    $stmt = $pdo->prepare('SELECT 1 FROM tracks WHERE id = :id');
    while (true) {
        $stmt->execute([':id' => $attempt]);
        if (!$stmt->fetchColumn()) {
            return $attempt;
        }
        $attempt = $base . '-' . $counter;
        $counter++;
    }
}

function parse_fragment_lines(string $input): array
{
    $lines = preg_split('/\r?\n/', $input);
    $fragments = [];
    foreach ($lines as $line) {
        if (trim($line) === '') {
            continue;
        }
        $normalized = str_replace("\t", '    ', rtrim($line));
        $leading = strlen($normalized) - strlen(ltrim($normalized));
        $indent = (int) floor($leading / 4);
        $fragments[] = [
            'content' => ltrim($normalized),
            'indent_level' => $indent,
        ];
    }

    return $fragments;
}

function create_track_from_request(array $input): array
{
    $name = trim($input['name'] ?? '');
    $language = trim($input['language'] ?? '');
    $difficulty = trim($input['difficulty'] ?? 'Intermediate');
    $xpPerProblem = max(10, (int) ($input['xp_per_problem'] ?? 50));
    $description = trim($input['description'] ?? '');

    if ($name === '' || $language === '' || $description === '') {
        return ['success' => false, 'message' => 'Name, language, and description are required.'];
    }

    $requestedId = trim($input['track_id'] ?? '');
    $trackId = ensure_unique_track_id($requestedId !== '' ? slugify($requestedId) : slugify($name));

    $badges = array_filter(array_map('trim', explode(',', $input['badges'] ?? '')));
    $themes = array_filter(array_map('trim', explode(',', $input['themes'] ?? '')));

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO tracks (id, name, language, difficulty, xp_per_problem, description) VALUES (:id, :name, :language, :difficulty, :xp, :description)');
        $stmt->execute([
            ':id' => $trackId,
            ':name' => $name,
            ':language' => $language,
            ':difficulty' => $difficulty,
            ':xp' => $xpPerProblem,
            ':description' => $description,
        ]);

        if ($badges) {
            $badgeStmt = $pdo->prepare('INSERT INTO track_badges (track_id, badge, sort_order) VALUES (:track, :badge, :sort_order)');
            foreach ($badges as $index => $badge) {
                $badgeStmt->execute([
                    ':track' => $trackId,
                    ':badge' => $badge,
                    ':sort_order' => $index,
                ]);
            }
        }

        if ($themes) {
            $themeStmt = $pdo->prepare('INSERT INTO track_themes (track_id, theme, sort_order) VALUES (:track, :theme, :sort_order)');
            foreach ($themes as $index => $theme) {
                $themeStmt->execute([
                    ':track' => $trackId,
                    ':theme' => $theme,
                    ':sort_order' => $index,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Unable to create track: ' . $exception->getMessage()];
    }

    return ['success' => true, 'message' => 'Track created successfully.', 'track_id' => $trackId];
}

function create_problem_from_request(array $input): array
{
    $trackId = trim($input['track_id'] ?? '');
    $title = trim($input['title'] ?? '');
    $synopsis = trim($input['synopsis'] ?? '');
    $difficulty = trim($input['difficulty'] ?? 'Bronze');
    $xpReward = max(5, (int) ($input['xp_reward'] ?? 0));
    $focus = trim($input['focus'] ?? 'Core concepts');
    $solutionInput = $input['solution_fragments'] ?? '';
    $distractorInput = $input['distractor_fragments'] ?? '';

    if ($trackId === '' || $title === '' || $synopsis === '' || $solutionInput === '') {
        return ['success' => false, 'message' => 'Track, title, synopsis, and solution fragments are required.'];
    }

    $pdo = db();
    $trackStmt = $pdo->prepare('SELECT 1 FROM tracks WHERE id = :id');
    $trackStmt->execute([':id' => $trackId]);
    if (!$trackStmt->fetchColumn()) {
        return ['success' => false, 'message' => 'Selected track does not exist.'];
    }

    $solutionFragments = parse_fragment_lines($solutionInput);
    if (count($solutionFragments) < 2) {
        return ['success' => false, 'message' => 'Provide at least two ordered solution fragments.'];
    }

    $distractorFragments = parse_fragment_lines($distractorInput);
    $fragments = assign_fragment_order($solutionFragments, $distractorFragments);

    $pdo->beginTransaction();
    try {
        $problemStmt = $pdo->prepare('INSERT INTO problems (track_id, title, synopsis, difficulty, xp_reward, focus) VALUES (:track, :title, :synopsis, :difficulty, :xp, :focus)');
        $problemStmt->execute([
            ':track' => $trackId,
            ':title' => $title,
            ':synopsis' => $synopsis,
            ':difficulty' => $difficulty,
            ':xp' => $xpReward,
            ':focus' => $focus,
        ]);
        $problemId = (int) $pdo->lastInsertId();

        $fragmentStmt = $pdo->prepare('INSERT INTO problem_fragments (problem_id, content, indent_level, is_distractor, sort_order) VALUES (:problem, :content, :indent, :distractor, :sort_order)');
        foreach ($fragments as $fragment) {
            $fragmentStmt->execute([
                ':problem' => $problemId,
                ':content' => $fragment['content'],
                ':indent' => $fragment['indent_level'],
                ':distractor' => $fragment['is_distractor'] ? 1 : 0,
                ':sort_order' => $fragment['sort_order'],
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Unable to create puzzle: ' . $exception->getMessage()];
    }

    return ['success' => true, 'message' => 'Puzzle added successfully.', 'problem_id' => $problemId];
}

function list_all_problems(): array
{
    $pdo = db();
    $stmt = $pdo->query('SELECT p.id, p.title, p.track_id, p.difficulty, t.name AS track_name FROM problems p JOIN tracks t ON t.id = p.track_id ORDER BY t.language, p.title');

    return array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'track_id' => $row['track_id'],
            'track_name' => $row['track_name'],
            'difficulty' => $row['difficulty'],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function bulk_import_problem_fragments(?array $file): array
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload a CSV file containing fragments to import.'];
    }

    $handle = fopen($file['tmp_name'], 'rb');
    if (!$handle) {
        return ['success' => false, 'message' => 'Unable to read uploaded fragment file.'];
    }

    $pdo = db();
    $problemIds = $pdo->query('SELECT id FROM problems')->fetchAll(PDO::FETCH_COLUMN);
    $validProblems = array_fill_keys(array_map('intval', $problemIds), true);

    $insertStmt = $pdo->prepare('INSERT INTO problem_fragments (problem_id, content, indent_level, is_distractor, sort_order) VALUES (:problem, :content, :indent, :distractor, :sort_order)');

    $inserted = 0;
    $skipped = 0;

    $pdo->beginTransaction();
    try {
        $rowIndex = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }

            $rowIndex++;
            if ($rowIndex === 1 && isset($row[0]) && strtolower(trim((string) $row[0])) === 'problem_id') {
                continue;
            }

            $row = array_map(static fn($value) => is_string($value) ? trim($value) : $value, $row);
            [$problemId, $content, $indent, $isDistractor, $sortOrder] = array_pad($row, 5, null);

            $problemId = (int) $problemId;
            if ($problemId <= 0 || !isset($validProblems[$problemId]) || $content === null || $content === '') {
                $skipped++;
                continue;
            }

            $indentLevel = max(0, (int) $indent);
            $distractor = in_array(strtolower((string) $isDistractor), ['1', 'true', 'yes'], true) ? 1 : 0;
            $orderValue = ($sortOrder === null || $sortOrder === '') ? null : (int) $sortOrder;

            $insertStmt->execute([
                ':problem' => $problemId,
                ':content' => $content,
                ':indent' => $indentLevel,
                ':distractor' => $distractor,
                ':sort_order' => $orderValue,
            ]);

            $inserted++;
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        fclose($handle);
        return ['success' => false, 'message' => 'Bulk fragment import failed: ' . $exception->getMessage()];
    }

    fclose($handle);

    if ($inserted === 0) {
        return ['success' => false, 'message' => 'No fragments were imported. Verify the CSV structure.'];
    }

    $message = sprintf('Imported %d fragments%s.', $inserted, $skipped > 0 ? sprintf(' (%d rows skipped)', $skipped) : '');

    return ['success' => true, 'message' => $message];
}

function bulk_import_daily_challenges(?array $file): array
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload a CSV file containing daily challenge rows.'];
    }

    $handle = fopen($file['tmp_name'], 'rb');
    if (!$handle) {
        return ['success' => false, 'message' => 'Unable to read uploaded daily challenge file.'];
    }

    $pdo = db();
    $problemIds = $pdo->query('SELECT id FROM problems')->fetchAll(PDO::FETCH_COLUMN);
    $validProblems = array_fill_keys(array_map('intval', $problemIds), true);

    $inserted = 0;
    $skipped = 0;

    try {
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }

            if ($inserted + $skipped === 0 && isset($row[0]) && strtolower(trim((string) $row[0])) === 'challenge_date') {
                continue;
            }

            $row = array_map(static fn($value) => is_string($value) ? trim($value) : $value, $row);
            [$dateInput, $problemId, $title, $description, $xpBonus] = array_pad($row, 5, null);

            $problemId = (int) $problemId;
            if ($problemId <= 0 || !isset($validProblems[$problemId])) {
                $skipped++;
                continue;
            }

            $date = DateTimeImmutable::createFromFormat('Y-m-d', (string) $dateInput) ?: null;
            if (!$date) {
                $skipped++;
                continue;
            }

            $title = $title !== null && $title !== '' ? $title : 'Daily Challenge';
            $description = $description !== null && $description !== '' ? $description : 'Ready for today’s featured puzzle?';
            $bonus = max(0, (int) $xpBonus);

            upsert_daily_challenge($date, $problemId, $title, $description, $bonus);
            $inserted++;
        }
    } catch (Throwable $exception) {
        fclose($handle);
        return ['success' => false, 'message' => 'Bulk daily challenge import failed: ' . $exception->getMessage()];
    }

    fclose($handle);

    if ($inserted === 0) {
        return ['success' => false, 'message' => 'No daily challenges were imported. Check the CSV contents.'];
    }

    ensure_daily_challenge_schedule($pdo);

    $message = sprintf('Imported %d daily challenges%s.', $inserted, $skipped > 0 ? sprintf(' (%d rows skipped)', $skipped) : '');

    return ['success' => true, 'message' => $message];
}

