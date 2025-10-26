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

    seed_database($pdo);
}

function seed_database(PDO $pdo): void
{
    $trackCount = (int) $pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn();
    if ($trackCount > 0) {
        return;
    }

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
    $track['problems'] = array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'synopsis' => $row['synopsis'],
            'difficulty' => $row['difficulty'],
            'xp_reward' => (int) $row['xp_reward'],
            'focus' => $row['focus'],
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

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: index.php?page=login');
        exit;
    }
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

function record_result(string $trackId, int $problemId, string $playerName, string $status, int $score): void
{
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO results (track_id, problem_id, player_name, status, score, completed_at) VALUES (:track, :problem, :player, :status, :score, :completed_at)');
    $stmt->execute([
        ':track' => $trackId,
        ':problem' => $problemId,
        ':player' => $playerName,
        ':status' => $status,
        ':score' => $score,
        ':completed_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
    ]);
}
