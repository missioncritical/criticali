<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * A data type identifies a group of fields with similar behavior
 */
abstract class Form_DataType {
  
  private static $instances;
  
  protected $name = null;
  
  /**
   * Format a field's value for display in a control
   *
   * @param mixed $value The value to format
   * @return mixed
   */
  public function format_for_control($value) {
    // default is no change
    return $value;
  }
  
  /**
   * The inverse of format_for_control, this takes the value posted from
   * a control converts it to the field's native type or format
   *
   * @param mixed $value The value to form
   * @return mixed
   */
  public function parse_control_value($value) {
    // default is no change
    return $value;
  }
  
  /**
   * Return the name of the default control type to use for this data type
   * @return string
   */
  public function default_control() {
    return 'textfield';
  }
  
  /**
   * Return the name of this data type
   */
  public function name() {
    if (!$this->name) {
      $this->name = get_class($this);
      
      $try = array('DataType_', 'Form_DataType_');
      foreach ($try as $ns) {
        if (substr($this->name, 0, strlen($ns)) == $ns) {
          $this->name = substr($this->name, strlen($ns));
          break;
        }
      }
      
      $this->name = Support_Inflector::underscore(str_replace('_', '/', $this->name));
    }
    
    return $this->name;
  }

  /**
   * Return the named data type instance. If a data type instance is
   * passed in, it is returned as-is.
   *
   * @param string $name The name of the data type to return
   * @return Form_DataType
   */
  public static function instance($name) {
    if ($name instanceof Form_DataType)
      return $name;
    
    if (!self::$instances)
      self::$instances = array();
    
    if (!isset(self::$instances[$name])) {

      $class_name = str_replace('/', '_', Support_Inflector::camelize($name));
      
      $try = array('DataType', 'Form_DataType');
      foreach ($try as $prefix) {
        $klass = $prefix . '_' . $class_name;

        if (class_exists($klass)) {
          self::$instances[$name] = new $klass();
          break;
        }
      }
    }
    
    return @self::$instances[$name];
  }

}
