# Session Handoff

## Last Updated
2026-04-28 (PM session)

## What Was Completed
- **AI tool cleanup** (AM): removed `.aider.conf.yml`, `.windsurfrules`, `.codeiumignore`, `.jetbrains/`; trimmed `AGENTS.md` §6.
- **Filament side-nav refactor** (AM): 6 groups; 11 resources renumbered; `MaintenanceLogResource` adopted into ICT Inventory.
- **Equipment shared-document feature** (AM): `App\Enums\DocumentType`; `Equipment::sharedDocument()`/`hasSharedDocument()`; paired view/attach actions; `EquipmentSharedDocumentTest` (5 cases) green.
- **Field-Ops PWA plan approved** (PM): Nuxt 3 in `pwa/` + Laravel REST API. Recorded as Decision 013. Plan at `~/.claude/plans/i-am-planning-to-zany-reddy.md`.
- **Local-dev-only stance** (PM): Decision 014 supersedes 006 — no more Sail/Docker for dev. `.ai/memory.md` Stack + Constraints updated; auto-memory `feedback_no_sail.md` added.

## Current Blockers
- None.

## Immediate Next Actions
1. Run `php artisan migrate:fresh --seed` (no Sail) to confirm the unification work from prior session still applies cleanly. Smoke-test login + assignment-create.
2. Start PWA M1: `composer require laravel/sanctum intervention/image-laravel`, then scaffold `routes/api.php` + `app/Http/Controllers/Api/AuthController.php`. Plan file has the full M1 spec.
3. Run `php artisan storage:link` once locally (entrypoint.sh change only covers fresh containers, which we no longer use).

## Notes for Next Session
- **No Sail/Docker** — all commands run directly on host (Decision 014).
- PWA plan is in `~/.claude/plans/i-am-planning-to-zany-reddy.md` — milestones M1–M5, ~6 weeks. M1+M2 alone (~2.5w) ships an online-only PWA covering most field needs.
- AssignmentService remains the sole sanctioned write path. PWA API controllers must call it; never write to `equipment_assignments` directly.
