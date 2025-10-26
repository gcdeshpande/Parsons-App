# Parsons Playgrounds

A lightweight PHP playground that gamifies Parsons problems across language-specific tracks. Players can enroll in tracks, complete quests, and compete on dedicated leaderboards while admins monitor progress from a command center dashboard.

## Getting started

1. Ensure you have PHP 8.1+ installed.
2. Start a local development server from the `public` directory:

   ```bash
   php -S localhost:8000 -t public
   ```
3. Visit `http://localhost:8000` in your browser.

## Features

- Multi-language tracks (PHP, Python, JavaScript) with distinct quests and XP rewards
- Session-based enrollment flow and progress tracking for each player
- Separate leaderboards per language with streak indicators
- Player dashboard with progress meters, badge previews, and enrollment actions
- Admin dashboard summarizing track health, top performers, and leaderboard shortcuts
- Responsive, neon-arcade UI themed for gamified learning experiences

## Project structure

```
public/         # Entry point and static assets
src/            # PHP helpers and bootstrap
templates/      # View templates rendered per route
data/           # Seed data for tracks and leaderboards
```

The application uses JSON seed data for simplicity. Swap in a database or API-backed source to integrate with production systems.
