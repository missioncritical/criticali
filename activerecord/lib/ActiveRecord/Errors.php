<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Errors collection for ActiveRecord.
 *
 * @package activerecord
 */


/**
 * Errors collection class for ActiveRecord.  Every ActiveRecord
 * instance has a corresponding ActiveRecord_Errors instance, even if
 * no errors exist for the object.
 */
class ActiveRecord_Errors {
  /**
   * The object we belong to
   */
  protected $base;

  /**
   * Error messages keyed by attribute
   */
  protected $errors;

  /**
   * Constructor
   *
   * @param ActiveRecord_Base $base  The base object we belong to
   */
  public function __construct($base) {
    $this->base   = $base;
    $this->errors = array();
  }

  /**
   * Add an error message that pertains to the base object as opposed
   * to a particular attribute on the object.
   *
   * @param string $msg  The error message.
   */
  public function add_to_base($msg) {
    $this->add('.base', $msg);
  }

  /**
   * Add an error message to the named attribute.  Multiple error
   * message may be associated with a single attribute.  If no message
   * is provided, the message "is invalid" is used.
   *
   * @param string $attr  The attribute name
   * @param string $msg   The message fragment to use
   */
  public function add($attr, $msg = false) {
    $msg = $msg ? $msg : "is invalid";
    if (!isset($this->errors[$attr])) $this->errors[$attr] = array();
    $this->errors[$attr][] = $msg;
  }

  /**
   * Conditionally adds an error message to the list of supplied
   * attributes when the attribute values are empty (determined by the
   * PHP function empty()).  If no error message is provided, the
   * message "is required" is used.
   *
   * @param mixed  $attr  The attibute name or array of attribute names
   * @param string $msg   The message fragment to use
   */
  public function add_on_empty($attr, $msg = false) {
    $attr = is_array($attr) ? $attr : array($attr);
    $msg = $msg ? $msg : "is required";

    foreach ($attr as $name) {
      if ($this->base->has_attribute($name))
        if (empty($this->base->$name))
          $this->add($name, $msg);
    }
  }

  /**
   * Conditionally adds an error message to the list of supplied
   * attributes when the string length of the attribute values are
   * outside a given range.  If no error messages are provided, the
   * messages "is too short (min $lowerBound)" / "is too long (max
   * $upperBound)" are used.
   *
   * @param mixed  $attr       The attibute name or array of attribute names
   * @param int    $lowerBound The minimum required characters
   * @param int    $upperBound The maximum required characters
   * @param string $msgShort   The message fragment to use for short attributes
   * @param string $msgLong    The message fragment to use for long attributes
   */
  public function add_on_boundary_breaking($attr, $lowerBound, $upperBound, $msgShort = false, $msgLong = false) {
    $attr = is_array($attr) ? $attr : array($attr);
    $msgShort = $msgShort ? $msgShort : "is too short (min $lowerBound)";
    $msgLong = $msgLong ? $msgLong : "is too long (max $upperBound)";

    foreach ($attr as $name) {
      if ($this->base->has_attribute($name)) {
        $len = strlen($this->base->$name);
        if ($len < $lowerBound)
          $this->add($name, $msgShort);
        if ($len > $upperBound)
          $this->add($name, $msgLong);
      }
    }
  }

  /**
   * Returns true if the named attribute has errors
   *
   * @param string $attr  The attribute name to test
   */
  public function is_invalid($attr) {
    return (isset($this->errors[$attr]) && $this->errors[$attr]);
  }

  /**
   * Return the list of errors associated with the named attribute.
   * Note, this function always returns an array whether zero, one, or
   * more errors exists.
   *
   * @param string $attr  The attribute to return errors for
   *
   * @return array
   */
  public function on($attr) {
    return isset($this->errors[$attr]) ? $this->errors[$attr] : array();
  }

  /**
   * Returns any errors associated with the base class as opposed to
   * a sepecific attribute.
   *
   * @return array
   */
  public function on_base() {
    return $this->on('.base');
  }

  /**
   * Return the collection of all full error messages.
   *
   * @return array
   */
  public function full_messages() {
    $msgs = array();

    foreach ($this->errors as $attr => $errs) {
      $prefix = ($attr == '.base') ? '' : Support_Inflector::humanize($attr) . ' ';
      foreach ($errs as $err) {
        $msgs[] = $prefix . $err;
      }
    }

    return $msgs;
  }

  /**
   * Returns true if the list of errors is empty
   *
   * @return bool
   */
  public function is_empty() {
    return (count($this->errors) == 0);
  }

  /**
   * Clear all errors from the collection.
   */
  public function clear() {
    $this->errors = array();
  }

  /**
   * Return the total number of errors in the collection (same as count(full_messages())).
   *
   * @return int
   */
  public function size() {
    $cnt = 0;

    foreach ($this->errors as $errs) {
      $cnt += count($errs);
    }

    return $cnt;
  }

}

?>
