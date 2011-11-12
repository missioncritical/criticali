<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * CriticalI_Project_Package is a CriticalI_Package that has been
 * installed within a project.
 */
class CriticalI_Project_Package extends CriticalI_Package {
  
  /**
   * Constructor
   *
   * @param string $name       The name of the package
   * @param string $version    The single installed version
   * @param string $properties The properties of the installed version
   */
  public function __construct($name, $version, $properties) {
    $this->name = $name;
    $this->versions = array(new CriticalI_Project_PackageVersion($this, $version, $properties));
  }
  
}

?>