<?php
/** @package smarty */

/**
 * section_link Smarty function
 *
 * Outputs a link to a section on the site.  If the link location matches the current controller name, the link will have a class of selected.
 *
 * Options:
 *  - <b>section:</b> The name of the section (for example, foo links to foo.php and FooController)
 *  - <b>text:</b>    The link content / text
 */
function smarty_function_section_link($params, $smarty) {
  if (!isset($params['section'])) {
    $smarty->trigger_error('Missing required parameter "section" in function "section_link"');
    return;
  }

  $section    = $params['section'];
  $text       = isset($params['text']) ? $params['text'] : '';
  $controller = $smarty->get_template_vars('controller');

  $selected = ($controller && ($controller->controller_name() == $section)) ? ' class="selected"' : '';

  return "<a href=\"".htmlspecialchars($section).".php\"$selected><span>$text</span></a>";
}

?>