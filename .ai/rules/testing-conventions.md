# Testing Conventions

- Use Pest feature tests for auth, tenancy isolation, permissions, and financial year behavior.
- Use `CreatesBusinessContext` trait helpers in Feature tests.
- Seed permissions and roles via `PermissionSeeder` and `RoleSeeder` when testing authorization.
- Every journal must balance: `SUM(debits) = SUM(credits)` (Phase 1+).
