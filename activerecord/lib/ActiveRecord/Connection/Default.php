<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The default implmentation of ActiveRecordConnection based on MySQL.
 *
 * @copyright Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
 */

/**
 * Default implementation of ActiveRecord_Connection
 */
class ActiveRecord_Connection_Default extends ActiveRecord_Connection {
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
   * Accepts a string or array containing an SQL statement or
   * fragment an performs parameter substitution.  When a string is
   * provided, it is returned verbatim, however, when an array is
   * provide, is is assumed that the first item in the array is the
   * SQL statement (or fragment) containing parameter placeholders (in
   * the form of question marks) and that all subsequent items in the
   * array are ordered parameters to be used for substituion.
   *
   * For example, passing in the following:
   * <code>
   *   sanitizeSQL(array("status=? AND last_name LIKE ?", 'Active', '%Smith%'))
   * </code>
   * Would return this string:
   * <code>
   *   "status='Active' AND last_name LIKE '%Smith%'"
   * </code>
   *
   * Of course, this is actually more useful when passing in variables
   * with unknown values as the parameters instead of literals, since
   * sanitizeSQL handles any necessary escaping of the values.
   *
   * @param mixed  $sql  The string or array to sanitize
   *
   * @return string
   */
  public function sanitizeSQL($sql) {
    if (!is_array($sql))
      return $sql;

    return preg_replace('/\?/e', '$this->quoteBoundValue(array_shift($sql))', array_shift($sql));
  }

  /**
   * Quote a value for use by sanitizeSQL
   *
   * @param mixed $value  The value to quote
   *
   * @return string
   */
  protected function quoteBoundValue($value) {
    return is_null($value) ? 'NULL' : $this->quote($value);
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
      return "$sql LIMIT " . ($offset-1) . ($limit > -1 ? ",$limit" : ",18446744073709551615"); // yes, that is what MySQL suggests for "no" limit with an offset
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
    $stmt = $this->query("DESCRIBE $table");
    $cols = array();

    while ($row = $stmt->fetch()) {
      $cols[] = new ActiveRecord_Column($row['Field'], $row['Default'], $row['Type'], ($row['Null'] == 'YES'));
    }
    $stmt->closeCursor();

    return $cols;
  }


  /**
   * Return the sequence name that would be used to generated the
   * next value for the named key on the given table.  Even if this
   * connection does not support sequences or the resulting sequence
   * does not exist, the name of what that sequence <i>would</i> be is
   * returned.
   *
   * @param string $table  The name of the table to generate the sequence name for
   * @param string $key    The name of the key on that table to generate the name for
   *
   * @return string
   */
  public function defaultSequenceName($table, $key) {
    return "${table}_${key}";
  }

  /**
   * Returns an array of table names in the database
   *
   * @return array
   */
  public function tables() {
    $tables = array();

    $stmt = $this->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
      $tables[] = $row[0];
    }
    $stmt->closeCursor();

    return $tables;
  }

  /**
   * Used to test whether this connection uses sequences to generate
   * primary keys for the given table.  If a new primary key value
   * should be fetched prior to inserting a new record into the named
   * table, true is returned.  Otherwise false is returned, and the
   * caller can assume it is safe to invoke lastInsertId following the
   * execution of the insert statement.
   *
   * @param string $table  The name of the table to test
   *
   * @return bool
   */
  public function prefetchPrimaryKey($table) {
    return FALSE;
  }

  /**
   * Returns the next value from the named sequence.  This function
   * throws ActiveRecord_SequencesNotSupportedError if the connection
   * does not support sequences.
   *
   * @param string $name  The name of the sequence
   *
   * @return mixed
   */
  public function nextSequenceValue($name) {
    throw new ActiveRecord_SequencesNotSupportedError();
  }
}

?>
