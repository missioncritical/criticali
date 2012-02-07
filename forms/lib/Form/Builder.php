<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * A builder outputs HTML for an entire form
 */
abstract class Form_Builder {
  
  private static $instances;
  
  protected $form;
  
  /**
   * Constructor
   */
  public function __construct($form) {
    $this->form = $form;
  }
  
  /**
   * Return the form this builder is building for
   * @return Form_Base
   */
  public function form() {
    return $this->form;
  }
  
  /**
   * Return the fields for the form
   *
   * @param string $forName If provided, return only the fields for the named item in the form
   * @return string
   */
  public function fields($forName = null) {
    if ($forName)
      $what = array($this->form()->object($forName));
    else
      $what = $this->form()->objects();
    
    $html = array();
    foreach ($what as $item) {
      $html[] = $this->html($item, array());
    }
    
    return $this->join_form_components($html);
  }
  
  /**
   * Join multiple fields/components together for output in a rendered form
   *
   * @param array $fields The HTML for the various fields to join
   * @return string
   */
  public function join_form_components($fields) {
    return implode("\n", $fields);
  }
  
  /**
   * Return the HTML the given Form_Object from the form
   *
   * @param Form_Object $object The object to return the html for
   * @param array $name_path The name path to the object
   * @return string
   */
  public function html($object, $name_path) {
    if ($object instanceof Form_Object_Container) {
      
      $items = array();
      $name_path[] = $object->name();
      foreach ($object->objects() as $subObject) {
        $items[] = $this->html($subObject, $name_path);
      }
      
      return $this->join_form_components($items);

    } elseif ($object instanceof Form_Object_Field) {
      return $this->field($object, $name_path);
    } else {
      throw new Exception("Unknown form object: ".get_class($object));
    }
  }

  /**
   * Return the HTML field for the given Form_Object from the form
   *
   * @param Form_Object $object The object to return the field for
   * @param array $name_path The name path to the field
   * @return string
   */
  abstract public function field($object, $name_path);
  
  /**
   * Return the Form_Control object to use for a given Form_Object from
   * the form
   *
   * @param Form_Object $object The object to return the control for
   * @return Form_Control
   */
  public function control_object($object) {
    if ($object->control())
      return $object->control();
    else
      return $this->form()->default_control_for_object($object);
  }

  /**
   * Return the named builder instance. If a builder instance is passed
   * in, it is returned as-is.
   *
   * @param string $name The name of the builder to return
   * @param Form_Base $form The form to create the builder for
   * @return Form_Builder
   */
  public static function instance($name, $form) {
    if ($name instanceof Form_Builder)
      return $name;
    
    if (!self::$instances)
      self::$instances = array();
    
    if (!isset(self::$instances[$name])) {

      $class_name = str_replace('/', '_', Support_Inflector::camelize($name));
      
      $try = array('Builder', 'Form_Builder');
      foreach ($try as $prefix) {
        $klass = $prefix . '_' . $class_name;

        if (class_exists($klass)) {
          self::$instances[$name] = new $klass($form);
          break;
        }
      }
    }
    
    return @self::$instances[$name];
  }

}
