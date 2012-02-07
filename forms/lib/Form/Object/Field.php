<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Represents a single field on a form
 */
class Form_Object_Field extends Form_Object {
  
  protected $title;
  protected $label;
  protected $note;
  protected $data_type;
  protected $control;
  protected $output_options;

  /**
   * Constructor
   *
   * Options are:
   *  - <b>title:</b> The human name of the field
   *  - <b>label:</b> The text to use for labeling the field (the default is the title)
   *  - <b>note:</b>  Optional explanatory text to include with the label
   *  - <b>data_type:</b> The data type of the field (the default is 'string')
   *  - <b>control:</b> An optional control type to use on the form (the default comes from the data type)
   *
   * An other provided options are assumed to be output options
   *
   * @param string $form       The containing form
   * @param string $name       The name of this object
   * @param array  $options    The array of options
   */
  public function __construct($form, $name, $options) {
    parent::__construct($form, $name);
    
    $defaults = array('title'=>Support_Inflector::humanize($name),
                      'data_type'=>'string');
    $options = array_merge($defaults, $options);
    
    $this->title = $options['title'];
    $this->label = isset($options['label']) ? $options['label'] : $this->title;
    $this->note  = isset($options['note']) ? $options['note'] : null;
    $this->data_type = Form_DataType::instance($options['data_type']);
    $this->control = isset($options['control']) ? Form_Control::instance($options['control']) : null;
    
    foreach (array('title', 'label', 'note', 'data_type', 'control') as $opt_name) {
      if (isset($options[$opt_name])) unset($options[$opt_name]);
    }
    
    $this->output_options = $options;
  }
  
  /**
   * Return this field's title (human name)
   * @return string
   */
  public function title() {
    return $this->title;
  }

  /**
   * Return this field's label text
   * @return string
   */
  public function label() {
    return $this->label;
  }

  /**
   * Return this field's note text (if any)
   * @return string
   */
  public function note() {
    return $this->note;
  }

  /**
   * Return this field's data type instance
   * @return Form_DataType
   */
  public function data_type() {
    return $this->data_type;
  }
  
  /**
   * Return this fields data type name
   * @return string
   */
  public function data_type_name() {
    return $this->data_type->name();
  }

  /**
   * Return this field's custom-specified control (if any)
   * @return Form_Control
   */
  public function control() {
    return $this->control;
  }

  /**
   * Return this field's collection of output options
   * @return array
   */
  public function output_options() {
    return $this->output_options;
  }

  /**
   * Set this object's value
   * @param mixed $value
   * @param boolean $fromControl When true, indicates the value is being assigned from a control
   */
  public function set_value($value, $fromControl = false) {
    if ($fromControl)
      $this->value = $this->data_type()->parse_control_value($value);
    else
      $this->value = $value;
  }

}
