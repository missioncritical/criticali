<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Represents an object based on a model in a form
 */
class Form_Object_Model extends Form_Object_Container {
  
  protected $class_name;
  protected $model_instance = null;
  
  /**
   * Constructor
   *
   * Options are:
   *  - <b>class_name:</b> The name of the model class (inferred from this object's name)
   *  - <b>include_fields:</b> If true (the default) fields are automatically included from the model
   *
   * @param string $form       The containing form
   * @param string $name       The name of this object
   * @param array  $options    The array of options
   */
  public function __construct($form, $name, $options) {
    parent::__construct($form, $name);
    
    $defaults = array('class_name'=>Support_Inflector::camelize($name),
                      'include_fields'=>true);
    $options = array_merge($defaults, $options);
    
    $this->class_name = $options['class_name'];
    
    if ($options['include_fields'])
      $this->include_default_fields();
  }
  
  /**
   * Return an instance of the model class associated with this form
   * object
   * @return ActiveRecord_Base
   */
  protected function model_instance() {
    if (!$this->model_instance) {
      $klass = $this->class_name;
      $this->model_instance = new $klass();
    }
    
    return $this->model_instance;
  }
  
  /**
   * Include the default fields for a model
   */
  protected function include_default_fields() {
    $model = $this->model_instance();
    
    foreach ($model->columns() as $col) {
      if ($this->form()->is_column_included_by_default($model, $col))
        $this->add_object(new Form_Object_Field($this->form(), $col->name(),
          $this->form()->default_options_for_column($model, $col)));
    }
  }
  
  /**
   * Include a field from the model in the form. If the field has already
   * been included, it is updated instead.
   *
   * @param string $name  The name of the field to include
   * @param array  $options Options for field (see Form_Object_Field for supported options)
   * @return Form_Object_Model A reference to this object
   */
  public function include_field($name, $options = null) {
    $options = is_array($options) ? $options : array();
    
    $model = $this->model_instance();

    if ($model->has_attribute($name)) {
      $options = array_merge(
        $this->form()->default_options_for_column($model, $model->column_for_attribute($name)),
        $options);
    }
    
    $this->add_object(new Form_Object_Field($this->form(), $name, $options));
    
    return $this;
  }
  
  /**
   * Change_field is an alias of include_field, intended for clarity when
   * updating a field definition that has already been included.
   */
  public function change_field($name, $options = null) {
    return $this->include_field($name, $options);
  }
  
}
