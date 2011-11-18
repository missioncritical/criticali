<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * CriticalI_Remote_PackageVersion is a CriticalI_Package_Version that
 * is available from one or more remote repositories.
 */
class CriticalI_Remote_PackageVersion extends CriticalI_Package_Version {
  
  protected $remotes;
  protected $wrapper;
  
  /**
   * Constructor
   *
   * @param CriticalI_Project_Package   $package The package this is part of
   * @param array                       $entry   The index entry on the remote
   * @param CriticalI_Remote_Repository $remote  The remote that provides the package
   */
  public function __construct($package, $entry, $remote) {
    $this->package = $package;
    
    list($major, $minor, $revision) = CriticalI_Package_Version::canonify_version($entry['version']);
    
    $this->major = $major;
    $this->minor = $minor;
    $this->revision = $revision;

    $this->directory = null;
    $this->properties = $entry['properties'];
    
    $this->remotes = array($remote);
    $this->wrapper = null;
  }
  
  /**
   * Add a remote that provides the package to the list
   * @param CriticalI_Remote_Repository The remote to add
   */
  public function add_remote($remote) {
    $this->remotes[] = $remote;
  }
  
  /**
   * Fetches this package version from one of our available remotes.
   * @param string $filename The file name where the package should be stored
   */
  public function fetch_to($filename) {
    $name = $this->package->name();
    $version = $this->version_string();
    
    // try each remote until it is found
    foreach ($this->remotes as $remote) {
      try {

        $remote->fetch_package($name, $version, $filename);
        return;

      } catch (Exception $e) {
        trigger_error($e->getMessage(), E_USER_WARNING);
      }
      
    }
    
    throw new Exception("Package $name $version could not be retrieved from any available remote.");
  }
  
  /**
   * Return this package's wrapper. This will fetch the remote package to
   * a temporary file and return the wrapper object for it.
   * @param boolean $reload If true, force the package to be freshly downloaded (not cached)
   * @return CriticalI_Package_Wrapper
   */
  public function wrapper($reload = false) {
    if ($reload || is_null($this->wrapper)) {
      $where = CriticalI_Util::tempfile('package');
    
      $this->fetch_to($where);
      
      $this->wrapper = new CriticalI_Package_Wrapper($where, $this->package->name());
    }
    
    return $this->wrapper;
  }
  
}

?>