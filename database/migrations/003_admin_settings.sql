CREATE TABLE IF NOT EXISTS administrators (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 email TEXT NOT NULL UNIQUE,
 password_hash TEXT NOT NULL,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
INSERT OR IGNORE INTO settings(name,value) VALUES ('site_title','Ecommerce');
INSERT OR IGNORE INTO settings(name,value) VALUES ('site_description','');
INSERT OR IGNORE INTO settings(name,value) VALUES ('site_email','');
INSERT OR IGNORE INTO settings(name,value) VALUES ('site_timezone','UTC');
