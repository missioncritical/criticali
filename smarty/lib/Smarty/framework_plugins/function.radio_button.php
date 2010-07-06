<?php

/**
 * radio_button Smarty function
 *
 * Output a radio button for editing the property of an object.  This
 * uses an id in the form of "var_attr_value" and a name in the form of
 * "var[attr]" which makes for easy assignment of attributes when
 * handling the form submission.
 *
 * Options:
 *  - <b>var:</b>     The name of the variable that contains the object
 *  - <b>attr:</b>    The name of the attribute on the object being edited (used for naming and to retrieve the field value)
 *  - <b>value:</b>   The value of this radio button.
 *  - All other options are passed through as HTML attributes
 *
 * @param array $options  The function options
 * @param Smary $smarty   The Smarty instance
 *
 * @return string
 */
function smarty_function_radio_button($options, $smarty) {
  if (!isset($options['var'])) {
    $smarty->trigger_error('Missing required parameter "var" in function "radio_button"');
    return;
  }
  if (!isset($options['attr'])) {
    $smarty->trigger_error('Missing required parameter "attr" in function "radio_button"');
    return;
  }
  if (!isset($options['value'])) {
    $smarty->trigger_error('Missing required parameter "value" in function "radio_button"');
    return;
  }
  
  $var  = $options['var'];
  $attr = $options['attr'];
  $val  = $options['value'];
  $obj  = $smarty->get_template_vars($var);

  unset($options['var']);
  unset($options['attr']);

  $options['type'] = 'radio';
  $options['id']   = "${var}_${attr}_${val}";
  $options['name'] = "${var}[${attr}]";
  
  if ($obj && isset($obj->$attr) && $obj->$attr === $val)
    $options['checked'] = 'checked';

  return Support_TagHelper::tag('input', $options);
}

?>