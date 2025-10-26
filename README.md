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
- Session-based enrollment flow backed by persistent SQLite progress data
- Separate leaderboards per language driven by aggregated result history
- Player dashboard with progress meters, badge previews, and enrollment actions
- Admin dashboard summarizing track health, top performers, and leaderboard shortcuts
- Responsive, neon-arcade UI themed for gamified learning experiences
- 25 escalating Parsons problems per track with distinct focus areas and XP tuning

## Project structure

```
public/         # Entry point and static assets
src/            # PHP helpers, SQLite bootstrap, and seeding logic
templates/      # View templates rendered per route
data/           # SQLite database file (generated automatically on first run)
```

The application provisions an SQLite database on first run. To plug in an external system, swap the bootstrap connection helpers for your preferred datastore.
