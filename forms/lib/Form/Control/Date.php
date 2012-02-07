<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Outputs HTML for a select-driven date control
 *
 * Supported options:
 *  - <b>start_year:</b> The first year in the selection range to include.
 *  - <b>end_year:</b> The last year in the range to include
 *  - All other options are passed through as HTML attributes
 */
class Form_Control_Date extends Form_Control {
  
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
    return $this->date_tags($form, $object, $name_path, $value, $object->output_options());
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
  public function date_tags($form, $object, $name_path, $value, $options) {
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
    
    return $this->join_date_parts($form, $object, $name_path, $value, $options,
      $monthHTML, $dayHTML, $yearHTML);
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
   * @return string
   */
  public function join_date_parts($form, $object, $name_path, $value, $options,
    $monthHTML, $dayHTML, $yearHTML) {

    return $monthHTML . ' / ' . $dayHTML . ' / ' . $yearHTML;
  }

}
