<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/* Note: This class must eventually be refactored to be more efficient.
   It is adequate for the time being, but will suffer performance and
   memory problems as the number of available packages (particularly for
   repositories) grows. The issue stems from how depedencies are
   resolved. Dependencies form a graph and constructing a plan of which
   packages to install (or upgrade) is really a matter of constructing a
   path through that graph (i.e. this is a problem that has been solved
   many times before). Currently a solution is found by determing all
   possible paths through the graph. That is not particularly scalable.
   As the number of nodes and edges in the graph increase (packages and
   their dependencies, respectively), the resources to evaluate all those
   paths will increase accordingly. This can be solved by viewing the
   problem as identical to a shortest path/least cost routing problem. A
   well-known algorithm exists for solving that problem. The idea behind
   it is simply that as you begin to traverse paths, you only need to
   continue along paths whose length/cost is the least in your set. You
   can do this by maintaining an ordered list of paths you are
   traversing. Once the path you are currently traversing exceeds the
   length/cost of the shortest/cheapest path in your list, you add the
   current path to the list and traverse the shorter/cheaper path
   instead. Once you have a complete path, you only need to evaluate that
   path against other paths in your list with the same cost/length. There
   is plenty of existing documentation out there that explains the idea
   in greater detail, but it's all applicable to this problem with cost
   being equivalent to a preference for paths that begin with the newest
   available package version. Hopefully this note is of some help when
   refactoring in the future. In the meantime, onwards and upwards.
*/

/**
 * A Planner is used to construct a CriticalI_ChangeManager_Plan for
 * making changes to a repository or project.
 */
class CriticalI_ChangeManager_Planner {
  
