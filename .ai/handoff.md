# Session Handoff

## Last Updated
2026-04-27

## What Was Completed
- **Bug fixes (post-smoke-test):** DatabaseSeeder now seeds equipment per school (was empty → assignment dropdown empty); equipment Select now searches `brand/model/property_no/serial_number`; DcpDistributionSeeder uses `unassigned` (was `Distributed/Pending/Received`).
- **Identity unification (Decision 011):** Employee is canonical; User is auth-only. Link via `employees.user_id` (unique, nullable, `nullOnDelete`). User model delegates `name`/`school_id`/`division_id`/`school` through the employee relation; SchoolScope and all `$user->school_id` callsites work unchanged. UserResource form picks an Employee. CreateUser/EditUser pages link `employees.user_id` after save. New migration `2026_04_27_000002` drops `school_id`/`division`/`division_id` from users; existing `2026_04_27_000001` fixed (`nullOnDelete` + unique). `scripts/sync_employees_to_users.php` deleted.
- **Audit fixes:** SEC-1+2 (random super-admin password unless local; school-admin passwords randomized); SEC-3 (RateLimiter `auth` and `oauth` limiters in AppServiceProvider; `throttle:oauth` on Google OAuth routes); SEC-5 (canAccessPanel now also requires a role); MAINT-1 (deleted 4 drifty resource READMEs, kept only AssignmentResource); MAINT-2 (memory.md split — modules moved to `.ai/memory-modules.md`, index now 49 lines).
- Decisions 011, 012 recorded.

## Current Blockers
- User to run `vendor/bin/sail artisan migrate:fresh --seed` to apply unification + bug-fix seeder.

## Immediate Next Actions
1. `migrate:fresh --seed`. Smoke test: log in as the seeded school-admin (random password printed during seed if non-local; password is `password` if local — TODO: remove that fallback or print it locally too) → try create assignment → verify equipment dropdown is populated AND searchable by brand/property_no → return equipment.
2. UserResource: log in as super-admin → create new user → pick an unlinked employee → confirm `employees.user_id` is set after save.
3. Pick next audit batch: SCALE-1 (cache role check on request), MAINT-3 (CI/Pint setup), or ARCH-1 (begin extracting more services).

## Notes for Next Session
- `Employee` is canonical identity; `User` is auth-only. Read `$user->name` etc. — accessor handles it. Writes happen on Employee.
- Local-env super-admin password: `P@ssw0rd123`. Production: set `SEED_SUPER_ADMIN_PASSWORD` env var or capture the random one printed by the seeder.
- `AssignmentService` is the contract for assignment writes. New write paths must call it.
- DB-agnostic rule (Decision 010) in force; no driver-specific SQL.
