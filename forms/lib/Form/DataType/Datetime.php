<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Datetime is a data type for combined date and time
 */
class Form_DataType_Datetime extends Form_DataType {

  /**
   * The inverse of format_for_control, this takes the value posted from
   * a control converts it to the field's native type or format
   *
   * @param mixed $value The value to form
   * @return mixed
   */
  public function parse_control_value($value) {
    if (is_array($value)) {
      // year
      if (isset($value['year'])) $year = $value['year'];
      elseif (isset($value['Year'])) $year = $value['Year'];
      else $year = 0;
      
      // month
      if (isset($value['month'])) $month = $value['month'];
      elseif (isset($value['Month'])) $month = $value['Month'];
      else $month = 0;

      // day
      if (isset($value['day'])) $day = $value['day'];
      elseif (isset($value['Day'])) $day = $value['Day'];
      else $day = 0;

      // hour
      if (isset($value['hour'])) $hour = $value['hour'];
      elseif (isset($value['Hour'])) $hour = $value['Hour'];
      else $hour = 0;

      // minute
      if (isset($value['minute'])) $minute = $value['minute'];
      elseif (isset($value['Minute'])) $minute = $value['Minute'];
      else $minute = 0;

      // second
      if (isset($value['second'])) $second = $value['second'];
      elseif (isset($value['seconds'])) $second = $value['seconds'];
      elseif (isset($value['Second'])) $second = $value['Second'];
      elseif (isset($value['Seconds'])) $second = $value['Seconds'];
      else $second = 0;
      
      // meridian
      if (isset($value['meridian']) || isset($value['Meridian'])) {
        $meridian = isset($value['meridian']) ? strtolower($value['meridian']) :
          strtolower($value['Meridian']);
        
        if ($meridian == 'am' && $hour == 12)
          $hour = 0;
        elseif ($meridian == 'pm' && $hour < 12)
          $hour += 12;
      }
      
      return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);

    } else {
      return $value;
    }
  }


  /**
   * Return the name of the default control type to use for this data type
   * @return string
   */
  public function default_control() {
    return 'datetime';
  }

}
