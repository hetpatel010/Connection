# Database Conventions

- Cloud production uses PostgreSQL; desktop/offline uses SQLite via `config/platform.php`.
- Human-readable document numbers are separate from UUID primary keys.
- Use soft deletes (`deleted_at`) for syncable entities; hard deletes are for non-synced data only.
- Money will be stored as integer minor units (paise) in future transaction tables.
