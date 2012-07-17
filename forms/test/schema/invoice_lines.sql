CREATE TABLE invoice_lines (
  id             INTEGER       NOT NULL PRIMARY KEY AUTOINCREMENT,
  invoice_id     INTEGER       NULL,
  description    TEXT          NOT NULL,
  amount         DECIMAL(12,1) NOT NULL)
