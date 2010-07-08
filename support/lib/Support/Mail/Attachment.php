<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Represents an email attachment
 */
class Support_Mail_Attachment {
  /** The name of the attachment */
  public $name;
  /** The MIME type of the attachment */
  public $type;
  /** The data or path/filename of the attachment data */
  public $data;
  /** A flag that, when true, indicates $data contains a path and not actual data */
  public $data_is_path;
  
  /**
   * Constructor
   *
   * @param string  $name         The name of the attachment
   * @param string  $type         The MIME type of the attachment
   * @param mixed   $data         The attachment data or path/filename of the data
   * @param boolean $data_is_path When true, indicates the $data parameter is a filename
   */
  public function __construct($name, $type, $data, $data_is_path = false) {
    $this->name = $name;
    $this->type = $type;
    $this->data = $data;
    $this->data_is_path = $data_is_path;
  }
}

?>