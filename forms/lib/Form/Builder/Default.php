<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * The default form builder
 */
class Form_Builder_Default extends Form_Builder {

  /**
   * Return the HTML field for the given Form_Object from the form
   *
   * @param Form_Object $object The object to return the field for
   * @param array $name_path The name path to the field
   * @return string
   */
  public function field($object, $name_path) {
    $controlObject = $this->control_object($object);

    $label = $this->label($object, $controlObject, $name_path);
    $control = $this->control($object, $controlObject, $name_path);
    
    return $this->join_label_and_control($object, $controlObject, $name_path, $label, $control);
  }
  
  /**
   * Return the HTML label for the given Form_Object from the form
   *
   * @param Form_Object $object The object to return the label for
   * @param Form_Control $control The control object to use
   * @param array $name_path The name path to the field
   * @return string
   */
  public function label($object, $control, $name_path) {
    return Support_TagHelper::content_tag('label',
      htmlspecialchars($object->label()) .
        ($object->note() ?
          Support_TagHelper::content_tag('span', htmlspecialchars($object->note()), array('class'=>'note'))
          : ''
        ),
      array('for'=>$control->control_id($name_path, $object->name())) );
  }

  /**
   * Return the HTML control for the given Form_Object from the form
   *
   * @param Form_Object $object The object to return the control for
   * @param Form_Control $control The control object to use
   * @param array $name_path The name path to the field
   * @return string
   */
  public function control($object, $control, $name_path) {
    return $control->html($this->form(), $object, $name_path,
      $object->data_type()->format_for_control($object->value()));
  }

  /**
   * Join the HTML for a field's label and a field's control
   *
   * @param Form_Object $object The object the fields are for
   * @param Form_Control $control The control instance being used for the field
   * @param array $name_path The name path to the field
   * @param string $labelHTML The field label HTML
   * @param string $controlHTML The field control HTML
   * @return string
   */
  public function join_label_and_control($object, $control, $name_path, $labelHTML, $controlHTML) {
    if ($control->name() == 'checkbox')
      return $controlHTML . ' ' . $labelHTML . "<br/>\n";
    else
      return $labelHTML . "<br/>\n" . $controlHTML . "<br/>\n";
  }
}
