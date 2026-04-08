# IEEPIS — JetBrains IDE Configuration Notes
#
# For PhpStorm / IntelliJ IDEA users.
# The .idea/ directory is git-ignored by default.
# This file documents the recommended PhpStorm settings for this project.

## Recommended PhpStorm Plugins
- PHP (built-in)
- Laravel (plugin marketplace: "Laravel")
- Blade (built-in via PHP plugin)
- EditorConfig (built-in since 2019.2)
- Tailwind CSS (plugin marketplace)
- .env files support (plugin marketplace)
- AI Assistant (built-in with JetBrains AI)

## PHP Interpreter
- Set PHP CLI interpreter to: Docker → Sail container
- Path: vendor/bin/sail php
- PHP version: 8.4

## JetBrains AI Instructions
When using JetBrains AI Assistant on this project, the system instructions are in:
- AGENT.md (read this first — full project context)
- ARCHITECTURE.md (system design)
- DECISIONS.md (why things were built a certain way)

Key rules for JetBrains AI:
1. All commands use `vendor/bin/sail` prefix — never bare `php` or `composer`
2. Never add employee_id FK to equipment table
3. Only write PHPUnit class-based tests
4. Run pint after PHP changes

## Run Configurations (Import Manually)
Create these in PhpStorm → Run/Debug Configurations → Shell Script:

1. "Sail Up"      → vendor/bin/sail up -d
2. "Sail Stop"    → vendor/bin/sail stop
3. "Run Tests"    → vendor/bin/sail artisan test --compact
4. "Pint"         → vendor/bin/sail bin pint --dirty --format agent
5. "Dev Server"   → vendor/bin/sail npm run dev
6. "Migrate"      → vendor/bin/sail artisan migrate
7. "Clear Cache"  → vendor/bin/sail artisan optimize:clear

## Database Connection (PhpStorm Database Tool)
- Host: 127.0.0.1
- Port: 3306 (or as configured in .env)
- Database: ieepis_db (check .env DB_DATABASE)
- User/Pass: see .env DB_USERNAME / DB_PASSWORD
