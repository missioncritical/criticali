<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * fields Smarty function
 *
 * Parameters:
 *  - <b>builder:</b> The builder instance to use (defaults to $builder)
 *  - <b>for:</b> The name of the item to output fields for (default is all fields)
 *
 * Outputs fields for the form associated with a builder.
 *
 * @param array   $options The function options
 * @param Smarty  $smarty  The Smarty instance
 *
 * @return string
 */
function smarty_function_fields($options, &$smarty) {
  
  if (isset($options['builder']))
    $builder = $options['builder'];
  else
    $builder = $smarty->get_template_vars('builder');
  
  if (!$builder) {
    $smarty->trigger_error("Builder could not be found in fields function");
    return;
  }
  
  return $builder->fields(isset($options['for']) ? $options['for'] : null);
}
