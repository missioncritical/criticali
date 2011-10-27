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
  protected $replaced;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->add = array();
    $this->remove = array();
    $this->requirements = array();
    $this->replaced = array();
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
   * Determine if this plan provides the given package and version
   * specification.
   *
   * @param string $packageName  The name of the package to search for
   * @param string $version      The version specification to search for
   *
   * @return boolean
   */
  public function will_satisfy_dependency($packageName, $version) {
    return ($this->index_of_version_specification($this->add, $packageName, $version) !== -1);
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
  public function is_package_on_add_list($packageName) {
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
   * @param string $packageName The name of the package being added
   * @param string $version The version specification from the requirements for the package
   * @param CriticalI_Package_Version $requiredBy The package making the requirement, for dependency tracking
   */
  public function add_package($packageVersion, $packageName, $version, $requiredBy) {
    $idx = $this->index_of_version($this->add, $packageVersion->package()->name(),
      $packageVersion->version_string());
    
    // only add once
    if ($idx == -1)
      $this->add[] = $packageVersion;
    
    // capture the requirement
    $this->add_requirement($packageName, $version, $requiredBy);
  }
  
  /**
   * Add a package name and version specification to the requirements list
   *
   * @param string $packageName The name of the package to add
   * @param string $version The version specification to add
   * @param CriticalI_Package_Version $requiredBy The package making the requirement
   */
  public function add_requirement($packageName, $version, $requiredBy) {
    $idx = $this->index_of_requirement($packageName, $version);
    
    // add only once
    if ($idx == -1) {
      $req = new CriticalI_ChangeManager_PlanRequirement($packageName, $version, $requiredBy);
      $this->requirements[] = $req;
    } else {
      $req = $this->requirements[$idx];
    }
    
    // capture who requires it
    if (is_null($requiredBy))
      $req->locked = true;
    else
      $req->requiredBy[] = $requiredBy;
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
   * Remove a package from the add list
   *
   * @param CriticalI_Pacakge_Version $pkgVer The package version object to remove
   */
  public function remove_from_add_list($pkgVer) {
    $name = $pkgVer->package()->name();
    $ver = $pkgVer->version_string();
    
    // remove it from the list
    $idx = $this->index_of_version($this->add, $name, $ver);
    if ($idx == -1)
      return;
    
    array_splice($this->add, $idx, 1);
      
    // cleanup dependencies as well
    for ($reqIdx = count($this->requirements) - 1; $reqIdx >= 0; $reqIdx -= 1) {
      $req = $this->requirements[$reqIdx];

      $idx = $this->index_of_version($req->requiredBy, $name, $ver);
      if ($idx == -1)
        continue;
      
      array_splice($req->requiredBy, $idx, 1);
      
      if ((!$req->locked) && (count($req->requiredBy) == 0))
        array_splice($this->requirements, $reqIdx, 1);
    }
  }
  
  /**
   * Remove one package version from the add list and add a second one in its place
   *
   * @param CriticalI_Pacakge_Version $oldPkgVer The package version object to replace
   * @param CriticalI_Pacakge_Version $newPkgVer The package version object to add
   * @param string $packageName The name of the package being added
   * @param string $version The version specification from the requirements for the package
   * @param CriticalI_Package_Version $requiredBy The package making the requirement, for dependency tracking
   */
  public function replace_planned_package($oldPkgVer, $newPkgVer, $packageName, $version, $requiredBy) {
    // put the old one on the replaced list
    if ($this->index_of_version($this->replaced, $oldPkgVer->package()->name(),
      $oldPkgVer->version_string()) == -1);
      $this->replaced[] = $oldPkgVer;
    
    // take it off the add list
    $this->remove_from_add_list($oldPkgVer);
    
    // add the new package
    $this->add_package($newPkgVer, $packageName, $version, $requiredBy);
  }

  /**
   * Determine if the replacing the first package with the second will
   * meet the requirements of the plan.
   *
   * @return boolean
   */
  public function will_meet_requirements($oldPkgVer, $newPkgVer) {
    $name = $oldPkgVer->package()->name();
    
    foreach ($this->requirements as $req) {
      if ($req->name != $name)
        continue;
      
      if ($newPkgVer->compare_version_specification($req->version) != 0)
        return false;
    }
    
    return true;
  }

  /**
   * Determine if the given package has previously been substituted by a
   * call to replace_planned_package
   *
   * @return boolean
   */
  public function was_substituted($pkgVer) {
    return ($this->index_of_version($this->replaced, $pkgVer->package()->name(),
      $pkgVer->version_string()) != -1);
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
  public $requiredBy;
  public $locked;
  
  public function __construct($name = null, $version = null, $requiredBy = null) {
    $this->name = $name;
    $this->version = $version;
    $this->requiredBy = is_array($requiredBy) ? $requiredBy :
      (is_null($requiredBy) ? array() : array($requiredBy));
  }
}

?>