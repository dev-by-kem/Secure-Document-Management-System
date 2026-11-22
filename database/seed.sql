-- ============================================================
-- SDMS — database/seed.sql — starter data.
-- Import RIGHT AFTER schema.sql (it expects the tables to exist).
--
-- ⚠ Run it once on a freshly imported schema. Importing it twice will
--   fail on unique keys — that failure is by design and protects your data.
--   To start over: re-import schema.sql, then this file.
--
-- Demo accounts (for team testing — CHANGE THESE PASSWORDS after first login):
--   admin  / Admin@123    → full control
--   staff  / Staff@123    → uploads and organizes documents
--   viewer / Viewer@123   → view + download only
--
-- Only bcrypt hashes are stored (SR-1) — generated with:
--   php -r "echo password_hash('Admin@123', PASSWORD_DEFAULT);"
-- ============================================================

-- No `USE sdms;` statement: free hosts (InfinityFree) block database-level commands.
-- In phpMyAdmin, click YOUR database in the left sidebar first, then Import this file.

-- The three roles from the manual (§D)
INSERT INTO roles (role_name, description) VALUES
('admin',  'Administrator - full control of users, documents, and settings'),
('staff',  'Employee - uploads, organizes, and updates documents'),
('viewer', 'Viewer - can only view and download authorized documents');

-- Demo accounts, one per role
INSERT INTO users (username, email, password_hash, full_name, role_id) VALUES
('admin',  'admin@sdms.local',  '$2y$10$0gKEkizajYuVjGFKSf2m5eZRarJCjkvMYWzs2Bij96WPTGW7ZcK1S', 'System Administrator', 1),
('staff',  'staff@sdms.local',  '$2y$10$ca9VghP9Byg93CVpO.8ovO0Fod4Kz.0kP1VTFhDSUqftIdXdrUlr6', 'Maria Santos',         2),
('viewer', 'viewer@sdms.local', '$2y$10$X9FNluOHpLsz4UqRJT.BC.7y3jz.LhN7u1BcrDcPVxGwLEJBqd1k6', 'Juan Dela Cruz',       3);

-- Sample categories; requires_approval = 1 means uploads go to Pending first
INSERT INTO document_categories (category_name, description, requires_approval) VALUES
('Assignments',       'Class assignments and homework',      0),
('Research Papers',   'Studies and formal research',         1),
('Project Documents', 'Group project files',                 1),
('Reports',           'Reports and summaries',               0),
('Others',            'Uncategorized school materials',      0);
