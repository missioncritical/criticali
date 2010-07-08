<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Represents a project directory set up for managing with criticali.
 */
class CriticalI_Project {
  const INSIDE_PUBLIC = 0;
  const OUTSIDE_PUBLIC = 1;
  
  protected $directory;
  protected $type;
  protected $properties;
  protected $addStack;
  protected $statusListener;
  
  /**
   * Constructor
   * @param string $dir  Project root directory
   */
  public function __construct($dir) {
    $this->directory = $dir;
    
    // determine the project type
    if (file_exists($this->directory.'/private/vendor/.packages'))
      $this->type = self::INSIDE_PUBLIC;
    elseif (file_exists($this->directory.'/vendor/.packages'))
      $this->type = self::OUTSIDE_PUBLIC;
    else
      throw new Exception("The directory ".$this->directory." does not appear to be a ".
                          "criticali project.  Please be sure it has been initialized with ".
                          "'criticali project-init'.");
    
    $filename = $this->private_directory() . '/vendor/.packages';
    $this->properties = CriticalI_ConfigFile::read($filename);
    
    $this->addStack = array();
  }
  
  /**
   * Returns the project's directory
   */
  public function directory() {
    return $this->directory;
  }
  
  /**
   * Returns the project's private directory.
   * 
   * For outside public projects, that's the root. For inside public projects,
   * that's a directory called "private".
   */
  public function private_directory() {
    if ($this->type == self::INSIDE_PUBLIC) {
      return $this->directory . '/private';
    } else {
      return $this->directory;
    }
  }
  
  /**
   * Returns the project's public directory.
   * 
   * For outside public projects, that's a directory called "public".  For
   * inside public projects, that's the root.
   */
  public function public_directory() {
    if ($this->type == self::INSIDE_PUBLIC) {
      return $this->directory;
    } else {
      return $this->directory . '/public';
    }
  }

  /**
   * Returns the project's type
   */
  public function type() {
    return $this->type;
  }
  
  /**
   * Return the status listener
   */
  public function status_listener() {
    return $this->statusListener;
  }

  /**
   * Set the status listener
   */
  public function set_status_listener($listener) {
    $this->statusListener = $listener;
  }

  /**
   * Write the properties file out
   */
  protected function write_properties() {
    $filename = $this->private_directory() . '/vendor/.packages';
    CriticalI_ConfigFile::write($filename, $this->properties);
  }
  
  /**
   * Return the value of a property for this project
   *
   * @param string $name The name of the property to retrieve
   * @param mixed  $default The default value for the property if not found
   * @return mixed
   */
  public function property($name, $default = null) {
    return isset($this->properties[$name]) ? $this->properties[$name] : $default;
  }
  
  /**
   * Return a list of installed packages and their versions.  Data is
   * returned as an array:
   * <code>
   *   array(
   *     'activerecord' => '0.2.0',
   *     'log4php'      => '0.1.0',
   *     'support'      => '0.2.0'
   *   );
   * </code>
   */
  public function packages() {
    return $this->property('packages', array());
  }
  
  /**
   * Test to see if a package is already installed
   *
   * @param mixed $pkg  A package name, CriticalI_Package, or CriticalI_Package_Version
   * @param string $ver An optional version specification
   */
  public function is_installed($pkg, $ver = null) {
    $pkgs = $this->packages();
    
    if ($pkg instanceof Vuluture_Package_Version) {
      if (!isset($pkgs[$pkg->package()->name()]))
        return false;
      $verNum = $pkgs[$pkg->package()->name()];
      return ($pkg->compare_version_number(CriticalI_Package_Version::canonify_version($verNum)) == 0);
      
    } elseif ($pkg instanceof CriticalI_Package) {
      if (!isset($pkgs[$pkg->name()]))
        return false;
      if (empty($ver))
        return true;
      // dummy version object
      $verParts = CriticalI_Package_Version::canonify_version($pkgs[$pkg->name()]);
      $verObj = new CriticalI_Package_Version(null, $verParts[0], $verParts[1], $verParts[2], '.');
      $spec = CriticalI_Package_Version::canonify_version_specification($ver);
      return ($verObj->compare_version_specification($spec) == 0);
      
    } else {
      if (!isset($pkgs[$pkg]))
        return false;
      if (empty($ver))
        return true;
      // dummy version object
      $verParts = CriticalI_Package_Version::canonify_version($pkgs[$pkg]);
      $verObj = new CriticalI_Package_Version(null, $verParts[0], $verParts[1], $verParts[2], '.');
      $spec = CriticalI_Package_Version::canonify_version_specification($ver);
      return ($verObj->compare_version_specification($spec) == 0);

    }
  }

