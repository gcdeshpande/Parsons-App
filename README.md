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
- Responsive, neon-arcade UI themed for gamified learning experiences
- 25 escalating Parsons problems per track with distinct focus areas and XP tuning
- Drag-and-drop Parsons puzzle arena with distractors, instant grading, and reset/shuffle controls

## Project structure

```
public/         # Entry point and static assets
src/            # PHP helpers, MySQL bootstrap, and seeding logic
templates/      # View templates rendered per route
```

The application provisions the MySQL schema and seed data on first run using the configured credentials.

## Solving Parsons puzzles

1. Enroll in a track from the home page or dashboard. The track roster unlocks a **Play** button for each quest once enrolled.
2. Inside the puzzle arena, drag fragments from the *Fragment pool* into the *Solution canvas*. Avoid distractors—only the required fragments in the correct order will validate.
3. Use **Shuffle** to randomize the pool or **Reset** to restore the initial order. Double-clicking a fragment moves it between the pool and canvas for quick adjustments.
4. Hit **Check solution** to submit. Perfect assemblies award XP (once per puzzle) and mark the quest as solved. The feedback panel surfaces errors (missing lines, distractors, or incorrect order) so you can iterate quickly.
