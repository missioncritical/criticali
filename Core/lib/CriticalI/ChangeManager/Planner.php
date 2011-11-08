<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A Planner is used to construct a CriticalI_ChangeManager_Plan for
 * making changes to a repository or project.
 */
class CriticalI_ChangeManager_Planner {
  
  protected $sourceList;
  protected $installedList;
  protected $allowMultipleVersions;
  protected $errors;

  /**
   * Constructor
   */
  public function __construct($sourceList, $installedList, $allowMultipleVersions) {
    $this->sourceList = $sourceList;
    $this->installedList = $installedList;
    $this->allowMultipleVersions = $allowMultipleVersions;
  }

  /**
   * Create a plan for installing the specified package or packages.
   *
   * A few notes on the usage of the parameters. `packageName` may be a
   * string specifying a single package or an array of package names.
   * Likewise `version` may be a single version specification string or
   * an array of version specifications. When `packageName` is a single
   * string, `version` must also be a single string, and when
   * `packageName` is an array, `version` must either be an array of the
   * same size or a single string (indicating the same version
   * specification applies to packages). If no version specification is
   * provided, the specification "*" (meaning any version) is used.
   *
   * @param mixed $packageName          The name or array of packages to install 
   * @param mixed $version              The version specification (or array of specification)
   * @param boolean $evalDepends        If true (default), evaluates package dependencies
   *
   * @return CriticalI_ChangeManager_Plan
   */
  public function install_plan($packageName, $version = null, $evalDepends = true) {
    // we're building a new plan
    $plan = new CriticalI_ChangeManager_Plan();
    $this->errors = array();
    
    // normalize the arguments
    $this->normalize_package_args($packageName, $version);
    
    // add the requirements to our plan
    foreach ($packageName as $idx=>$name) {
      $plan->push_requirement($name, $version[$idx]);
    }
    
    // build out the plan
    $plan = $this->eval_requirements($plan, $evalDepends);
    
    if ($plan === false)
      throw new CriticalI_ChangeManager_ResolutionError($this->errors);
    
    return $plan;
  }
  
  /**
   * Create a plan for removing the specified package or packages.
   *
   * See install_plan() for more information on the parameter format.
   *
   * @param mixed $packageName          The name or array of packages to remove
   * @param mixed $version              The version specification (or array of specification)
   * @param boolean $evalDepends        If true (default), fails when removal would break dependencies
   *
   * @return CriticalI_ChangeManager_Plan
   */
  public function remove_plan($packageName, $version = null, $evalDepends = true) {
    $plan = new CriticalI_ChangeManager_Plan();
    
    // normalize the arguments
    $this->normalize_package_args($packageName, $version);
    
    // add the requirements to our plan
    foreach ($packageName as $idx=>$name) {
      $pkg = $this->installed_package_instance($name, $version[$idx]);
      $plan->remove_package($pkg);
    }
    
    if ($evalDepends) {
      $missing = $this->list_missing_dependencies($plan);
      if (count($missing) > 0)
        throw new CriticalI_ChangeManager_HasDependentError();
    }

    return $plan;
  }
  
  /**
   * Normalize $packageName and $version arguments as passed to public methods
   *
   * @param mixed $packageName          The name or array of packages
   * @param mixed $version              The version specification (or array of specification)
   */
  protected function normalize_package_args(&$packageName, &$version) {
    // normalize the package name
    $packageName = is_array($packageName) ? $packageName : array($packageName);
    
    // and the version specification
    if (!is_array($version)) {
      $version = ($version == '' || is_null($version)) ? '*' : $version;
      $version = array_fill(0, count($packageName), $version);
    }
    if (count($version) != count($packageName))
      throw new CriticalI_UsageError(
        "Number of parameters provided for packageName and version do not match.");
  }

  /**
   * Evaluate the requirements of a plan by adding packages to meet the
   * requirements.
   *
   * @param CriticalI_ChangeManager_Plan $plan  The plan to evaluate
   * @param boolean $evalDepends If true, evaluates package dependencies
   *
   * @return CriticalI_ChangeManager_Plan
   */
  protected function eval_requirements($plan, $evalDepends) {
    $plans = array($plan);
    
    while (count($plans) > 0) {
      $plan = array_pop($plans);
      
      if ($plan->requirement_count() == 0)
        return $plan;
      
      list($package, $version) = $plan->pop_requirement();
  
      if ($this->satisfies_requirement($package, $version) ||
          $plan->satisfies_requirement($package, $version)) {
        $plans[] = $plan;
        continue;
      }

      // see what our options are
      $options = $this->matching_versions($package, $version);
    
      foreach ($options as $pkg) {
        $newPlan = $this->try_add($plan, $pkg, $evalDepends);
        if ($newPlan !== false)
          $plans[] = $newPlan;
      }
      
    }
    
    return false;
  }
  
