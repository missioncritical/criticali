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
  protected $dependentsList;

  /**
   * Constructor
   */
  public function __construct($sourceList, $installedList, $allowMultipleVersions) {
    $this->sourceList = $sourceList;
    $this->installedList = $installedList;
    $this->allowMultipleVersions = $allowMultipleVersions;
    $this->dependentsList = null;
  }
  
  /**
   * Create a plan for installing the specified package
   *
   * @param string $packageName          The name of the package to install
   * @param string $versionSpecification The version specification for the package
   * @param boolean $evalDepends         If true (default), evaluates package dependencies
   * @param boolean $canUpgrade          If true (default), upgrades are allowed
   *
   * @return CriticalI_ChangeManager_Plan
   */
  public function install_plan($packageName, $versionSpecification = null, $evalDepends = true, $canUpgrade = true) {
    // create a new plan
    $plan = new CriticalI_ChangeManager_Plan();
    
    // normalize the version specification
    if ($versionSpecification == '' || is_null($versionSpecification))
      $versionSpecification = '*';
    
    // build the plan
    $this->add_to_plan($plan, $packageName, $versionSpecification, $evalDepends, $canUpgrade);
    
    return $plan;
  }
  
  /**
   * Add a package to a plan
   *
   * @param CriticalI_ChangeManager_Plan $plan The plan to add the package to
   * @param string $packageName The name of the package to add
   * @param string $version The version specification for the package
   * @param boolean $evalDepends If true (default), evaluates package dependencies
   * @param boolean $canUpgrade If true (default), upgrades are allowed
   * @param CriticalI_Package_Version $requiredBy The package requiring the addition for dependency tracking
   */
  protected function add_to_plan($plan, $packageName, $version, $evalDepends = true, $canUpgrade = true, $requiredBy = null) {
    // determine if the destination already has the package
    if ($this->satisfies_dependency($plan, $packageName, $version)) {
      // make sure the requirement is captured
      $plan->add_requirement($packageName, $version, $requiredBy);
      // no other changes needed
      return;
    }
    
    // make sure the source provides the package
    if (!isset($this->sourceList[$packageName]))
      throw new CriticalI_UnknownPackageError($packageName);
    $pkg = $this->sourceList[$packageName];
    $pkgVer = $pkg->satisfy_dependency($version);
    if (is_null($pkgVer))
      throw new CriticalI_UnknownPackageVersionError($arg, $version);
      
    // handle conflicts and automatic upgrades
    if ($this->has_conflict($plan, $packageName, $version, $canUpgrade, $pkgVer, $requiredBy))
      throw new CriticalI_Project_AlreadyInstalledError($packageName);

    // add the package
    $plan->add_package($pkgVer, $packageName, $version, $requiredBy);

    // evaluate dependencies
    if ($evalDepends) {
      $depends = $pkgVer->property('dependencies', array());
      foreach ($depends as $name=>$version) {
        if ($version == '' || is_null($version))
          $version = '*';
        $this->add_to_plan($plan, $name, $version, true, $canUpgrade, $pkgVer);
        // always check to make sure we weren't replaced
        if ($plan->was_substituted($pkgVer))
          return;
      }
    }
  }
  
  /**
   * Return true if the current system or planned changes satisfy the
   * dependency.
   *
   * @param CriticalI_ChangeManager_Plan $plan The plan to add the package to
   * @param string $packageName The name of the package to add
   * @param string $version The version specification for the package
   *
   * @return boolean
   */
  protected function satisfies_dependency($plan, $packageName, $version) {
    // check the current system
    if (isset($this->installedList[$packageName])) {
      $pkgVer = $this->installedList[$packageName]->satisfy_dependency($version);
      if (!is_null($pkgVer)) {
        // only true if the specified package is not being removed
        if (!$plan->is_on_remove_list($pkgVer)) {
          return true;
        }
      }
    }
    
    // check the packages being added
    if ($plan->will_satisfy_dependency($packageName, $version))
      return true;
    
    return false;
  }
  
  /**
   * Returns the list of installed dependents indexed by package name
   *
   * @return array
   */
  protected function dependents_list() {
    if (is_null($this->dependentsList)) {
      $this->dependentsList = array();
      
      foreach ($this->installedList as $name=>$pkg) {
        foreach ($pkg as $pkgVer) {
          $depends = $pkgVer->property('dependencies', array());
          foreach ($depends as $n=>$v) {
            if ($v == '' || is_null($v)) $v = '*';
            if (!isset($this->dependentsList[$n])) $this->dependentsList[$n] = array();
            $this->dependentsList[$n][] = array('requirement'=>$v, 'version'=>$pkgVer);
          }
        }
      }
    }
    
    return $this->dependentsList;
  }
  
  /**
   * Determine if there is a conflict with adding the given package in the
   * existing system or planned changes.
   *
   * @param CriticalI_ChangeManager_Plan $plan The plan to add the package to
   * @param string $packageName The name of the package to add
   * @param string $version The version specification for the package
   * @param boolean $canUpgrade If true, upgrades are allowed
   * @param CriticalI_Package_Version $newPkgVer The new version object to install
   * @param CriticalI_Package_Version $requiredBy The package requiring the addition for dependency tracking
   *
   * @return boolean
   */
  protected function has_conflict($plan, $packageName, $version, $canUpgrade, $newPkgVer, $requiredBy) {
    if ($this->allowMultipleVersions) {
      // conflicts aren't possible
      return false;
    }
    
    // is this installed?
    if (isset($this->installedList[$packageName])) {
      $oldPkgVer = $this->installedList[$packageName]->newest();

      // fall through if it's being removed
      if (!$plan->is_on_remove_list($oldPkgVer)) {
        // if we can't upgrade, it's a conflict
        if (!$canUpgrade)
          return true;

        // any upgrade must meet the requirements of the current and planned system
        if ($this->will_meet_requirements($plan, $oldPkgVer, $newPkgVer)) {
          // okay, do the upgrade
          $this->upgrade_package($plan, $oldPkgVer, $newPkgVer, $packageName, $version, $requiredBy);
          return false;
        } else {
          return true;
        }
      }
    }
    
    // not a part of the install, so determined by the plan
    return $this->plan_will_conflict($plan, $packageName, $version, $newPkgVer, $requiredBy);
  }

  /**
   * Determine if there is a planned change which will conflict with
   * adding the given package.
   *
   * @param CriticalI_ChangeManager_Plan $plan The plan to add the package to
   * @param string $packageName The name of the package to add
   * @param string $version The version specification for the package
   * @param CriticalI_Package_Version $newPkgVer The new version object to install
   * @param CriticalI_Package_Version $requiredBy The package requiring the addition for dependency tracking
   *
   * @return boolean
   */
  protected function plan_will_conflict($plan, $packageName, $version, $newPkgVer, $requiredBy) {
    // see if the plan has the same package
    if ($plan->is_package_on_add_list($packageName)) {
      // see if we can replace the planned version with this one
      $oldPkgVer = $plan->package_on_add_list($packageName);

      // it must meet the requirements of the current system
      // and the requirements of the planned changes
      if ($this->will_meet_requirements($plan, $oldPkgVer, $newPkgVer)) {
        // make the substitution
        $this->upgrade_planned_package($plan, $oldPkgVer, $newPkgVer, $packageName, $version, $requiredBy);
        return false;
      } else {
        return true;
      }
    }
    
    return false;
  }
  
  /**
   * Test if a project substitution will meet the requirements of the
   * current and planned system.
   *
   * @param CriticalI_ChangeManager_Plan $plan The plan being constructed
   * @param CriticalI_Package_Version $oldPkgVer The package to remove
   * @param CriticalI_Package_Version $newPkgVer The package to replace it with
   *
   * @return boolean
   */
  protected function will_meet_requirements($plan, $oldPkgVer, $newPkgVer) {
    $depends = $this->dependents_list();
    if (isset($depends[$oldPkgVer->package()->name()])) {
      $requirements = $depends[$oldPkgVer->package()->name()];
      
      foreach ($requirements as $req) {
        if ( ($req['version'] === $oldPkgVer) || ($plan->is_on_remove_list($req['version'])) )
          continue;
        
        $spec = CriticalI_Package_Version::canonify_version_specification($req['requirement']);
        if ($newPkgVer->compare_version_specification($spec) != 0)
          return false;
      }
    }
    
    if ($plan->was_substituted($newPkgVer))
      return false;
    
    return $plan->will_meet_requirements($oldPkgVer, $newPkgVer);
  }
  
  /**
   * Called internally when upgrading an installed package to meet requirements
   *
   * @param CriticalI_ChangeManager_Plan $plan The plan to add the package to
   * @param CriticalI_Package_Version $oldPkgVer The package being upgraded
   * @param CriticalI_Package_Version $newPkgVer The new version object to install
   * @param string $packageName The name of the package to add
   * @param string $version The version specification for the package
   */
  protected function upgrade_package($plan, $oldPkgVer, $newPkgVer, $packageName, $version, $requiredBy) {
          $plan->remove_package($oldPkgVer);
          $plan->add_package($newPkgVer, $packageName, $version, $requiredBy);
  }
  
  /**
   * Called internally when replacing a planned package installation to meet requirements
   *
   * @param CriticalI_ChangeManager_Plan $plan The plan to add the package to
   * @param CriticalI_Package_Version $oldPkgVer The package being upgraded
   * @param CriticalI_Package_Version $newPkgVer The new version object to install
   * @param string $packageName The name of the package to add
   * @param string $version The version specification for the package
   */
  protected function upgrade_planned_package($plan, $oldPkgVer, $newPkgVer, $packageName, $version, $requiredBy) {
    $plan->replace_planned_package($oldPkgVer, $newPkgVer, $packageName, $version, $requiredBy);
  }
  
}

?>