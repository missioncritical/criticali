<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Outputs HTML as a collection of input[type=radio] elements
 *
 * Supported options:
 *  - <b>options|options_name:</b> (required) The options collection or name of the variable containing the collection, respectively. The collection must support foreach iteration.
 *  - <b>value_key|value_attr|value_method:</b> The array key, object attribute, or object method (respectively) to call on each option to obtain the value to output. If not present, treats options as an associative array and uses the array key as the value.
 *  - <b>label_key|label_attr|label_method:</b> The array key, object attribute, or object method (respectively) to call on each option to obtain the label/display value to output. If not present, treats options as an associative array and uses the array value as the label.
 *  - <b>blank_label:</b> If present, a radio button with a blank value is added with this as its label
 *  - All other options are passed through as HTML attributes
 */
class Form_Control_Radios extends Form_Control_Select {
  
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
    return $this->radio_tags($form, $object, $name_path, $value, $object->output_options());
  }
  
  /**
   * Return a concatenation of input[type=radio] elements
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @param array $options Output options / element attributes
   * @return string
   */
  public function radio_tags($form, $object, $name_path, $value, $options) {
    $choices = $this->load_select_options($form, $options);

    $valueOp = $this->pick_option_operation('value', $options);
    $LabelOp = $this->pick_option_operation('label', $options);
    
    // clean up the options
    foreach (array('options', 'options_name', 'value_key', 'value_attr', 'value_method',
      'label_key', 'label_attr', 'label_method') as $var) {
      unset($options[$var]);
    }
  
    $selOptions = array();
  
    if (isset($options['blank_label'])) {
      $blank = $options['blank_label'];
      unset($options['blank_label']);
      
      $selOptions[] = $this->join_radio_tag_and_label($form, $object, $name_path, $value, $options,
        $this->radio_tag($form, $object, $name_path, $value, $options, ''),
        $this->radio_label($form, $object, $name_path, $value, $options, '', $blank),
        ($value == ''));
    }

    foreach ($choices as $optKey=>$optValue) {
      $val = $this->get_option_value($valueOp, $optValue, $optKey);
      $label = $this->get_option_value($lableOp, $optValue, $optValue);
    
      $selOptions[] = $this->join_radio_tag_and_label($form, $object, $name_path, $value, $options,
        $this->radio_tag($form, $object, $name_path, $value, $options, $val),
        $this->radio_label($form, $object, $name_path, $value, $options, $val, $label),
        ($value == $val));
    }
  
    return $this->join_radio_tags($form, $object, $name_path, $value, $options, $selOptions);
  }
  
  /**
   * Return a single input[type=radio] element for one option
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $selected The selected value
   * @param array $options Output options / element attributes
   * @param mixed $value The value for this element
   * @return string
   */
  public function radio_tag($form, $object, $name_path, $selected, $options, $value) {
    $options['id']   = $this->control_id($name_path, $object->name()) . "_$value";
    $options['name'] = $this->control_name($name_path, $object->name());
    $options['type'] = 'radio';
    $options['value'] = $value;

    if ($selected == $value)
      $options['checked'] = 'checked';
    
    return Support_TagHelper::tag('input', $options);
  }

  /**
   * Return a label element for one option
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $selected The selected value
   * @param array $options Output options / element attributes
   * @param mixed $value The value for this element
   * @param string $label The label to use
   * @return string
   */
  public function radio_label($form, $object, $name_path, $selected, $options, $value, $label) {
    $options['for']   = $this->control_id($name_path, $object->name()) . "_$value";

    return Support_TagHelper::content_tag('label', htmlspecialchars($label), $options);
  }

  /**
   * Join the input[type=radio] and label elements
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The object value
   * @param array $options Output options / element attributes
   * @param string $radioHTML The input element HTML
   * @param string $labelHTML The label element HTML
   * @param boolean $isSelected True if this radio element is marked as selected
   * @return string
   */
  public function join_radio_tag_and_label($form, $object, $name_path, $value, $options,
    $radioHTML, $labelHTML, $isSelected) {
    
    return $radioHTML . ' ' . $labelHTML;
  }

  /**
   * Join the collection of all radio tags (which have already been joined with their label)
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The object value
   * @param array $options Output options / element attributes
   * @param array $tags The array of radio tags
   * @return string
   */
  public function join_radio_tags($form, $object, $name_path, $value, $options, $tags) {
    return implode("<br/>\n", $tags);
  }

}
