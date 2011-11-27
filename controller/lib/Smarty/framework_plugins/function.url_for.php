<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

/**
 * url_for Smarty function
 *
 * Output a URL constructed from a set of parameters. This is analogous
 * to the url_for() method on a controller class.
 *
 * Options:
 *  - <b>controller:</b>  The controller name (default is the current controller)
 *  - <b>action:</b> The action name (default is the current action)
 *  - <b>id:</b> A value for the id parameter or a object with supplies an id method
 *  - <b>method:</b> The HTTP request method to use
 *  - All other options are assumed to be normal request parameters
 *
 * @param array $options  The function options
 * @param Smary $smarty   The Smarty instance
 *
 * @return string
 */
function smarty_function_url_for($options, $smarty) {
  $controller = $smarty->get_template_vars('controller');
  if (!$controller) {
    $smarty->trigger_error('The "url_for" function must be called in the context of a controller');
    return;
  }

  $method = 'get';
  if (isset($options['method'])) {
    $method = $options['method'];
    unset($options['method']);
  }
  
  return $controller->url_for($options, $method);
}

?>