<?php

/**
 * message_area Smarty function
 *
 * Options:
 *  - <b>name:</b> The name of the area. Defaults to msg.
 *  - <b>text:</b> A message to display. Defaults to flash($name).
 */
function smarty_function_message_area($params, $smarty) {
  $controller = $smarty->get_template_vars('controller');
  
  if ( ! isset($params['name']) ) {
    $params['name'] = 'msg';
  }
  
  if ( ! isset($params['text']) && $controller ) {
    $params['text'] = $controller->flash($params['name']);
  }
  
  $content = '';
  if ( $params['text'] ) {
    $content = '<span>' . htmlspecialchars($params['text']) . '</span>';
  }
  
  return '<div id="message_area_' . htmlspecialchars($params['name']) . '"'
    . ' class="message_area">' . $content . '</div>';
}
