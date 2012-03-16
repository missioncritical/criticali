<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Outputs HTML as a select element
 *
 * Supported options:
 *  - <b>options|options_name:</b> (required) The options collection or name of the variable containing the collection, respectively. The collection must support foreach iteration.
 *  - <b>value_key|value_attr|value_method:</b> The array key, object attribute, or object method (respectively) to call on each option to obtain the value to output. If not present, treats options as an associative array and uses the array key as the value.
 *  - <b>label_key|label_attr|label_method:</b> The array key, object attribute, or object method (respectively) to call on each option to obtain the label/display value to output. If not present, treats options as an associative array and uses the array value as the label.
 *  - <b>blank_label:</b> If present, a blank option is added with this as its label/display value
 *  - All other options are passed through as HTML attributes
 */
class Form_Control_Select extends Form_Control {
  
  protected $logger = null;
  
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
    return $this->select_tag($form, $object, $name_path, $value, $object->output_options());
  }
  
  /**
   * Return a select element
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @param array $options Output options / element attributes
   * @return string
   */
  public function select_tag($form, $object, $name_path, $value, $options) {
    $choices = $this->load_select_options($form, $options);

    $valueOp = $this->pick_option_operation('value', $options);
    $labelOp = $this->pick_option_operation('label', $options);
    
    // clean up the options
    foreach (array('options', 'options_name', 'value_key', 'value_attr', 'value_method',
      'label_key', 'label_attr', 'label_method') as $var) {
      unset($options[$var]);
    }
  
    $selOptions = array();
  
    if (isset($options['blank_label'])) {
      $selOptions[] = '<option value="">'.htmlspecialchars($options['blank_label']).'</option>';
      unset($options['blank_label']);
    }
  
    foreach ($choices as $optKey=>$optValue) {
      $val = $this->get_option_value($valueOp, $optValue, $optKey);
      $label = $this->get_option_value($labelOp, $optValue, $optValue);
    
      $opt = '<option value="'.htmlspecialchars($val).'"';
      if ($value == $val)
        $opt .= ' selected="selected"';
      $opt .= '>'.htmlspecialchars($label).'</option>';
    
      $selOptions[] = $opt;
    }
  
    $options['id']   = $this->control_id($name_path, $object->name());
    $options['name'] = $this->control_name($name_path, $object->name());

    return Support_TagHelper::content_tag('select', implode("\n", $selOptions), $options);
  }
  
  /**
   * Return the collection of options to use for the select element
   */
  protected function load_select_options($form, $options) {
    if (isset($options['options']))
      return $options['options'];
    
    if (isset($options['options_name'])) {
      $vars = $form->value();

      if (!isset($vars[$options['options_name']])) {
        $this->logger()->warn("Missing expected variable \"$options[options_name]\" for select control.");
        return array();
      }
      
      return $vars[$options['options_name']];
    }

    $this->logger()->warn("Missing required option \"options\" or \"options_name\" in select control.");
    return array();
  }
  
  /**
   * Return information on how to grab a value or label from the options collection
   */
  protected function pick_option_operation($type, $options) {
    foreach (array('_key', '_attr', '_method') as $try) {
      if (isset($options[$type . $try])) {
        $op = substr($try, 1);
        $what = $options[$type . $try];
        
        return array($op, $what);
      }
    }
    
    return null;
  }

  /**
   * Handle returning either the label or value for a given option from
   * the collection based on how the control was configured.
   */
  protected function get_option_value($op, $item, $default) {
    if (!$op)
      return $default;
    
    $what = $op[0];
    $attr = $op[1];
    
    if ($what == 'key') {
      return $item[$attr];
    } elseif ($what == 'attr') {
      return $item->$attr;
    } elseif ($what == 'method') {
      return $item->$attr();
    } else {
      return $default;
    }
  }

  /**
   * Return a logger
   */
  protected function logger() {
    if (!$this->logger)
      $this->logger = Support_Resources::logger(get_class($this));
    return $this->logger;
  }

}
