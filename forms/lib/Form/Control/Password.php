<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Outputs HTML as input[type=password]
 */
class Form_Control_Password extends Form_Control_Textfield {
  
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
    $options = array_merge($object->output_options(), array('type'=>'password'));
    return $this->input_tag($form, $object, $name_path, $value, $options);
  }

}
