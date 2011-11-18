<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * CriticalI_Remote_Package is a collection of CriticalI_Package versions
 * available from one or more remote repository.
 */
class CriticalI_Remote_Package extends CriticalI_Package {
  
  /**
   * Constructor
   *
   * @param array $entry An entry for the package from a remote repository
   * @param CriticalI_Remote_Repository $remote The remote associated with the package
   */
  public function __construct($entry, $remote) {
    $this->name = $entry['name'];
    $this->versions = array(new CriticalI_Remote_PackageVersion($this, $entry, $remote));
  }
  
  /**
   * Add a version to this collection
   *
   * @param array $entry An entry for the version from a remote repository
   * @param CriticalI_Remote_Repository $remote The remote associated with the version
   */
  public function add_version($entry, $remote) {
    // see if we already have this version
    if (($idx = $this->index_of_version($entry['version'])) !== false) {
      $this->versions[$idx]->add_remote($remote);
    // otherwise, add it
    } else {
      $this->versions[] = new CriticalI_Remote_PackageVersion($this, $entry, $remote);
      usort($this->versions, array('CriticalI_Package_Version', 'compare_versions'));
    }
  }
  
}

?>