CREATE TABLE documents (
  id         INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  title      TEXT    NOT NULL,
  author     TEXT    NOT NULL,
  retrievals INTEGER NOT NULL DEFAULT 0)
