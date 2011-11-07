<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A Plan represents a planned set of Package_Version changes for a
 * repository or project.
 */
class CriticalI_ChangeManager_Plan {
  protected $add;
  protected $remove;
  protected $requirements;
  
  /**
   * Constructor
   *
   * @param CriticalI_ChangeManager_Plan $copy Optional plan to create a copy of
   */
  public function __construct($copy = null) {
    $this->add = $copy ? $copy->add_list() : array();
    $this->remove = $copy ? $copy->remove_list() : array();
    $this->requirements = $copy ? $copy->requirements_list() : array();
  }
  
  /**
   * Return the list of packages to add
   *
   * @return array
   */
  public function add_list() {
    return $this->add;
  }
  
  /**
   * Return the list of packages to remove
   *
   * @return array
   */
  public function remove_list() {
    return $this->remove;
  }

  /**
   * Return the list of requirements
   *
   * @return array
   */
  public function requirements_list() {
    return $this->requirements;
  }
  
  /**
   * Return the count of requirements in this plan's list
   *
   * @return int
   */
  public function requirement_count() {
    return count($this->requirements);
  }

  /**
   * Add a requirement to the list
   *
   * @param string $name    Required package name
   * @param string $version Required version specification
   */
  public function push_requirement($name, $version) {
    if ($this->index_of_requirement($name, $version) == -1) {
      $this->requirements[] = new CriticalI_ChangeManager_PlanRequirement($name, $version);
    }
  }
  
  /**
   * Remove and return the last requirement from the list. Returns an
   * array whose first element is the package name and whose second
   * element is the version specification.
   *
   * @return array
   */
  public function pop_requirement() {
    $req = array_pop($this->requirements);
    
    return is_null($req) ? $req : array($req->name, $req->version);
  }
  
  /**
   * Determine if this plan already provides the given package and
   * version specification
   *
   * @param string $name    Package name to test
   * @param string $version Version specification to test
   *
   * @return boolean
   */
  public function satisfies_requirement($package, $version) {
    return ($this->index_of_version_specification($this->add, $package, $version) !== -1);
  }
  
  /**
   * Determine if the given package will conflict with planned packages
   * to install
   *
   * @param CriticalI_Package_Version $pkg The package to test
   *
   * @return boolean
   */
  public function will_conflict($pkg) {
    return $this->is_on_add_list($pkg->package()->name());
  }

  /**
   * Determine if the given package version is to be removed
   *
   * @param CriticalI_Package_Version $packageVersion The version to search for
   *
   * @return boolean
   */
  public function is_on_remove_list($packageVersion) {
    return ($this->index_of_version($this->remove, $packageVersion->package()->name(),
      $packageVersion->version_string()) !== -1);
  }
  
  /**
   * Determine if the given package name is to be added
   *
   * @param string $packageName  The name of the package to search for
   *
   * @return boolean
   */
  public function is_on_add_list($packageName) {
    return ($this->index_of_package($this->add, $packageName) !== -1);
  }
  
  /**
   * Return the CriticalI_Package_Version instance with the given name
   * from the add list
   *
   * @param string $packageName  The name of the package to search for
   *
   * @return CriticalI_Package_Version
   */
  public function package_on_add_list($packageName) {
    $idx = $this->index_of_package($this->add, $packageName);
    
    return ($idx === -1 ? null : $this->add[$idx]);
  }
  
  /**
   * Add the package version to the add list
   *
   * @param CriticalI_Pacakge_Version $packageVersion The package version object to add
   */
  public function add_package($packageVersion) {
    $idx = $this->index_of_version($this->add, $packageVersion->package()->name(),
      $packageVersion->version_string());
    
    // only add once
    if ($idx == -1)
      $this->add[] = $packageVersion;
  }
  
  /**
   * Add a package to the remove list
   */
  public function remove_package($packageVersion) {
    $idx = $this->index_of_version($this->remove, $packageVersion->package()->name(),
      $packageVersion->version_string());
    
    // only add to list once
    if ($idx == -1)
      $this->remove[] = $packageVersion;
  }
  
  /**
   * Search an array for a matching package version specification
   */
  protected function index_of_version_specification(&$list, $packageName, $packageVersion) {
    $spec = CriticalI_Package_Version::canonify_version_specification($packageVersion);
    foreach ($list as $idx=>$pkg) {
      if (($pkg->package()->name() == $packageName) &&
          ($pkg->compare_version_specification($spec) == 0))
        return $idx;
    }
    
    return -1;
  }
  
  /**
   * Search an array for a matching package version number
   */
  protected function index_of_version(&$list, $packageName, $packageVersion) {
    $ver = CriticalI_Package_Version::canonify_version($packageVersion);
    foreach ($list as $idx=>$pkg) {
      if (($pkg->package()->name() == $packageName) &&
          ($pkg->compare_version_number($ver) == 0))
        return $idx;
    }
    
    return -1;
  }

  /**
   * Search an array for a matching package (any version)
   */
  protected function index_of_package(&$list, $packageName) {
    foreach ($list as $idx=>$pkg) {
      if ($pkg->package()->name() == $packageName)
        return $idx;
    }
    
    return -1;
  }
  
  /**
   * Search the requirements array for a matching package and version
   * specification
   */
  protected function index_of_requirement($name, $version) {
    foreach ($this->requirements as $idx=>$req) {
      if ($req->name == $name && $req->version == $version)
        return $idx;
    }
    
    return -1;
  }
  
}

/**
 * Used internally to model requirements
 */
class CriticalI_ChangeManager_PlanRequirement {
  public $name;
  public $version;
  
  public function __construct($name = null, $version = null) {
    $this->name = $name;
    $this->version = $version;
  }
}

?>