  /**
   * Add a new package to the project.
   *
   * By default this evaluates dependencies and adds any additional
   * packages required by the new package first.
   *
   * @param CriticalI_Package_Version $pkg  The package to add
   * @param boolean $evalDepends Whether or not to evaluate dependencies
   */
  public function add($pkg, $evalDepends = true) {
    // can't install a package that already exists
    if ($this->is_installed($pkg->package()->name()))
      throw new CriticalI_Project_AlreadyInstalledError($pkg->package()->name());
    
    $install = new CriticalI_Project_InstallOperation($this, $pkg);
    
    // handle dependencies first
    if ($evalDepends) {
      $packages = CriticalI_Package_List::get();
      $this->addStack[] = $pkg;
      
      try {
        $this->add_dependencies($pkg, $install);
      } catch (Exception $e) {
        array_pop($this->addStack);
        throw $e;
      }
      
      array_pop($this->addStack);
    }
    
    if ($this->statusListener)
      $this->statusListener->info($this, $pkg, "Installing ".$pkg->package()->name());
    
    // install the files
    $pkgDir = $GLOBALS['CRITICALI_ROOT'] . '/' . $pkg->installation_directory();
    $bases = $pkg->property('library.install.from', CriticalI_Defaults::LIBRARY_INSTALL_FROM);
    foreach (explode(',', $bases) as $base) {
      $dir = $pkgDir . '/' . trim($base);
      $matches = CriticalI_Globber::match($dir,
        $pkg->property('library.install.glob', CriticalI_Defaults::LIBRARY_INSTALL_GLOB));
      
      $dest = ($this->type == self::INSIDE_PUBLIC) ? 'private/vendor' : 'vendor';
      
      try {
        foreach ($matches as $file) {
          $destFile = substr($file, strlen($dir));
          $install->copy($file, $dest . '/' . $destFile);
        }
      } catch (Exception $e) {
        $install->abort();
        throw $e;
      }
      
    }

    // register any init files
    $initFiles = CriticalI_Globber::match($pkgDir,
      $pkg->property('init.hooks', CriticalI_Defaults::INIT_HOOKS));
    $vendor = $this->private_directory() . '/vendor';
    foreach ($initFiles as $fullPath) {
      $initClass = CriticalI_ClassUtils::class_name($fullPath, $pkgDir);
      $file = CriticalI_ClassUtils::file_name($initClass);
      if (file_exists("$vendor/$file")) {
        if (isset($this->properties['init_files']) && (strlen($this->properties['init_files']) > 0))
          $this->properties['init_files'] .= ",$file";
        else
          $this->properties['init_files'] = $file;
      }
    }
    
    // allow the package to do any custom work
    $matches = CriticalI_Globber::match($pkgDir,
      $pkg->property('project.install.hooks', CriticalI_Defaults::PROJECT_INSTALL_HOOKS));
    foreach ($matches as $hookFile) {
      try {
        $hookClass = CriticalI_ClassUtils::class_name($hookFile, $pkgDir);
        include_once($hookFile);
        if (!class_exists($hookClass, false))
          throw new Exception("File \"$hookFile\" did not declare class \"$hookClass\".");
          
        $hookInst = new $hookClass();
        if (!($hookInst instanceof CriticalI_Project_InstallHook))
          throw new Exception("$hookClass does not implement CriticalI_Project_InstallHook.");
        
        $hookInst->install($pkg, $this, $install);
      } catch (Exception $e) {
        trigger_error("Skipping installation hook \"$hookFile\" due to error: " .
          $e->getMessage(), E_USER_WARNING);
      }
    }
    
    // set the new properties for the package
    // entries in the depends on list
    if (strlen($install->dependency_string()) > 0) {
      if (!isset($this->properties['depends_on'])) $this->properties['depends_on'] = array();
      $this->properties['depends_on'][$pkg->package()->name()] = $install->dependency_string();
    
      // entries in the dependents list
      $this->add_as_dependent($pkg->package()->name(), $install->dependency_string());
    }
    
    // and the package itself
    if (!isset($this->properties['packages'])) $this->properties['packages'] = array();
    $this->properties['packages'][$pkg->package()->name()] = $pkg->version_string();
    
    if (!isset($this->properties['manifests'])) $this->properties['manifests'] = array();
    $this->properties['manifests'][$pkg->package()->name()] = serialize($install->file_list());
    
    // register any uninstallers
    $uninstallers = array();
    $matches = CriticalI_Globber::match($pkgDir,
      $pkg->property('project.uninstall.hooks', CriticalI_Defaults::PROJECT_UNINSTALL_HOOKS));
    foreach ($matches as $hookFile) {
      $hookClass = CriticalI_ClassUtils::class_name($hookFile, $pkgDir);
      if (!empty($hookClass)) $uninstallers[] = $hookClass;
    }
    if ($uninstallers) {
      if (!isset($this->properties['uninstallers'])) $this->properties['uninstallers'] = array();
      $this->properties['uninstallers'][$pkg->package()->name()] = implode(',', $uninstallers);
    }

    // write the properties
    $this->write_properties();
  }
  
