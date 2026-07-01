-- Laravel database sessions table for PostgreSQL
-- Run this in the same database as your app (DB_DATABASE) and schema (default: public).
--
-- Example: psql -U your_user -d your_database -f database/sessions-table.sql
-- Or in psql: \i database/sessions-table.sql

-- Drop existing table only if you want to recreate it (will lose existing sessions)
-- DROP TABLE IF EXISTS sessions;

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) NOT NULL PRIMARY KEY,
    user_id BIGINT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL
);

CREATE INDEX IF NOT EXISTS sessions_user_id_index ON sessions (user_id);
CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON sessions (last_activity);

-- Verify (optional): SELECT * FROM sessions LIMIT 1;
