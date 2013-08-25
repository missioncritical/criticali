<?php
/** @package smarty */

/**
 * assign_block Smarty function
 *
 * Assign a value to the named block.
 *
 * Options:
 *  - <b>name:</b>  The block name (e.g. 'content')
 *  - <b>value:</b> The value to assign
 *
 * @param array $options  The function options
 * @param Smary $smarty   The Smarty instance
 *
 * @return string
 */
function smarty_function_assign_block($options, $smarty) {
  if (!isset($options['name'])) {
    $smarty->trigger_error('Missing required parameter "name" in function "assign_block"');
    return;
  }

  $name  = $options['name'];
  $value = @$options['value'];

  $blocks  = $smarty->get_template_vars('blocks');
  if (!$blocks) $blocks = array();
  
  $blocks[$name] = $value;
  
  $smarty->assign('blocks', $blocks);
  
  return null;
}