  /**
   * Adds dependencies for a package
   *
   * @param CriticalI_Package_Version $pkg  The package to add
   * @param CriticalI_Project_InstallOperation $install The install operation for the package being added
   */
  protected function add_dependencies($pkg, $install) {
    $depends = $pkg->property('dependencies', array());
    foreach ($depends as $name=>$version) {
      $install->add_dependency_item($name, $version);
    
      if ($this->is_installed($name, $version))
        continue;
      $projectPackages = $this->packages();
      if (isset($projectPackages[$pkg->package()->name()]))
        throw new CriticalI_Project_ConflictingDependencyError($name, $version, $projectPackages[$pkg]);
        
      // infinite recursion is bad, so see if we're already installing this
      $found = false;
      $spec = CriticalI_Package_Version::canonify_version_specification($version);
      foreach ($this->addStack as $queued) {
        if (($queued->package()->name() == $name) &&
            ($queued->compare_version_specification($spec) == 0)) {
          $found = true;
          break;
        }
      }
      if ($found)
        continue;
    
      // get the package
      $packages = CriticalI_Package_List::get();
      if (!isset($packages[$name]))
        throw new CriticalI_Project_MissingDependencyError($name, $version);
      $otherPkg = $packages[$name];
      $otherVer = $otherPkg->satisfy_dependency($version);
      if (!$otherVer)
        throw new CriticalI_Project_MissingDependencyError($name, $version);
      
      // install it
      $this->add($otherVer, true);
    }
  }
  
  /**
   * Add a package name as a dependent of one or more other packages
   *
   * @param string $dependentName  The name of the dependent
   * @param string $dependsOnList  A list of packages it's a dependent for (as returned by dependency_string() from the install object)
   */
  protected function add_as_dependent($dependentName, $dependsOnList) {
    if (!isset($this->properties['dependents'])) $this->properties['dependents'] = array();
    foreach (explode(',', $dependsOnList) as $info) {
      list ($name, $ver) = explode('=', $info, 2);
      if (!isset($this->properties['dependents'][$name]))
        $items = array();
      else
        $items = explode(',', $this->properties['dependents'][$name]);
      if (!in_array($dependentName, $items))
        $items[] = $dependentName;
      $this->properties['dependents'][$name] = implode(',', $items);
    }
  }

