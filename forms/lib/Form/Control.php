<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * A control outputs HTML for a form field (or other object)
 */
abstract class Form_Control {
  
  private static $instances;

  protected $name;
  
  /**
   * Output the control HTML for a form object
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return HTML for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @return string
   */
  abstract public function html($form, $object, $name_path, $value);
  
  /**
   * Return the named control instance. If a control instance is passed
   * in, it is returned as-is.
   *
   * @param string $name The name of the control to return
   * @return Form_Control
   */
  public static function instance($name) {
    if ($name instanceof Form_Control)
      return $name;
    
    if (!self::$instances)
      self::$instances = array();
    
    if (!isset(self::$instances[$name])) {

      $class_name = str_replace('/', '_', Support_Inflector::camelize($name));
      
      $try = array('Control', 'Form_Control');
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

  /**
   * Return the id for a a control with the given field name and path
   *
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param string $name The field name of the control
   */
  public function control_id($name_path, $name) {
    $name_path[] = $name;
    return implode('_', $name_path);
  }

  /**
   * Return the name attribute value for a a control with the given field
   * name and path
   *
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param string $name The field name of the control
   */
  public function control_name($name_path, $name) {
    $name_path[] = $name;
    
    $first = array_shift($name_path);
    $more = implode('][', $name_path);
    
    if ($more)
      return $first . '[' . $more . ']';
    else
      return $first;
  }
  
  /**
   * Return the name of this control
   */
  public function name() {
    if (!$this->name) {
      $this->name = get_class($this);
      
      $try = array('Control_', 'Form_Control_');
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

}
