<?php 

/**
 * repeat Smarty function
 *
 * Convenient way to output a string n times in a row.
 * 
 * Options:
 *  - <b>times:</b>     Number of times to output the string.
 *  - <b>separator:</b> A separating string to go between the repeated strings.
 *  - <b>string:</b>    The string to output
 *
 * @param array $options  The function options
 * @param Smary $smarty   The Smarty instance
 *
 * @return string
 */

function smarty_function_repeat($options, $smarty) {
  if (!isset($options['times'])) {
    $smarty->trigger_error('Missing required parameter "times" in function "repeat"');
    return;
  }
  if (!isset($options['string'])) {
    $smarty->trigger_error('Missing required parameter "string" in function "repeat"');
    return;
  }
  if (!isset($options['separator']))
    $options['separator'] = '';
    
  $builder = array();
  for($i = 0; $i < $options['times']; $i += 1) {
    $builder[] = $options['string'];
  }

  return implode($options['separator'], $builder);
}
