CREATE TABLE profiles (
  id         INTEGER  NOT NULL PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER      NULL,
  first_name TEXT     NOT NULL,
  last_name  TEXT     NOT NULL,
  email      TEXT     NOT NULL
)
