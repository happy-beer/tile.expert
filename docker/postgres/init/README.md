# PostgreSQL init scripts

This directory is mounted into `/docker-entrypoint-initdb.d` in the `db` container.
Scripts are executed only on first database initialization (fresh volume).

- `01_schema.sql` contains a PostgreSQL-compatible conversion of the provided MySQL dump.

Important:
- Existing volumes are not re-initialized automatically.
- The source dump contains schema only (no INSERT statements), so tables are created empty.
