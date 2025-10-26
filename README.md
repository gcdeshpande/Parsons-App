# Parsons Playgrounds

A lightweight PHP playground that gamifies Parsons problems across language-specific tracks. Players can enroll in tracks, complete quests, and compete on dedicated leaderboards while admins monitor progress from a command center dashboard.

## Getting started

1. Ensure you have PHP 8.1+ installed along with the PDO MySQL extension.
2. Provide MySQL credentials via environment variables before starting the server. The bootstrap will create the database (default `parsons_app`) if it does not already exist.

   ```bash
   export DB_HOST=127.0.0.1
   export DB_PORT=3306
   export DB_NAME=parsons_app
   export DB_USER=root
   export DB_PASSWORD=secret
   ```

3. Start a local development server from the `public` directory:

   ```bash
   php -S localhost:8000 -t public
   ```
4. Visit `http://localhost:8000` in your browser.

## Features

- Multi-language tracks (PHP, Python, JavaScript) with distinct quests and XP rewards
- Session-based enrollment flow backed by persistent MySQL progress data
- Separate leaderboards per language driven by aggregated result history
- Player dashboard with progress meters, badge previews, and enrollment actions
- Admin dashboard summarizing track health, top performers, and leaderboard shortcuts
- Responsive, mobile-first neon arcade UI tuned for touch and desktop play
- 25 escalating Parsons problems per track with distinct focus areas and XP tuning
- Drag-and-drop Parsons puzzle arena with distractors, instant grading, and reset/shuffle controls (tap friendly on mobile)
- Daily challenge spotlight with XP bonuses and completion tracking
- Admin tooling to create new tracks, seed puzzles (including distractors), and curate the daily challenge

## Project structure

```
public/         # Entry point and static assets
src/            # PHP helpers, MySQL bootstrap, and seeding logic
templates/      # View templates rendered per route
```

The application provisions the MySQL schema and seed data on first run using the configured credentials.

## Accounts

- New players can register from the **Register** link in the navigation. Usernames accept letters, numbers, underscores, or dashes and require an 8+ character password.
- A default admin account is seeded for convenience: `admin` / `AdminPass123!`. Update or replace this user directly in the database for production deployments. Admin access is only available via stored users—there is no admin option on the public registration form.

## Solving Parsons puzzles

1. Enroll in a track from the home page or dashboard. The track roster unlocks a **Play** button for each quest once enrolled.
2. Inside the puzzle arena, drag fragments from the *Fragment pool* into the *Solution canvas*. Avoid distractors—only the required fragments in the correct order will validate.
3. Use **Shuffle** to randomize the pool or **Reset** to restore the initial order. On desktop you can drag; on mobile tap fragments to move them between the pool and canvas or switch panels with the *Fragment pool / Solution canvas* toggle.
4. Hit **Check solution** to submit. Perfect assemblies award XP (once per puzzle) and mark the quest as solved. The feedback panel surfaces errors (missing lines, distractors, or incorrect order) so you can iterate quickly.

### Daily challenge

- The home page and dashboard highlight a featured puzzle with combined base + bonus XP and community completion totals.
- Logged-in players see a random daily challenge they have not yet solved—once cleared it disappears until a fresh challenge is available, ensuring the card never repeats solved entries.
- Administrators can retarget the daily challenge to any puzzle (including new ones) and adjust the bonus XP or teaser copy from the dashboard. XP bonuses are awarded the first time a player completes the featured puzzle.

### Admin tools

When logged in as an admin, the dashboard exposes a management suite for live content updates:

- **Create track** – supply language, description, difficulty, optional custom slug, plus comma-separated badges/themes. The track is created immediately with default XP-per-problem.
- **Add puzzle** – choose a track, specify synopsis metadata, and paste solution/distractor fragments (one per line, indentation driven by leading spaces). The fragment pool and solution ordering are generated automatically.
- **Feature daily challenge** – pick any puzzle from the catalog, customise the headline/teaser, and adjust bonus XP for the selected date.
- **Bulk upload fragments** – import CSV rows (`problem_id,content,indent_level,is_distractor,sort_order`) to append fragments to existing puzzles.
- **Bulk upload daily challenges** – import CSV rows (`challenge_date,problem_id,title,description,xp_bonus`) to seed or update the rotating featured puzzle schedule.
