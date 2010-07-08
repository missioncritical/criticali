<?php
/** @package smarty */

/**
 * checkbox Smarty function
 *
 * Output a check box and corresponding hidden field for editing the
 * boolean property of an object.  This uses an id in the form of
 * "var_attr" and a name in the form of "var[attr]" which makes for easy
 * assignment of attributes when handling the form submission.
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
function smarty_function_checkbox($options, $smarty) {
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

  unset($options['var']);
  unset($options['attr']);

  $options['id']   = "${var}_${attr}";
  $options['name'] = "${var}[${attr}]";
  
  $hiddenOpts = $options;
  $hiddenOpts['id'] .= '_hidden';
  $hiddenOpts['type'] = 'hidden';
  $hiddenOpts['value'] = 0;
  
  $options['type'] = 'checkbox';
  if (!isset($options['value'])) $options['value'] = 1;
  if ($obj->$attr) $options['checked'] = 'checked';
  
  if ( isset($options['class']) ) {
    $options['class'] .= ' checkbox';
  } else {
    $options['class'] = 'checkbox';
  }

  return Support_TagHelper::tag('input', $hiddenOpts)
    . Support_TagHelper::tag('input', $options);
}
