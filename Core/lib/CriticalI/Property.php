<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * CriticalI_Property is a class for working with user-level properties
 * defined within the repository.
 */
class CriticalI_Property {
  protected static $list = null;
  
  protected $properties;
  
  /**
   * Get the value of a property
   *
   * @param string $name The name of the property to retrieve
   * @param string $default The value to return if the property does not exist (optional)
   * @return mixed
   */
  public static function get($name, $default = null) {
    $props = self::listing();

    if (isset($props->properties[$name]))
      return $props->properties[$name];
    return $default;
  }
  
  /**
   * Test for the existence of a property
   *
   * @param string $name The name of the property to test
   * @return boolean
   */
  public static function exists($name) {
    $props = self::listing();

    return isset($props->properties[$name]);
  }

  /**
   * Return all properties as an associative array
   *
   * @return array
   */
  public static function all() {
    $props = self::listing();

    return $props->properties;
  }

  /**
   * Set the value of a property. The property is immediately saved to disk.
   *
   * @param string $name The name of the property to set
   * @param string $value The value to set it to
   */
  public static function set($name, $value) {
    $props = self::listing();

    $props->save($name, $value);
  }
  
  /**
   * Set the value of a several properties at once.
   *
   * The properties are specified as an associative array with the array
   * keys representing the property names to set and the array values
   * corresponding to the respective values to set for the properties.
   * The properties are immediately saved to disk as a batch.
   *
   * @param array $properties The collection of properties to set
   */
  public static function set_multiple($properties) {
    $props = self::listing();

    $props->batch_save($properties);
  }

  /**
   * Remove (delete) a property. The change is immediately saved to disk.
   *
   * @param string $name The name of the property to remove
   * @return string The previous value of the property (if any)
   */
  public static function remove($name) {
    $props = self::listing();

    $lastValues = $props->delete_properties(array($name));
    
    return $lastValues[$name];
  }
  
  /**
   * Remove (delete) a set of properties. The changes are immediately
   * saved to disk.
   *
   * @param array $names The list of property names to remove
   * @return array The previous values of the properties as an associative array
   */
  public static function remove_multiple($names) {
    $props = self::listing();

    return $props->delete_properties($names);
  }

  /**
   * Constructor.
   *
   * This class may not be directly instantiated.
   */
  protected function __construct() {
    $this->properties = array();
    
    $this->load();
  }
  
  /**
   * Return the shared class instance
   */
  protected static function listing() {
    if (!self::$list)
      self::$list = new CriticalI_Property();
    return self::$list;
  }

  /**
   * Load the properties from the repository
   */
  protected function load() {
    CriticalI_RepositoryLock::read_lock();
    if (file_exists("$GLOBALS[CRITICALI_ROOT]/.properties")) {
      $data = CriticalI_ConfigFile::read("$GLOBALS[CRITICALI_ROOT]/.properties");
      $this->properties = isset($data['user']) ? $data['user'] : array();
    } else {
      $this->properties = array();
    }
  }
  
  /**
   * Write a property to the repository
   */
  protected function save($key, $value) {
    $this->batch_save(array($key=>$value));
  }
  
  /**
   * Validates that the key values in a set of properties are valid
   */
  protected function validateKeys($properties) {
    foreach ($properties as $key=>$value) {
      if (strpbrk($key, '?{}|&~![()^"*') !== false)
        throw new CriticalI_UsageError("Invalid property name \"$key\". " .
          "The characters ?{}|&~![()^\"* may not be used in a property name.");
    }
  }
  
  /**
   * Write a collection of properties to the repository
   *
   * @param array $properties An associative array of properties to set
   */
  protected function batch_save($properties) {
    $this->validateKeys($properties);
    
    // start with the current set of properties in case anything has changed
    CriticalI_RepositoryLock::write_lock();
    if (file_exists("$GLOBALS[CRITICALI_ROOT]/.properties")) {
      $data = CriticalI_ConfigFile::read("$GLOBALS[CRITICALI_ROOT]/.properties");
      $this->properties = isset($data['user']) ? $data['user'] : array();
    } else {
      $data = array();
      $this->properties = array();
    }
    
    // make our updates
    $this->properties = array_merge($this->properties, $properties);
    $data['user'] = $this->properties;
    
    // write the result
    CriticalI_ConfigFile::write("$GLOBALS[CRITICALI_ROOT]/.properties", $data);
  }
  
  /**
   * Remove a collection of properties from the repository
   *
   * @param array $properties A list of property names to remove
   * @return array The previous values of the properties as an associative array
   */
  protected function delete_properties($properties) {
    $values = array();
    
    // start with the current set of properties in case anything has changed
    CriticalI_RepositoryLock::write_lock();
    if (file_exists("$GLOBALS[CRITICALI_ROOT]/.properties")) {
      $data = CriticalI_ConfigFile::read("$GLOBALS[CRITICALI_ROOT]/.properties");
      $this->properties = isset($data['user']) ? $data['user'] : array();
    } else {
      $data = array();
      $this->properties = array();
    }
    
    // make our updates
    foreach ($properties as $name) {
      if (isset($this->properties[$name])) {
        $values[$name] = $this->properties[$name];
        unset($this->properties[$name]);
      } else {
        $values[$name] = null;
      }
    }
    
    $data['user'] = $this->properties;
    
    // write the result
    CriticalI_ConfigFile::write("$GLOBALS[CRITICALI_ROOT]/.properties", $data);
    
    return $values;
  }

}

?>