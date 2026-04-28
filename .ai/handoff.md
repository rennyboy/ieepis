# Session Handoff

## Last Updated
2026-04-28

## What Was Completed
- **AI tool cleanup:** removed `.aider.conf.yml`, `.windsurfrules`, `.codeiumignore`, `.jetbrains/` (user does not use those tools); trimmed `AGENTS.md` §6 cross-tool table.
- **Filament side-nav refactor:** rewrote `navigationGroups()` to 6 groups — Overview, ICT Inventory, People, Organization, Documents & Tickets, Administration (collapsed). Renumbered 11 resources; adopted orphan `MaintenanceLogResource` into ICT Inventory.
- **Equipment shared-document feature:** new `App\Enums\DocumentType` (string-backed); `Document::$casts['document_type']` casts to it; `Equipment::sharedDocument()` + `hasSharedDocument()`; paired view/attach actions on the table row + ViewEquipment/EditEquipment headers; hint added to DocumentsRelationManager. `php artisan storage:link --force` added to `docker/entrypoint.sh`.
- **Tests:** new `EquipmentSharedDocumentTest` (5 cases) — full suite green (6/6, 12 assertions).
- Yesterday's completed items rotated to `SecondBrain/Daily Notes/2026-04-27.md`.

## Current Blockers
- None.

## Immediate Next Actions
1. Run `vendor/bin/sail artisan storage:link` once on the existing local env (entrypoint change only covers fresh containers).
2. Smoke test the new equipment row action: list view shows "Attach Document" (warning) when none, "Document" (success) when one exists; same in the View/Edit page header.
3. Pick next batch from yesterday's audit: SCALE-1 (cache role check on request), MAINT-3 (CI/Pint setup), or ARCH-1 (extract more services).

## Notes for Next Session
- Don't replace `'PAR' => 'PAR'` arrays globally — `AssignmentResource` and `EquipmentResource` `supporting_doc_type*` Selects use intentional subsets, not the `DocumentType` enum.
- "Shared document" = most-recent doc by `document_date DESC, created_at DESC`. Soft warning only; no save-block.
