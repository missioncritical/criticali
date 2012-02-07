<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Time is a data type for a time value
 */
class Form_DataType_Time extends Form_DataType {

  /**
   * The inverse of format_for_control, this takes the value posted from
   * a control converts it to the field's native type or format
   *
   * @param mixed $value The value to form
   * @return mixed
   */
  public function parse_control_value($value) {
    if (is_array($value)) {
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
        elseif ($meridian == 'pm' && $hour > 12)
          $hour += 12;
      }
      
      return sprintf('%02d:%02d:%02d', $hour, $minute, $second);

    } else {
      return $value;
    }
  }

  /**
   * Return the name of the default control type to use for this data type
   * @return string
   */
  public function default_control() {
    return 'time';
  }

}
