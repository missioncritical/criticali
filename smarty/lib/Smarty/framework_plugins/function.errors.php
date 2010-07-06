<?php

/**
 * errors Smarty function
 *
 * Output the errors for an ActiveRecord object (if any are present).
 *
 * Options:
 *  - <b>for:</b>  The object to display the errors for (required)
 *
 * @param array $options  The function options
 * @param Smary $smarty   The Smarty instance
 *
 * @return string
 */
function smarty_function_errors($options, $smarty) {
  if (!isset($options['for'])) {
    $smarty->trigger_error('Missing required parameter "for" in function "errors"');
    return;
  }

  $obj  = $options['for'];

  $html = '';

  if (($obj) && (!$obj->errors()->is_empty())) {
    $html .= '<div class="errors">';
    $html .= '<strong>Please correct the following errors:</strong>';
    $html .= '<ul>';

    foreach ($obj->errors()->full_messages() as $msg) {
      $html .= '<li>'.htmlspecialchars($msg).'</li>';
    }

    $html .= '</ul>';
    $html .= '</div>';
  }

  return $html;
}

?>