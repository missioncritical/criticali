<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * CriticalI_Project_PackageVersion is a CriticalI_Package_Version that
 * has been installed within a project.
 */
class CriticalI_Project_PackageVersion extends CriticalI_Package_Version {
  
  /**
   * Constructor
   *
   * @param CriticalI_Project_Package $package    The package this is part of
   * @param string                    $version    This version number
   * @param array                     $properties The properties of this instance
   */
  public function __construct($package, $version, $properties) {
    $this->package = $package;
    
    list($major, $minor, $revision) = CriticalI_Package_Version::canonify_version($version);
    
    $this->major = $major;
    $this->minor = $minor;
    $this->revision = $revision;

    $this->directory = null;
    $this->properties = $properties;
  }
  
}

?>