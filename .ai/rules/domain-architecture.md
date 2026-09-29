# Domain Architecture

- Business logic lives in `app/Domain/` and `app/Application/Services/`. Never in Livewire components or Blade views.
- All syncable entities use UUID v7 primary keys via `HasUuid`.
- All business-owned records include `business_id` and use `BelongsToBusiness` global scope.
- Never trust client-supplied `business_id`; resolve via `BusinessContext` from authenticated session.
- Financial year locks are enforced in domain services (`FinancialYearService`), not UI-only.
- Confirmed financial transactions are reversed, not destructively deleted (future phases).
- `User` has a `password => 'hashed'` cast, so passwords are hashed automatically. NEVER call `Hash::make` on a password before assigning it to a User (factory, seeder, register, reset, update, member invite) — that double-hashes it and breaks login regardless of the stored password.
