CREATE TABLE blog_posts (
  id         INTEGER  NOT NULL PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER      NULL,
  published  BOOLEAN  NOT NULL DEFAULT 0,
  content    TEXT     NOT NULL,
  published_at TIMESTAMP  NULL
)