  /**
   * Remove a package name as a dependent of one or more other packages
   *
   * @param string $dependentName  The name of the dependent
   * @param string $dependsOnList  A list of packages it's a dependent for (as returned by dependency_string() from the install object)
   */
  protected function remove_as_dependent($dependentName, $dependsOnList) {
    if (!isset($this->properties['dependents'])) return;
    foreach (explode(',', $dependsOnList) as $info) {
      list ($name, $ver) = explode('=', $info, 2);
      if (!isset($this->properties['dependents'][$name]))
        continue;
      $items = explode(',', $this->properties['dependents'][$name]);
      $finalItems = array();
      foreach ($items as $aDependent) { if ($aDependent != $dependentName) $finalItems[] = $aDependent; }
      if ($finalItems)
        $this->properties['dependents'][$name] = implode(',', $finalItems);
      else
        unset($this->properties['dependents'][$name]);
    }
  }

  /**
   * Remove a package from the project.
   *
   * By default this evaluates dependencies and will fail (throws a
   * CriticalI_Project_ExistingDependencyError) if any other installed
   * package depends on the one to remove.
   *
   * @param string $pkgName The name of the page to remove
   * @param boolean $evalDepends Whether or not to evaluate dependencies
   */
  public function remove($pkg, $evalDepends = true) {
    // the package has to be installed in order to remove it
    if (!$this->is_installed($pkg))
      throw new CriticalI_Project_NotInstalledError($pkg);
    
    // check any dependencies
    if ($evalDepends) {
      $dependents = $this->get_dependents($pkg);
      if ($dependents)
        throw new CriticalI_Project_ExistingDependencyError($pkg, implode(', ', $dependents));
    }
    
    $manifest = (isset($this->properties['manifests']) &&
                 isset($this->properties['manifests'][$pkg])) ?
                unserialize($this->properties['manifests'][$pkg]) : array();
    
    // run any uninstallers
    $this->run_uninstallers_for($pkg);
    
    // remove the files
    $this->remove_files($manifest);
    
    // remove this from the dependency lists
    if (isset($this->properties['depends_on']) &&
        isset($this->properties['depends_on'][$pkg])) {
      $this->remove_as_dependent($pkg, $this->properties['depends_on'][$pkg]);
      unset($this->properties['depends_on'][$pkg]);
    }
    
    // clean up remaining package properties
    if (isset($this->properties['uninstallers']) &&
        isset($this->properties['uninstallers'][$pkg]))
      unset($this->properties['uninstallers'][$pkg]);

    if (isset($this->properties['manifests']) &&
        isset($this->properties['manifests'][$pkg]))
      unset($this->properties['manifests'][$pkg]);

    if (isset($this->properties['packages']) &&
        isset($this->properties['packages'][$pkg]))
      unset($this->properties['packages'][$pkg]);
      
    // clean up init scripts
    if (isset($this->properties['init_files'])) {
      $inits = explode(',', $this->properties['init_files']);
      foreach ($manifest as $file) {
        if (($pos = $this->init_list_search($file, $inits)) !== false)
          array_splice($inits, $pos, 1);
      }
      $this->properties['init_files'] = implode(',', $inits);
    }
    
    // write the updated properties
    $this->write_properties();
  }
  
  /**
   * Return a list of package names that depend on the named package.
   * Returns an empty array if there are no dependents.
   *
   * @param string $name  The package to return the dependents for
   * @return array
   */
  public function get_dependents($pkg) {
    if (!isset($this->properties['dependents']))
      return array();
    if (!isset($this->properties['dependents'][$pkg]))
      return array();
    return explode(',', $this->properties['dependents'][$pkg]);
  }
  
