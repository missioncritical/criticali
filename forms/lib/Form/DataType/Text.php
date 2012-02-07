<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Text is a data type for a multiple lines of free-form text
 */
class Form_DataType_Text extends Form_DataType {

  /**
   * Return the name of the default control type to use for this data type
   * @return string
   */
  public function default_control() {
    return 'textarea';
  }
  
}
