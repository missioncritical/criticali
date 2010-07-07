<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Column meta-data for ActiveRecord.
 *
 * @copyright Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
 */


/**
 * A class containing information about a table column.
 */
class ActiveRecord_Column {
  /**
   * The column name
   */
  protected $name;
  /**
   * The column data type (as reported by the DB)
   */
  protected $sql_type;
  /**
   * The simplified column data type
   */
  protected $type;
  /**
   * The size limit of the column
   */
  protected $limit;
  /**
   * The scale of the columm
   */
  protected $scale;
  /**
   * The precision of the column
   */
  protected $precision;
  /** 
   * Whether or not the column may contain nulls
   */
  protected $null_allowed;
  /**
   * The default value
   */
  protected $default;
  /**
   * A flag indicating whether or not this is the primary key for the
   * table
   */
  protected $primary;

  /**
   * Constructor
   *
   * @param string $name         The column name
   * @param string $default      The default value as a string
   * @param string $sql_type     The column data type
   * @param bool   $null_allowed Whether or not nulls are allowed
   */
  public function __construct($name, $default, $sql_type = NULL, $null_allowed = true) {
    $this->name         = $name;
    $this->sql_type     = $sql_type;
    $this->null_allowed = $null_allowed;
    $this->limit        = $this->extract_limit($sql_type);
    $this->precision    = $this->extract_precision($sql_type);
    $this->scale        = $this->extract_scale($sql_type);
    $this->type         = $this->simplified_type($sql_type);
    $this->default      = $this->type_cast($default, $this->null_allowed);
  }

  /**
   * Return the column name
   * @return string
   */
  public function name() {
    return $this->name;
  }

  /**
   * Return the column data type (as reported by the DB)
   * @return string
   */
  public function sql_type() {
    return $this->sql_type;
  }

  /**
   * Return the column data type as a normalized name.  May be one of
   * integer, float, decimal, datetime, date, timestamp, time, text,
   * string, binary, or boolean.
   *
   * @return string
   */
  public function type() {
    return $this->type;
  }

  /**
   * Return the size limit of the column
   * @return int
   */
  public function limit() {
    return $this->limit;
  }

  /**
   * Return the scale of the columm
   * @return int
   */
  public function scale() {
    return $this->scale;
  }

  /**
   * Return the precision of the column
   * @return int
   */
  public function precision() {
    return $this->precision;
  }

  /**
   * Returns true if the column may contain nulls, false otherwise.
   * @return bool
   */
  public function null_allowed() {
    return $this->null_allowed;
  }

  /**
   * Return the default value for the column
   * @return mixed
   */
  public function default_value() {
    return $this->default;
  }

  /**
   * Returns true if this column is the tables primary key, false otherwise.
   * @return bool
   */
  public function primary() {
    return $this->primary;
  }

  /**
   * Set whether or not this column is the tables primary key.
   *
   * @param bool $flag  The new value for the primary indicator
   * @return bool
   */
  public function set_primary($flag) {
    $this->primary = $flag;
  }

  /**
   * Return true if the data type is a text type
   *
   * @return bool
   */
  public function is_text() {
    $type = $this->type();
    return (($type == 'string') || ($type == 'text'));
  }

  /**
   * Return true if the data type is a numeric type
   *
   * @return bool
   */
  public function is_number() {
    $type = $this->type();
    return (($type == 'float') || ($type == 'integer') || ($type == 'decimal'));
  }

  /**
   * Cast a string or raw database value to the correct type for this column
   *
   * @param mixed $value     The value to cast
   * @param bool  $allowNull Optional flag indicating whether or not NULL is an acceptable value (default is true)
   *
   * @return mixed  The value as the correct type
   */
  public function type_cast($value, $allowNull = true) {
    // null is always null, except when it's not
    if (is_null($value) && $allowNull)
      return $value;

    switch ($this->type) {
    case 'string':
    case 'text':
      return strval($value);
    case 'integer':
      return intval($value);
    case 'float':
      return floatval($value);
    case 'decimal':
      return strval($value); // yes, a string
    case 'datetime':
    case 'timestamp':
    case 'time':
      //return new Time($value);
      return $value;
    case 'date':
      //return new Date($value);
      return $value;
    case 'binary':
      return strval($value);
    case 'boolean':
      return ($value ? true : false);
    default:
      return $value;
    }
  }

  /**
   * The opposite of type_cast.  Accepts a data type appropriate for
   * this column and converts it (if required) back to a value
   * appropriate for use in a SQL statement.
   *
   * @param mixed $value  The value to cast
   *
   * @return mixed  The value as the SQL-ready type
   */
  public function reverse_type_cast($value) {
    // null is always null
    if (is_null($value))
      return $value;

    switch ($this->type) {
    case 'string':
    case 'text':
      return strval($value);
    case 'integer':
      return $value === '' ? NULL : intval($value);
    case 'float':
      return $value === '' ? NULL : floatval($value);
    case 'decimal':
      return strval($value) === '' ? NULL : strval($value); // yes, a string
    case 'datetime':
    case 'timestamp':
    case 'time':
      return $value;
    case 'date':
      return $value;
    case 'binary':
      return strval($value);
    case 'boolean':
      return ($value ? 1 : 0);
    default:
      return $value;
    }
  }

  /**
   * Retrieve the size limit from a raw SQL type
   *
   * @param string $sql_type  The SQL type to use
   *
   * @return int
   */
  protected function extract_limit($sql_type) {
    $matches = array();
    if (preg_match('/\((.*)\)/', $sql_type, $matches))
      return intval($matches[1]);
    else
      return NULL;
  }

  /**
   * Retrieve the precision (for decimal types) from a raw SQL type
   *
   * @param string $sql_type  The SQL type to use
   *
   * @return int
   */
  protected function extract_precision($sql_type) {
    $matches = array();
    if (preg_match('/(?:numeric|decimal|number)\((\d+)(?:,(\d+))?\)/i', $sql_type, $matches))
      return intval($matches[1]);
    else
      return NULL;
  }

  /**
   * Retrieve the scale (for decimal types) from a raw SQL type
   *
   * @param string $sql_type  The SQL type to use
   *
   * @return int
   */
  protected function extract_scale($sql_type) {
    $matches = array();
    if (preg_match('/(?:numeric|decimal|number)\((\d+)\)/i', $sql_type, $matches))
      return 0;
    elseif (preg_match('/(?:numeric|decimal|number)\((\d+),(\d+)\)/i', $sql_type, $matches))
      return intval($matches[2]);
    else
      return NULL;
  }

  /**
   * Return a normalized type name from a raw SQL type
   *
   * @param string $sql_type  The SQL type to use
   *
   * @return string
   */
  protected function simplified_type($sql_type) {
    // special case for tinyint(1)
    if (strpos(strtolower($sql_type), 'tinyint(1)') !== FALSE)
      return 'boolean';

    $matches = array();
    if (!preg_match('/(int|float|double|decimal|numeric|number|datetime|timestamp|time|date|clob|text|blob|binary|char|string|boolean|enum)/i', $sql_type, $matches)) {
        return $sql_type;
    }

    $match = strtolower($matches[1]);

    // special case for decimal(0): same as int
    if (($match == 'decimal') || ($match == 'numeric') || ($match == 'number'))
      return $this->extract_scale($sql_type) == 0 ? 'integer' : 'decimal';

    $substitutions = array('int'=>'integer',
                           'double'=>'float',
                           'clob'=>'text',
                           'blob'=>'binary',
                           'char'=>'string',
                           'enum'=>'string');

    return isset($substitutions[$match]) ? $substitutions[$match] : $match;
  }

}

?>
