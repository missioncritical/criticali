<?php
/** @package smarty */

/**
 * week_options Smarty function
 *
 * Generates options for a select with formatted inner text and week numbers as values.
 *
 * Options:
 *  - <b>weeks:</b>     An array of week numbers.
 *  - <b>selected:</b>  The selected week.
 *
 * @param array $options  The function options
 * @param Smary $smarty   The Smarty instance
 *
 * @return string
 */
function smarty_function_week_options($options, $smarty) {
  if (!isset($options['weeks'])) {
    $smarty->trigger_error('Missing required parameter "week" in function "week_options"');
    return;
  }
  
  $option_tags = '';
  $format = '%B %e';
  
  foreach($options['weeks'] as $week) {
    $day_start = ($week-1)*7;
    $start_time = mktime(0, 0, 0, 12, 30+($week-1)*7);
    $end_time   = $start_time + 6*24*60*60;
    
    $content = sprintf('%s - %s', strftime($format,$start_time), strftime($format,$end_time));
    $html_opts = array('value' => $week);
    if($_REQUEST['week'] == $week) $html_opts['selected'] = 'selected';
    $option_tags .= Support_TagHelper::content_tag('option', $content, $html_opts);
  }
  
  return $option_tags;
}

?>