  /**
   * Run any registered uninstallers for a package
   *
   * @param string $name  The package to run uninstallers for
   */
  protected function run_uninstallers_for($name) {
    if ( (!isset($this->properties['uninstallers'])) ||
         (!isset($this->properties['uninstallers'][$name])) )
      return;
    
    $classes = explode(',', $this->properties['uninstallers'][$name]);
    
    foreach ($classes as $className) {
      $path = $this->private_directory() . '/vendor/' . implode('/', explode('_', $className)) . '.php';
        
      try {
        if (!file_exists($path))
          throw new Exception("Class not found.");
        include_once($path);
        if (!class_exists($className, false))
          throw new Exception("File $path did not declare class $className.");
        
        $uninstaller = new $className();
        if (!($uninstaller instanceof CriticalI_Project_UninstallHook))
          throw new Exception("$className is not an instance of CriticalI_Project_UninstallHook.");
        
        $uninstaller->uninstall($this, $name);
        
      } catch (Exception $e) {
        trigger_error("Skipping uninstaller $className due to error: ".$e->getMessage(), E_USER_WARNING);
      }
      
    }
  }
  
  /**
   * Remove all of the files in a listing.  Any directories in the
   * listing are removed if they are empty after first removing all other
   * files in the list.
   *
   * @param array $manifest List of files to remove
   */
  protected function remove_files($manifest) {
    $projectPrefix = $this->directory() . '/';
    
    // remove added files
    $dirs = array();
    foreach ($manifest as $file) {
      $fullname = $this->is_absolute($file) ? $file : ($projectPrefix . $file);
      if (is_dir($fullname))
        $dirs[] = $fullname;
      elseif (file_exists($fullname))
        unlink($fullname);
    }
    
    rsort($dirs);
    foreach ($dirs as $dir) {
      if ($this->directory_entry_count($dir) === 0)
        rmdir($dir);
    }
  }

  /**
   * Test a path to see if it is absolute
   *
   * @param string $path  The path to test
   */
  protected function is_absolute($path) {
    return (preg_match("/^(?:[a-zA-Z]:)?[\\/\\\\]/", $path) > 0);
  }
  
  /**
   * Count the number of entries in a directory (not counting self and
   * parent)
   *
   * @param string $dir  The directory to check
   * @return int Count of entries or false on error
   */
  protected function directory_entry_count($dir) {
    $dh = opendir($dir);
    if ($dh === false) return false;
    
    $count = 0;
    while (($entry = readdir($dh)) !== false) {
      if ($entry != '.' && $entry != '..')
        $count++;
    }
    
    closedir($dh);
    
    return $count;
  }
  
  /**
   * Search an array of init files for a matching manifest entry
   *
   * @param string $needle   The manifest entry to look for
   * @param array  $haystack The list of init files to search
   *
   * @return mixed  The corresponding key for the file in the array or false if not found
   */
  protected function init_list_search($needle, $haystack) {
    $prefix = $this->type() == self::INSIDE_PUBLIC ? 'private/vendor' : 'vendor';
    foreach ($haystack as $key=>$item) {
      if ("$prefix/$item" == $needle)
        return $key;
    }
    return false;
  }

  /**
   * Upgrade a package in the project.
   *
   * By default this evaluates dependencies and will add or upgrade any
   * needed dependencies of the package, but will fail (throws a
   * CriticalI_Project_ExistingDependencyError) if any other installed
   * package depends on the one to upgrade and cannot be upgraded to work
   * with the new version.
   *
   * @param string $oldPkgName The name of the page to uprade
   * @param CriticalI_Package_Version $newPkg The version to upgrade to
   * @param boolean $evalDepends Whether or not to evaluate dependencies
   */
  public function upgrade($oldPkgName, $newPkg, $evalDepends = true) {
  }
}

?>