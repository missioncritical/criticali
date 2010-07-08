<?php
/** @package smarty */

/**
 * controller_action_id Smarty function
 *
 * Outputs a string (using underscore format) comprised of the current controller and action name.
 */
function smarty_function_controller_action_id($params, $smarty) {
  $controller = $smarty->get_template_vars('controller');
  
  return $controller ? ($controller->controller_name() . '_' .
    Support_Inflector::underscore($controller->action())) : '';

}

?>