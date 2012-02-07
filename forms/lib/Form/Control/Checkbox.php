<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Outputs HTML as input[type=checkbox]
 */
class Form_Control_Checkbox extends Form_Control {
  
  /**
   * Output the control HTML for a form object
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return HTML for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @return string
   */
  public function html($form, $object, $name_path, $value) {
    return $this->input_tag($form, $object, $name_path, $value, $object->output_options());
  }
  
  /**
   * Return an input checkbox element
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @param array $options Output options / element attributes
   * @return string
   */
  public function input_tag($form, $object, $name_path, $value, $options) {
    $class_name = $this->default_class_name();

    if ($class_name && isset($options['class']))
      $options['class'] .= " $class_name";
    elseif ($class_name)
      $options['class'] = $class_name;
    
    if (!isset($options['value']))
      $options['value'] = 1;
      
    if ($value)
      $options['checked'] = 'checked';
  
    $options['type'] = 'checkbox';
    $options['id']   = $this->control_id($name_path, $object->name());
    $options['name'] = $this->control_name($name_path, $object->name());
    
    $hiddenOptions = array(
      'type'=>'hidden',
      'id'=>($options['id'] . '_hidden'),
      'name'=>$options['name'],
      'value'=>0
    );

    return Support_TagHelper::tag('input', $hiddenOptions) .
      Support_TagHelper::tag('input', $options);
  }
  
  /**
   * Return any default class name that should appear on the input element
   */
  public function default_class_name() {
    return 'checkbox';
  }

}