  protected $sourceList;
  protected $installedList;
  protected $allowMultipleVersions;
  protected $errors;
  protected $upgradable;

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
   * @param mixed $version              The version specification (or array of specifications)
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
      $plan->push_requirement($name, $this->make_install_specification($version[$idx]));
    }
    
    // build out the plan
    $plans = $this->eval_requirements($plan, $evalDepends);
    
    if (!$plans)
      throw new CriticalI_ChangeManager_ResolutionError($this->errors);
    
    return $plans[0];
  }
  
  /**
   * Create a plan for removing the specified package or packages.
   *
   * See install_plan() for more information on the parameter format.
   *
   * @param mixed $packageName          The name or array of packages to remove
   * @param mixed $version              The version specification (or array of specifications)
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
      $pkg = $this->installed_package_instance($name, $this->make_install_specification($version[$idx]));
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
   * Create a plan for upgrading the specified package or packages.
   *
   * See install_plan() for more information on the parameter format.
   *
   * @param mixed $packageName          The name or array of packages to upgrade
   * @param mixed $version              The version specification (or array of specifications)
   * @param boolean $evalDepends        If true (default), fails when upgrading would break dependencies
   *
   * @return CriticalI_ChangeManager_Plan
   */
  public function upgrade_plan($packageName, $version = null, $evalDepends = true) {
    // we're building a new plan
    $plan = new CriticalI_ChangeManager_Plan();
    $this->errors = array();
    $this->upgradable = array();
    
    // normalize the arguments
    $this->normalize_package_args($packageName, $version);
    
    // build out a plan for those packages
    $plans = $this->eval_upgrade($plan, $packageName, $version, $evalDepends);
    
    if ($evalDepends && $plans) {
      foreach ($plans as $plan) {
        $missing = $this->list_missing_dependencies($plan);
        if (count($missing) == 0)
          return $plan;
      }
    } elseif ($plans) {
      return $plans[0];
    }
    
    if ($plans)
        throw new CriticalI_ChangeManager_HasDependentError();
    else
      throw new CriticalI_ChangeManager_ResolutionError($this->errors);
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
   * Return the version specification as one suitable for an install requirement
   *
   * @param string $version The version specification to use
   *
   * @return string
   */
  protected function make_install_specification($version) {
    // the presecence of ! + or - prevents any changes
    if (preg_match("/!-\\+/", $version)) return $version;
    
    $parts = explode('.', $version, 3);
    
    if (count($parts) == 1) {
      // default behavior ok
      return $version;
    } elseif (count($parts) == 2) {
      // require same minor version
      return "$parts[0].$parts[1].0-$parts[0].$parts[1]." . PHP_INT_MAX;
    } else {
      // treat as exact
      return $version . '!';
    }
  }
  
  /**
   * Evaluate the requirements of a plan by adding packages to meet the
   * requirements.
   *
   * @param CriticalI_ChangeManager_Plan $plan  The plan to evaluate
   * @param boolean $evalDepends If true, evaluates package dependencies
   * @param boolean $upgrade     If true (false by default), indicates this is an upgrade
   *
   * @return CriticalI_ChangeManager_Plan
   */
  protected function eval_requirements($plan, $evalDepends, $upgrade = false) {
    $plans = array($plan);
    $solutions = array();
    
    while (count($plans) > 0) {
      $plan = array_shift($plans);
      
      if ($plan->requirement_count() == 0) {
        $solutions[] = $plan;
        continue;
      }
      
      list($package, $version) = $plan->pop_requirement();
  
      if ($this->satisfies_requirement($package, $version, $plan) ||
          $plan->satisfies_requirement($package, $version)) {
        array_unshift($plans, $plan);
        continue;
      }

      // see what our options are
      $options = $this->matching_versions($package, $version);
    
      foreach ($options as $pkg) {
        $newPlans = $this->try_add($plan, $pkg, $evalDepends, $upgrade);
        if ($newPlans) {
          foreach ($newPlans as $newPlan) { $plans[] = $newPlan; }
        }
      }
      
    }
    
    return $solutions ? $solutions : false;
  }
  
  /**
   * Clone a package and attempt to add a package to it
   */
  protected function try_add($plan, $pkg, $evalDepends, $upgrade) {
    if ($this->is_installed($pkg, $plan) && $upgrade) {
      $this->remove_closest($pkg, $plan);
    } elseif ($this->will_conflict($pkg, $plan)) {
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
      return $this->eval_requirements($newPlan, $evalDepends, $upgrade);
    } catch (Exception $e) {
      $this->errors[] = $e->getMessage();
      return false;
    }
  }
  
  /**
   * Evaluate an upgrade request for a set of packages.
   *
   * @param CriticalI_ChangeManager_Plan $plan  The plan to evaluate
   * @param array   $packages    List of package names to upgrade
   * @param array   $versions    List of corresponding version requirements for the upgrade
   * @param boolean $evalDepends If true, evaluates package dependencies
   *
   * @return CriticalI_ChangeManager_Plan
   */
  protected function eval_upgrade($plan, $packages, $versions, $evalDepends) {
    $plans = array();
    $solutions = array();
    
    $package = array_pop($packages);
    $version = array_pop($versions);
    
    if (!isset($this->installedList[$package]))
      throw new CriticalI_ChangeManager_NotInstalledError($package);

    // determine what to remove
    $remove = array();
    $spec = CriticalI_Package_Version::canonify_version_specification($version);

    if ($this->allowMultipleVersions) {
      for ($i = $this->installedList[$package]->count() - 1; $i >= 0; $i--) {
        if ($this->installedList[$package][$i]->compare_version_specification($spec) <= 0) {
          $remove[] = $this->installedList[$package][$i];
          $this->mark_upgradable($this->installedList[$package][$i]);
          break;
        }
      }

    } else {
      foreach ($this->installedList[$package] as $oldPkg) {
        if ($oldPkg->compare_version_specification($spec) > 0)
          throw new CriticalI_ChangeManager_InvalidUpgradeError($package, $version);
        $remove[] = $oldPkg;
      }
    }
    
    // if there's nothing to remove, we've got a problem
    if (!$remove)
      throw new CriticalI_ChangeManager_InvalidUpgradeError($package, $version);
    
    // see what our options are
    $options = array_reverse($this->matching_versions($package, $version));
    foreach ($options as $pkg) {
      if (!$this->is_exact_or_newer_version_installed($pkg)) {
        $newPlan = new CriticalI_ChangeManager_Plan($plan);
        $newPlan->push_requirement($package, $pkg->version_string() . '!');
        foreach ($remove as $r) { $newPlan->remove_package($r); }
        $plans[] = $newPlan;
      }
    }

    // no change is valid if a higher version is not available
    if (!$plans)
      $plans[] = $plan;

    while (count($plans) > 0) {
      $plan = array_pop($plans);
      $r = $plan->requirements_list();

      if (count($packages) > 0) {
        $results = $this->eval_upgrade($plan, $packages, $versions, $evalDepends);
      } else {
        $results = $this->eval_requirements($plan, $evalDepends, true);
      }

      if ($results) {
        foreach ($results as $result) { $solutions[] = $result; }
      }
    }
    
    return $solutions ? $solutions : false;
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
    
    if ( isset($this->installedList[$pkg->package()->name()]) &&
         ((!$plan) || (!$plan->is_on_remove_list($pkg))) )
      return true;
    
    return $plan ? $plan->will_conflict($pkg) : false;
  }
  
  /**
   * Determine if a package by the same name is already installed.
   *
   * @return boolean
   */
  protected function is_installed($pkg, $plan = null) {
    if ( isset($this->installedList[$pkg->package()->name()]) &&
         ((!$plan) || (!$plan->is_on_remove_list($pkg))) )
      return true;
    
    return false;
  }
  
  /**
   * Determine if a specific package version is already installed.
   *
   * @return boolean
   */
  protected function is_exact_version_installed($pkg, $plan = null) {
    if ( isset($this->installedList[$pkg->package()->name()]) &&
         ((!$plan) || (!$plan->is_on_remove_list($pkg))) ) {
      $idx = $this->installedList[$pkg->package()->name()]->index_of_version($pkg->version_string());
      return $idx !== false;
    }
    
    return false;
  }

  /**
   * Determine if a specific package version or newer is already installed.
   *
   * @return boolean
   */
  protected function is_exact_or_newer_version_installed($pkg, $plan = null) {
    if (isset($this->installedList[$pkg->package()->name()])) {
      $localPkg = $this->installedList[$pkg->package()->name()];
      for ($i = $localPkg->count() - 1; $i >= 0; $i--) {
        if ((!$plan) || (!$plan->is_on_remove_list($localPkg[$i]))) {
          $test = $localPkg[$i]->compare_version_number($pkg->version());
          if ($test < 0) return false;
          if ($test >= 0) return true;
        }
      }
    }
    
    return false;
  }
  
  /**
   * Add the dependencies of a package to a list of upgradable packages
   */
  protected function mark_upgradable($pkg) {
    $depends = $pkg->property('dependencies', array());
    foreach ($depends as $name=>$version) {
      if (isset($this->installedList[$name])) {
        $ver = $this->installedList[$name]->satisfy_dependency($version);
        if ($ver) {
          $key = "name-".$ver->version_string();
          $visited = isset($this->upgradable[$key]);
          $this->upgradable[$key] = 1;
          if (!$visited) $this->mark_upgradable($ver);
        }
      }
    }
  }

  /**
   * Add the closest matching installed package to the remove list
   */
  protected function remove_closest($pkg, $plan) {
    if (!isset($this->installedList[$pkg->package()->name()]))
      return;
    
    $package = $this->installedList[$pkg->package()->name()];
    
    if (($package->count() == 1) || (!$this->allowMultipleVersions)) {
      foreach ($package as $p) { $plan->remove_package($p); }

    } else {
      foreach ($package as $p) {
        if ($p->compare_version_number($pkg->version()) <= 0) {
          $key = $p->package()->name() . '-' . $p->version_string();
          if (isset($this->upgradable[$key])) {
            $plan->remove_package($p);
            return;
          }
        }
      }
    }

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