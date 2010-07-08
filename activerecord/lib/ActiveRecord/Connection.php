<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Database connections extended for use with ActiveRecord.
 *
 * @package activerecord
 */

/**
 * Indicates an attempt to use a sequence on a connection that does
 * not support them.
 */
class ActiveRecord_SequencesNotSupportedError extends Exception {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct("Sequences are not supported by this connection.");
  }
}

/**
 * ActiveRecord_Connection extends PDO to provide some additional
 * functionality required by ActiveRecord that may vary by database.
 * Per-database implementations may be provided by extending
 * ActiveRecord_Connection or ActiveRecord_Connection_Default.
 * Extensions are located by naming convention.  A database-specific
 * extension is expected to have the class name
 * ActiveRecord_Connection_<Driver> where <Driver> is the name of the
 * driver specified in the connection string with an initial capital
 * letter (e.g. ActiveRecord_Connection_Oci for an OCI extension).  If
 * no database-specific extension is found,
 * ActiveRecord_Connection_Default is used.
 */
abstract class ActiveRecord_Connection extends PDO {
  /**
   * Constructor
   *
   * Do not call this method directly.  Call create instead.
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
   * Factory-type method for creating a new ActiveRecord_Connection
   * (this must be used instead of constructing a new instance).
   *
   * @param string $dsn      The data source name
   * @param string $username The user name for the DSN string (optional)
   * @param string $password The password for the DSN string (optional)
   * @param array  $options  An array of options as allowed by PDO (optional)
   */
  public static function create($dsn, $username = NULL, $password = NULL, $options = NULL) {
    $pos = strpos($dsn, ':');
    $driver = 'Default';
    if ($pos)
      $driver = ucfirst(substr($dsn, 0, $pos));

    $class = "ActiveRecord_Connection_${driver}";
    if (!class_exists($class))
      $class = "ActiveRecord_Connection_Default";

    return new $class($dsn, $username, $password, $options);
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
  abstract public function sanitizeSQL($sql);

  /**
   * Append a LIMIT clause to a SQL statement
   *
   * @param int $limit  The maximum number of rows to return (or -1 for no maximum)
   * @param int $offset The first row number to return in the results (e.g. 1 to return all rows, 2 to skip the first row)
   *
   * @return string
   */
  abstract public function addLimit($sql, $limit = -1, $offset = 1);

  /**
   * Return an array of ActiveRecordColumn objects for the named
   * table.
   *
   * @param string $table  The name of the table to return the columns for
   *
   * @return array
   */
  abstract public function columns($table);


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
  abstract public function defaultSequenceName($table, $key);

  /**
   * Returns an array of table names in the database
   *
   * @return array
   */
  abstract public function tables();

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
  abstract public function prefetchPrimaryKey($table);

  /**
   * Returns the next value from the named sequence.  This function
   * throws ActiveRecord_SequencesNotSupportedError if the connection
   * does not support sequences.
   *
   * @param string $name  The name of the sequence
   *
   * @return mixed
   */
  abstract public function nextSequenceValue($name);

  /**
   * Runs the select query provided in the $sql parameter, then
   * fetches all rows into an array.  Each individual row is fetched
   * using the mode parameter provided.
   *
   * For example, if you have a table named users containing the following:
   * <code>
   *   +----+------------+-----------+------------------+
   *   | id | first_name | last_name | email            |
   *   +----+------------+-----------+------------------+
   *   |  1 | John       | Doe       | jdoe@google.com  |
   *   |  2 | John       | Smith     | jsmith@yahoo.com |
   *   +----+------------+-----------+------------------+
   * </code>
   * and you run the following:
   * <code>
   *   $connection->selectAll("SELECT * FROM users", PDO::FETCH_ASSOC);
   * </code>
   * It will return a data structure like this:
   * <code>
   *   array(
   *     array('id'=>1, 'first_name'=>'John', 'last_name'=>'Doe',   'email'=>'jdoe@google.com'),
   *     array('id'=>2, 'first_name'=>'John', 'last_name'=>'Smith', 'email'=>'jsmith@yahoo.com')
   *   )
   * </code>
   *
   * Empty data sets return an empty array.
   *
   * @param string $sql  The select statement to run
   * @param int    $mode The mode to use for fetching rows
   *
   * @return array
   */
  public function selectAll($sql, $mode = PDO::FETCH_BOTH) {
    $stmt = $this->query($sql);
    $results = $stmt->fetchAll($mode);
    $stmt->closeCursor();

    return $results;
  }

  /**
   * This is a convenience method for running select queries which
   * return a single row containing only a single column.  This method
   * runs the provided query and returns the value from the first
   * column of the first row of the results.  Any other values in the
   * result set are discarded.  Empty result sets return NULL.
   *
   * @param string $sql  The select statement to run
   *
   * @return mixed
   */
  public function selectValue($sql) {
    $stmt = $this->query($sql);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $stmt->closeCursor();

    return $row ? $row[0] : NULL;
  }
}

?>