  /**
   * Clone a package and attempt to add a package to it
   */
  protected function try_add($plan, $pkg, $evalDepends) {
    if ($this->will_conflict($pkg, $plan)) {
      $this->errors[] = "\"" . $pkg->package()->name() . ' (' . $pkg->version_string() .
        ")\" would conflict with an installed or required package.";
      return false;
    }
    
    $newPlan = new CriticalI_ChangeManager_Plan($plan);
    $newPlan->add_package($pkg);
    
    if ($evalDepends) {
      $depends = $pkg->property('dependencies', array());
      foreach ($depends as $name=>$version) {
        $version = ($version == '' || is_null($version)) ? '*' : $version;
        $newPlan->push_requirement($name, $version);
      }
    }
    
    try {
      return $this->eval_requirements($newPlan, $evalDepends);
    } catch (Exception $e) {
      $this->errors[] = $e->getMessage();
      return false;
    }
  }
  
  /**
   * Determine if the installed set of packages satisfy the requirement
   * for the named package and version.
   *
   * @param string $package  The name of the package
   * @param string $version  The version specification
   * @param CriticalI_ChangeManager_Plan $plan Optional plan listing removed packages
   *
   * @return boolean
   */
  protected function satisfies_requirement($package, $version, $plan = null) {
    if (isset($this->installedList[$package])) {
      $pkg = $this->installedList[$package]->satisfy_dependency($version);
      if ( (!is_null($pkg)) && ((!$plan) || (!$plan->is_on_remove_list($pkg))) )
          return true;
    }
    
    return false;
  }
  
  /**
   * Return the list of suitable package versions for installation
   * matching the given package name and version specification.
   *
   * @param string $package  The name of the package
   * @param string $version  The version specification
   *
   * @return array
   */
  protected function matching_versions($package, $version) {
    if (!isset($this->sourceList[$package]))
      throw new CriticalI_UnknownPackageError($package);
      
    $pkg = $this->sourceList[$package];
    
    $matches = array();
    $spec = CriticalI_Package_Version::canonify_version_specification($version);
    
    // find all versions that satisfy the requirements
    for ($i = $pkg->count() - 1; $i >= 0; $i--) {
      $result = $pkg[$i]->compare_version_specification($spec);
      if ($result < 0) break;
      if ($result == 0) $matches[] = $pkg[$i];
    }

    if (count($matches) == 0)
      throw new CriticalI_UnknownPackageVersionError($package, $version);
    
    return $matches;
  }
  
  /**
   * Determine if a conflicting package is already installed (or,
   * optionally, will be installed).
   *
   * @return boolean
   */
  protected function will_conflict($pkg, $plan = null) {
    if ($this->allowMultipleVersions)
      return false;
    
    if (isset($this->installedList[$pkg->package()->name()]))
      return true;
    
    return $plan ? $plan->will_conflict($pkg) : false;
  }
  
  /**
   * Return the named CriticalI_Package_Version instance from the
   * installed list.
   *
   * @param string $package  The name of the package
   * @param string $version  The version specification
   *
   * @return CriticalI_Package_Version
   */
  protected function installed_package_instance($package, $version) {
    if (!isset($this->installedList[$package]))
      throw new CriticalI_ChangeManager_NotInstalledError($package);
      
    $pkg = $this->installedList[$package];
    
    $ver = $pkg->satisfy_dependency($version);
    
    if (!$ver)
      throw new CriticalI_ChangeManager_NotInstalledError($package, $version);
    
    return $ver;
  }
  
  /**
   * Return the list of dependencies that are not fulfilled in the system
   * represented by the current installation and the given plan.
   *
   * @param CriticalI_ChangeManager_Plan $plan  The plan to evaluate
   *
   * @return array
   */
  protected function list_missing_dependencies($plan) {
    $missing = array();
    $check = array();

    // begin with the list of installed packages
    foreach ($this->installedList as $package) {
      foreach ($package as $pkg) {
        if (!$plan->is_on_remove_list($pkg))
          $check[] = $pkg;
      }
    }
    
    // add the list of packages to install
    foreach ($plan->add_list() as $pkg) {
      $check[] = $pkg;
    }
    
    // check everything
    foreach ($check as $pkg) {
      $depends = $pkg->property('dependencies', array());
      foreach ($depends as $name=>$version) {
        $version = ($version == '' || is_null($version)) ? '*' : $version;
        if ((!$this->satisfies_requirement($name, $version, $plan)) &&
            (!$plan->satisfies_requirement($name, $version)))
          $missing[] = array($name, $version);
      }
    }
    
    return $missing;
   }

}

?>