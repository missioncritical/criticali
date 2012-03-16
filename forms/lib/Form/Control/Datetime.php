<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Outputs HTML for a select-driven date and time control
 *
 * Supported options:
 *  - <b>start_year:</b> The first year in the selection range to include.
 *  - <b>end_year:</b> The last year in the range to include
 *  - <b>meridian:</b> If true, 12 hour time with an am/pm indicator is used
 *  - All other options are passed through as HTML attributes
 */
class Form_Control_Datetime extends Form_Control {
  
  /**
   * Output the control HTML for a form object
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return HTML for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @return string
   */
  public function html($form, $object, $name_path, $value) {
    return $this->datetime_tags($form, $object, $name_path, $value, $object->output_options());
  }
  
  /**
   * Return a group of select tags for picking a date
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @param array $options Output options / element attributes
   * @return string
   */
  public function datetime_tags($form, $object, $name_path, $value, $options) {
    $startYear = date('Y');
    if (isset($options['start_year'])) {
      $y = intval($options['start_year']);
      $startYear = ($y < 1000) ? ($startYear + $y) : $y;
      unset($options['start_year']);
    }
    
    $endYear = $startYear + 100;
    if (isset($options['end_year'])) {
      $y = intval($options['end_year']);
      $endYear = ($y < 1000) ? ($startYear + $y) : $y;
      unset($options['end_year']);
    }
    
    $step = 1;
    if ($endYear < $startYear)
      $step = -1;
      
    $ts = strtotime($value);
    
    // month
    $month = $value ? intval(date('n', $ts)) : 0;
    $months = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
    $selOptions = array();
    for ($i = 0; $i <= 12; $i++) {
      $opt = '<option value="'.$i.'"';
      if ($month == $i)
        $opt .= ' selected="selected"';
      $opt .= '>'.htmlspecialchars($months[$i]).'</option>';
    
      $selOptions[] = $opt;
    }

    $options['id']   = $this->control_id($name_path, $object->name()) . "_month";
    $options['name'] = $this->control_name($name_path, $object->name()) . "[month]";

    $monthHTML = Support_TagHelper::content_tag('select', implode("\n", $selOptions), $options) . "\n";
  

    // day
    $day = $value ? intval(date('j', $ts)) : 0;
    $selOptions = array();
    for ($i = 0; $i <= 31; $i++) {
      $label = $i == 0 ? '' : $i;
      $opt = '<option value="'.$i.'"';
      if ($day == $i)
        $opt .= ' selected="selected"';
      $opt .= '>'.$label.'</option>';
    
      $selOptions[] = $opt;
    }

    $options['id']   = $this->control_id($name_path, $object->name()) . "_day";
    $options['name'] = $this->control_name($name_path, $object->name()) . "[day]";

    $dayHTML = Support_TagHelper::content_tag('select', implode("\n", $selOptions), $options) . "\n";
  

    // year
    $year = $value ? intval(date('Y', $ts)) : 0;
    $selOptions = array();
    $selOptions[] = '<option value=""></option>';
    $i = $startYear;
    $j = $i;
    do {
      $opt = '<option value="'.$i.'"';
      if ($year == $i)
        $opt .= ' selected="selected"';
      $opt .= '>'.$i.'</option>';
    
      $selOptions[] = $opt;
    
      $j = $i;
      $i += $step;
    } while ($j != $endYear);

    $options['id']   = $this->control_id($name_path, $object->name()) . "_year";
    $options['name'] = $this->control_name($name_path, $object->name()) . "[year]";

    $yearHTML = Support_TagHelper::content_tag('select', implode("\n", $selOptions), $options) . "\n";

    
    // hour
    $meridian = isset($options['meridian']) ? $options['meridian'] : false;
    if ($meridian)
      $hour = $value ? intval(date('g', $ts)) : -1;
    else
      $hour = $value ? intval(date('G', $ts)) : -1;

    $selOptions = array();
    $selOptions[] = '<option value=""></option>';
    $i = $meridian ? 1 : 0;
    $j = $meridian ? 12 : 23;
    for (; $i <= $j; $i++) {
      $opt = '<option value="'.$i.'"';
      if ($hour == $i)
        $opt .= ' selected="selected"';
      $opt .= '>'.$i.'</option>';
    
      $selOptions[] = $opt;
    }

    $options['id']   = $this->control_id($name_path, $object->name()) . "_hour";
    $options['name'] = $this->control_name($name_path, $object->name()) . "[hour]";

    $hourHTML = Support_TagHelper::content_tag('select', implode("\n", $selOptions), $options) . "\n";


    // minute
    $minute = $value ? intval(date('i', $ts)) : -1;
    $selOptions = array();
    $selOptions[] = '<option value=""></option>';
    for ($i = 0; $i < 60; $i++) {
      $j = sprintf('%02d', $i);
      $opt = '<option value="'.$j.'"';
      if ($minute == $j)
        $opt .= ' selected="selected"';
      $opt .= '>'.$j.'</option>';
    
      $selOptions[] = $opt;
    }

    $options['id']   = $this->control_id($name_path, $object->name()) . "_minute";
    $options['name'] = $this->control_name($name_path, $object->name()) . "[minute]";

    $minuteHTML = Support_TagHelper::content_tag('select', implode("\n", $selOptions), $options) . "\n";


    // second
    $second = $value ? intval(date('s', $ts)) : -1;
    $selOptions = array();
    $selOptions[] = '<option value=""></option>';
    for ($i = 0; $i < 60; $i++) {
      $j = sprintf('%02d', $i);
      $opt = '<option value="'.$j.'"';
      if ($second == $j)
        $opt .= ' selected="selected"';
      $opt .= '>'.$j.'</option>';
    
      $selOptions[] = $opt;
    }

    $options['id']   = $this->control_id($name_path, $object->name()) . "_second";
    $options['name'] = $this->control_name($name_path, $object->name()) . "[second]";

    $secondHTML = Support_TagHelper::content_tag('select', implode("\n", $selOptions), $options) . "\n";
    
    
    $meridianHTML = '';
    if ($meridian) {
      $flag = $value ? date('a', $ts) : -1;
      $selOptions = array();
      $selOptions[] = '<option value=""></option>';
      foreach (array('am', 'pm') as $flagValue) {
        $opt = '<option value="'.$flagValue.'"';
        if ($flag == $flagValue)
          $opt .= ' selected="selected"';
        $opt .= '>'.$flagValue.'</option>';
    
        $selOptions[] = $opt;
      }

      $options['id']   = $this->control_id($name_path, $object->name()) . "_meridian";
      $options['name'] = $this->control_name($name_path, $object->name()) . "[meridian]";

      $meridianHTML = Support_TagHelper::content_tag('select', implode("\n", $selOptions),
        $options) . "\n";
    }

    
    return $this->join_date_parts($form, $object, $name_path, $value, $options,
      $monthHTML, $dayHTML, $yearHTML, $hourHTML, $minuteHTML, $secondHTML, $meridianHTML);
  }
  
  /**
   * Concatenate HTML for the different date part controls
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @param array $options Output options / element attributes
   * @param string $monthHTML The HTML for the month control
   * @param string $dayHTML The HTML for the day control
   * @param string $yearHTML The HTML for the year control
   * @param string $hourHTML The HTML for the hour control
   * @param string $minuteHTML The HTML for the minute control
   * @param string $secondHTML The HTML for the second control
   * @param string $meridianHTML The HTML for the meridian control
   * @return string
   */
  public function join_date_parts($form, $object, $name_path, $value, $options,
    $monthHTML, $dayHTML, $yearHTML, $hourHTML, $minuteHTML, $secondHTML, $meridianHTML) {

    return $monthHTML . ' / ' . $dayHTML . ' / ' . $yearHTML . ' ' .
      $hourHTML . ' : ' . $minuteHTML . ' : ' . $secondHTML . ' ' . $meridianHTML;
  }

}
