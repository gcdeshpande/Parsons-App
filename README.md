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

## Project structure

```
public/         # Entry point and static assets
src/            # PHP helpers, MySQL bootstrap, and seeding logic
templates/      # View templates rendered per route
```

The application provisions the MySQL schema and seed data on first run using the configured credentials.
