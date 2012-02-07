<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Outputs HTML for a select-driven date and time control
 *
 * Supported options:
 *  - <b>meridian:</b> If true, 12 hour time with an am/pm indicator is used
 *  - All other options are passed through as HTML attributes
 */
class Form_Control_Time extends Form_Control {
  
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
    return $this->time_tags($form, $object, $name_path, $value, $object->output_options());
  }
  
  /**
   * Return a group of select tags for picking a time
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @param array $options Output options / element attributes
   * @return string
   */
  public function time_tags($form, $object, $name_path, $value, $options) {

    $meridian = isset($options['meridian']) ? $options['meridian'] : false;
    $timeParts = $value ? explode(':', $value) : array(-1, -1, -1);
      
    // hour
    $hour = $timeParts[0];
    if ($meridian && $hour == 0)
      $hour = 12;
    elseif ($meridian && $hour > 12)
      $hour -= 12;

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
    $minute = $timeParts[1];
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
    $second = $timeParts[2];
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
      $flag = $timeParts[0] < 12 ? 'am' : 'pm';
      if ($timeParts[0] < 0) $flag = '';
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
      $hourHTML, $minuteHTML, $secondHTML, $meridianHTML);
  }
  
  /**
   * Concatenate HTML for the different date part controls
   *
   * @param Form_Base $form The containing form
   * @param Form_Object $object The form object to return the input tag for
   * @param array $name_path An array of parent names in the path to this object on the form
   * @param mixed $value The value for the control
   * @param array $options Output options / element attributes
   * @param string $hourHTML The HTML for the hour control
   * @param string $minuteHTML The HTML for the minute control
   * @param string $secondHTML The HTML for the second control
   * @param string $meridianHTML The HTML for the meridian control
   * @return string
   */
  public function join_date_parts($form, $object, $name_path, $value, $options,
    $hourHTML, $minuteHTML, $secondHTML, $meridianHTML) {

    return $hourHTML . ' : ' . $minuteHTML . ' : ' . $secondHTML . ' ' . $meridianHTML;
  }

}
