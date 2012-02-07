<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Date is a data type for a calendar date value
 */
class Form_DataType_Date extends Form_DataType {

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
      
      return sprintf('%04d-%02d-%02d', $year, $month, $day);

    } else {
      return $value;
    }
  }

  /**
   * Return the name of the default control type to use for this data type
   * @return string
   */
  public function default_control() {
    return 'date';
  }

}
