<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * An ActiveRecord_Connection for SQLite.
 *
 * @copyright Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
 */

/**
 * ActiveRecord_Connection for SQLite
 */
class ActiveRecord_Connection_Sqlite extends ActiveRecord_Connection_Default {
  /**
   * Constructor
   *
   * Do not call this method directly.  Call ActiveRecord_Connection::create instead.
   *
   * @param string $dsn      The data source name
   * @param string $username The user name for the DSN string (optional)
   * @param string $password The password for the DSN string (optional)
   * @param array  $options  An array of options as allowed by PDO (optional)
   */
  public function __construct($dsn, $username = NULL, $password = NULL, $options = NULL) {
    parent::__construct($dsn, $username, $password, $options);
  }

  /**
   * Append a LIMIT clause to a SQL statement
   *
   * @param int $limit  The maximum number of rows to return (or -1 for no maximum)
   * @param int $offset The first row number to return in the results (e.g. 1 to return all rows, 2 to skip the first row)
   *
   * @return string
   */
  public function addLimit($sql, $limit = -1, $offset = 1) {
    if ($offset > 1)
      return "$sql LIMIT $limit OFFSET " . ($offset-1);
    elseif ($limit > 0)
      return "$sql LIMIT $limit";
    else
      return $sql;
  }

  /**
   * Return an array of ActiveRecord_Column objects for the named
   * table.
   *
   * @param string $table  The name of the table to return the columns for
   *
   * @return array
   */
  public function columns($table) {
    $stmt = $this->query("PRAGMA table_info($table)");
    $cols = array();

    while ($row = $stmt->fetch()) {
      $cols[] = new ActiveRecord_Column($row['name'], $row['dflt_value'], $row['type'], ($row['notnull'] ? false : true));
    }
    $stmt->closeCursor();

    return $cols;
  }

  /**
   * Returns an array of table names in the database
   *
   * @return array
   */
  public function tables() {
    $tables = array();

    $stmt = $this->query("SELECT name FROM sqlite_master WHERE type='table'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
      $tables[] = $row[0];
    }
    $stmt->closeCursor();

    return $tables;
  }

}

?>
