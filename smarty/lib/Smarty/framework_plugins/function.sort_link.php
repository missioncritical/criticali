<?php 
/** @package smarty */

/**
 * repeat Smarty function
 *
 * Generates messy sorting links.
 * 
 * Options:
 *  - <b>key:</b>     Sorting key.  These are the valid options to be passed in the request array for sorting.
 *  - <b>title:</b>   Text of link.
 *
 * @param array $options  The function options
 * @param Smary $smarty   The Smarty instance
 *
 * @return string
 */

function smarty_function_sort_link($options, $smarty) {
  if (!isset($options['key'])) {
    $smarty->trigger_error('Missing required parameter "key" in function "sort_link"');
    return;
  }
  if (!isset($options['title'])) {
    $smarty->trigger_error('Missing required parameter "title" in function "sort_link"');
    return;
  }
  $key = $options['key'];
  $inner_html = $options['title'];
  $query_string = '';

  foreach($_GET as $k => $v) {
    if ($k != 'sort' && $k != 'sort_dir') $query_string .= "$k=$v&";
  }
  $query_string .= "sort=$key&";
  
  $current_sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
  if ($current_sort == $key) {
    if ($_GET['sort_dir'] == 'desc')
      $arrow = ' v';
    else {
      $arrow = ' ^';
      $query_string .= 'sort_dir=desc&';
    }
  } else { $arrow = ''; }
  return Support_TagHelper::content_tag('a', htmlspecialchars($inner_html), array('href' => "cms.php?$query_string")) . $arrow;
}
