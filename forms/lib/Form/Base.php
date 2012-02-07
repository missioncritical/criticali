<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * A Form is an abstract type of view. It represents a collection of
 * fields and rules for displaying and/or working with an object or group
 * of objects.
 */
abstract class Form_Base extends Form_Object_Container {
  
  protected $default_excluded_fields = array();
  
  /**
   * Constructor
   */
  public function __construct($name = null) {
    parent::__construct($name, null);
  }
  
  /**
   * Return the name of this form class
   * @return string
   */
  public function name() {
    if (!$this->name) {
      $this->name = get_class($this);
      if (substr($this->name, -4) == 'Form')
        $this->name = substr($this->name, 0, -4);
      $this->name = strtolower(str_replace('_', '/', $this->name));
    }
    
    return $this->name;
  }

  /**
   * Add a field to the default list to exclude
   *
   * @param string $field  The name of a field (or array of names) to exclude
   */
  public function exclude_field_by_default($field) {
    if (is_array($field))
      foreach ($field as $name) { $this->default_excluded_fields[$name] = 1; }
    else {
      $this->default_excluded_fields[$field] = 1;
    }
  }
  
  /**
   * Include a model on this form
   *
   * @param string $name The (class) name of the model to include
   * @param array $options Any options for the model
   */
  public function include_model($name, $options = null) {
    $options = is_array($options) ? $options : array();
    
    $obj = new Form_Object_Model($this, $name, $options);
    $this->objects[$obj->name()] = $obj;
    
    return $obj;
  }
  
  /**
   * Assign this form's values to another object or set of objects.
   *
   * By default a form expects an array of named objects to assign to. As
   * a special case, if the form contains only one entity (e.g. a single
   * model), when an object is passed in instead of array, it is assumed
   * to correspond to the single entity in the form. In all other cases,
   * an object would be expected to have attributes corresponding to each
   * named entity on the form.
   *
   * @param mixed &$object The object to assign the value to
   */
  public function assign_to(&$object) {
    if ( (count($this->objects) == 1) && is_object($object) ) {
      $temp = array();
      $child = reset($this->objects);
      $temp[$child->name()] = $object;
      $child->assign_to($temp);

    } else {
      foreach ($this->objects as $child) {
        $child->assign_to($object);
      }
    }
  }
  
  /**
   * assign_request is a shorthand method for setting the values from a
   * request on this form and assigning them to an output object (or
   * objects) in a single step.
   *
   * @param mixed $input The input values (such as an HTTP $_REQUEST)
   * @param mixed $output The output object or objects
   */
  public function assign_request(&$input, &$output) {
    $this->set_value($input, true);
    $this->assign_to($output);
  }

  /**
   * Determine if a column should be included on a form by default
   *
   * @param ActiveRecord_Base $model    The model the column belongs to
   * @param ActiveRecord_Column $column The column to test
   * @return boolean
   */
  public function is_column_included_by_default($model, $column) {
    if ( $column->primary() || ($column->name() == $model->primary_key()) )
      return false;
    if (isset($this->default_excluded_fields[$column->name()]))
      return false;
    
    return true;
  }
  
  /**
   * Return the default form field options for a column
   *
   * @param ActiveRecord_Base $model    The model the column belongs to
   * @param ActiveRecord_Column $column The column to provide options for
   * @return array
   */
  public function default_options_for_column($model, $column) {
    $options = array(
      'title'=>$this->title_for_column($model, $column),
      'data_type'=>$this->data_type_for_column($model, $column),
    );
    
    $this->set_column_options($model, $column, $options);
    
    return $options;
  }
  
  /**
   * This method allows derived classes to augment the default options
   * for a column. The default set of options are passed by reference and
   * should be modified directly.
   *
   * @param ActiveRecord_Base $model    The model the column belongs to
   * @param ActiveRecord_Column $column The column options are for
   * @param array &$default_options     The default set of options which may be modified as needed
   */
  protected function set_column_options($model, $column, &$default_options) {
  }
  
  /**
   * Return the title that should be used by default for a column
   *
   * @param ActiveRecord_Base $model    The model the column belongs to
   * @param ActiveRecord_Column $column The column to provide the title for
   * @return string
   */
  public function title_for_column($model, $column) {
    return ucwords(Support_Inflector::humanize($column->name()));
  }

  /**
   * Return the data type that should be used by default for a column
   *
   * @param ActiveRecord_Base $model    The model the column belongs to
   * @param ActiveRecord_Column $column The column to provide the title for
   * @return string
   */
  public function data_type_for_column($model, $column) {
    $column_type = $column->type();
    
    switch ($column_type) {

      case 'string':
      case 'text':
      case 'datetime':
      case 'time':
      case 'date':
      case 'boolean':
        return $column_type;

      case 'integer':
      case 'float':
      case 'decimal':
        return 'number';

      case 'timestamp':
        return 'datetime';
        
      default:
        return 'string';

    }
  }
  
  /**
   * Return the default Form_Control object to use for a given Form_Object from
   * the form
   *
   * @param Form_Object $object The object to return the control for
   * @return Form_Control
   */
  public function default_control_for_object($object) {
    return Form_Control::instance($object->data_type()->default_control());
  }

  /**
   * Return the default form for the named model (or instance of a model)
   *
   * @param mixed $model The model name or instance
   */
  public static function form_for($model) {
    $name = is_object($model) ? get_class($model) : $model;
    
    $klass = $name . 'Form';
    if (class_exists($klass))
      return new $klass();
    
    $try = array('ApplicationForm', 'Form_Default');
    foreach ($try as $klass) {
      if (class_exists($klass)) {
        $ref = new ReflectionClass($klass);
        if (!$ref->isAbstract()) {
          $form = new $klass($name . 'Form');
          $form->include_model(Support_Inflector::underscore($name));
          return $form;
        }
      }
    }
    
    throw new Exception("No form was found for $name");
  }

}
