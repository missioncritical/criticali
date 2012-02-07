<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Outputs HTML as a textarea element
 */
class Form_Control_Textarea extends Form_Control {
  
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
    return $this->textarea_tag($form, $object, $name_path, $value, $object->output_options());
  }
  
  /**
   * Return a textarea element
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @param array $options Output options / element attributes
   * @return string
   */
  public function textarea_tag($form, $object, $name_path, $value, $options) {
    // disabled should either appear or not, but we want to allow true or false
    // to be passed for simplicity.
    if (empty($options['disabled']))
      unset($options['disabled']);
    else
      $options['disabled'] = "disabled";

    $options['id']   = $this->control_id($name_path, $object->name());
    $options['name'] = $this->control_name($name_path, $object->name());

    return Support_TagHelper::content_tag('textarea', htmlspecialchars($value), $options);
  }
  
}
