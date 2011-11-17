<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Indicates a zip file related error
 */
class CriticalI_Package_ZipError extends Exception {
  protected $archive;
  protected $entry;
  
  /**
   * Constructor
   *
   * @param string $archive The name of the archive
   * @param string $entry The name of the entry
   * @param string $status Any status message from the zip library
   * @param in $code The error code
   */
  public function __construct($archive, $entry = null, $status = null, $code = null) {
    parent::__construct(
      ($entry ?
        "An error occurred processing \"$entry\" in the archive \"$archive\"." :
        "An error occurred in the archive \"$archive\".") .
      ($status ?
        " $status" :
        '') .
      ($code ?
        " Error code: $code" :
        "")
      , $code);
    
    $this->archive = $archive;
    $this->entry = $entry;
  }
  
  /**
   * Return the name of the archive where the error occurred
   *
   * @return string
   */
  public function getArchiveName() {
    return $this->archive;
  }
  
  /**
   * Return the name of the entry that caused the error
   *
   * @return string
   */
  public function getEntryName() {
    return $this->entry;
  }
  
}

?>