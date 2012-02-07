<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Boolean is a data type for true/false values
 */
class Form_DataType_Boolean extends Form_DataType {

  /**
   * Return the name of the default control type to use for this data type
   * @return string
   */
  public function default_control() {
    return 'checkbox';
  }

}
