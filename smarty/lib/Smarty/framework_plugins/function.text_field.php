<?php

/**
 * text_field Smarty function
 *
 * Output a text field for editing the property of an object.  This
 * uses an id in the form of "var_attr" and a name in the form of
 * "var[attr]" which makes for easy assignment of attributes when
 * handling the form submission.
 *
 * Options:
 *  - <b>var:</b>  The name of the variable that contains the object
 *  - <b>attr:</b> The name of the attribute on the object being edited (used for naming and to retrieve the field value)
 *  - All other options are passed through as HTML attributes
 *
 * @param array $options  The function options
 * @param Smary $smarty   The Smarty instance
 *
 * @return string
 */
function smarty_function_text_field($options, $smarty) {
  if (!isset($options['var'])) {
    $smarty->trigger_error('Missing required parameter "var" in function "text_field"');
    return;
  }
  if (!isset($options['attr'])) {
    $smarty->trigger_error('Missing required parameter "attr" in function "text_field"');
    return;
  }

  $var  = $options['var'];
  $attr = $options['attr'];
  $obj  = $smarty->get_template_vars($var);
  
  // disabled should either appear or not, but we want to allow true or false
  // to be passed for simplicity.
  if ( empty($options['disabled']) ) {
    unset($options['disabled']);
  } else {
    $options['disabled'] = "disabled";
  }

  unset($options['var']);
  unset($options['attr']);
  
  if ( isset($options['class']) ) {
    $options['class'] .= ' text_field';
  } else {
    $options['class'] = 'text_field';
  }
  
  if ( ! array_key_exists('maxlength', $options) ) {
    $options['maxlength'] = 255;
  }
  
  $options['type'] = 'text';
  $options['id']   = "${var}_${attr}";
  $options['name'] = "${var}[${attr}]";
  $value = $obj ? $obj->$attr : '';
  if ((!empty($value)) && ($value !== 0)) $options['value'] = $value;

  return Support_TagHelper::tag('input', $options);
}

?>