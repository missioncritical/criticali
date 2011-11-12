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
  protected $statusListener;
  protected $packageList;
  
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
    $this->packageList = null;
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
   * Return the list of installed packages as a
   * CriticalI_Project_PackageList (which contains CriticalI_Package
   * objects instead of just a hash of strings).
   *
   * @return CriticalI_Project_PackageList
   */
  public function package_list() {
    if (!$this->packageList)
      $this->packageList = new CriticalI_Project_PackageList($this);
    
    return $this->packageList;
  }
  
  /**
   * Test to see if a package is already installed
   *
   * @param mixed $pkg  A package name, CriticalI_Package, or CriticalI_Package_Version
   * @param string $ver An optional version specification
   */
  public function is_installed($pkg, $ver = null) {
    $list = $this->package_list();
    
    if ($pkg instanceof CriticalI_Package_Version) {
      if (!isset($list[$pkg->package()->name()]))
        return false;
      $found = $list[$pkg->package()->name()]->satisfy_dependency($pkg->version_string() . '!');

    } else {
      $name = ($pkg instanceof CriticalI_Package ? $pkg->name() : $pkg);
    
      if (!isset($list[$name]))
        return false;
      if (!$ver)
        return true;
    
      $found = $list[$name]->satisfy_dependency($ver);
    }
    
    return ($found ? true : false);
  }
  
  /**
   * Perform the set of operations prescribed by a
   * CriticalI_ChangeManager_Plan
   *
   * @param CriticalI_ChangeManager_Plan $plan The plan to perform
   */
  public function perform($plan) {
    // remove any requested packages
    foreach ($plan->remove_list() as $pkg) {
      $this->remove($pkg->package()->name());
    }
    
    // add any requested packages
    foreach ($plan->add_list() as $pkg) {
      $this->add($pkg);
    }
  }

  /**
   * Add a package to the project.
   *
   * This is a low-level method. It does not perform any error checking
   * or dependency resolution. Create a CriticalI_ChangeManager_Plan and
   * pass it to the perform method for higher level functionality.
   *
   * @param CriticalI_Package_Version $pkg  The package to add
   */
  public function add($pkg) {
    
    $install = new CriticalI_Project_InstallOperation($this, $pkg);
    
    if ($this->statusListener)
      $this->statusListener->info($this, $pkg, "Installing ".$pkg->package()->name());
    
    // install the files
    $this->install_files($install, $pkg);
    
    // set any property defaults
    $this->install_property_defaults($install, $pkg);

    // register any init files
    $this->install_init_files($pkg);
    
    // allow the package to do any custom work
    $this->run_installers_for($install, $pkg);
    
    // set the new properties for the package
    // entries in the depends on list
    $this->install_dependency_list($pkg);
    
    // and the package itself
    $this->install_package_in_list($install, $pkg);
    
    // register any uninstallers
    $this->install_uninstallers($pkg);

    // write the properties
    $this->write_properties();
  }
  
  /**
   * Remove a package from the project.
   *
   * This is a low-level method. It does not perform any error checking
   * or dependency resolution. Create a CriticalI_ChangeManager_Plan and
   * pass it to the perform method for higher level functionality.
   *
   * @param string $packageName The name of the package to remove
   */
  public function remove($packageName) {
    $packages = $this->package_list();

    // the package has to be installed in order to remove it
    if (!isset($packages[$packageName]))
      throw new CriticalI_Project_NotInstalledError($packageName);
    
    $pkg = $packages[$packageName]->newest();
    
    $manifest = unserialize( $pkg->property('manifest', serialize(array())) );
    
    if ($this->statusListener)
      $this->statusListener->info($this, $pkg, "Removing ".$pkg->package()->name());

    // run any uninstallers
    $this->run_uninstallers_for($pkg);
    
    // remove the files
    $this->uninstall_files($manifest);
    
    // remove this from the dependency lists
    $this->uninstall_dependency_list($packageName);
    
    // clean up remaining package properties
    $this->uninstall_package_in_list($packageName);
      
    // clean up init scripts
    $this->uninstall_init_script_listings($manifest);
    
    // write the updated properties
    $this->write_properties();
  }
  
  /**
   * Install the files for a package
   *
   * @param CriticalI_Project_InstallOperation $install  The install object
   * @param CriticalI_Package $pkg The package to install files for
   */
  protected function install_files($install, $pkg) {
    $pkgDir = $GLOBALS['CRITICALI_ROOT'] . '/' . $pkg->installation_directory();
    $dest = ($this->type == self::INSIDE_PUBLIC) ? 'private/vendor' : 'vendor';

    $bases = $pkg->property('library.install.from', CriticalI_Defaults::LIBRARY_INSTALL_FROM);

    foreach (explode(',', $bases) as $base) {
      $dir = $pkgDir . '/' . trim($base);
      $matches = CriticalI_Globber::match($dir,
        $pkg->property('library.install.glob', CriticalI_Defaults::LIBRARY_INSTALL_GLOB));
      
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

  }
  
  /**
   * Set any property defaults for a package
   *
   * @param CriticalI_Project_InstallOperation $install  The install object
   * @param CriticalI_Package $pkg The package to install defaults for
   */
  protected function install_property_defaults($install, $pkg) {
    $defaults = $pkg->property('config.defaults');
    if ($defaults) {
      foreach ($defaults as $prop=>$default) {
        $install->set_default_config_value($prop, $default);
      }
    }
  }

  /**
   * Add any specified classes to the init_files property for a package
   *
   * @param CriticalI_Package $pkg The package to add classes for
   */
  protected function install_init_files($pkg) {
    $pkgDir = $GLOBALS['CRITICALI_ROOT'] . '/' . $pkg->installation_directory();

    $initFiles = CriticalI_Globber::match($pkgDir,
      $pkg->property('init.hooks', CriticalI_Defaults::INIT_HOOKS));
    $vendor = $this->private_directory() . '/vendor';

    foreach ($initFiles as $fullPath) {
      $initClass = CriticalI_ClassUtils::class_name($fullPath, $pkgDir);
      $file = CriticalI_ClassUtils::file_name($initClass);
      if (file_exists("$vendor/$file"))
        $this->add_init_file($file);
    }
  }

  /**
   * Run any specified installers for a package
   *
   * @param CriticalI_Project_InstallOperation $install  The install object
   * @param CriticalI_Package $pkg The package to run installers for
   */
  protected function run_installers_for($install, $pkg) {
    $pkgDir = $GLOBALS['CRITICALI_ROOT'] . '/' . $pkg->installation_directory();

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
  }

  /**
   * Add dependencies for a newly installed package to this project's
   * properties
   *
   * @param CriticalI_Package $pkg The package to store dependencies for
   */
  protected function install_dependency_list($pkg) {
    $depends = $pkg->property('dependencies', array());
    if ($depends) {
      $items = array();
      foreach ($depends as $n=>$v) { $items[] = "$n=$v"; }
      
      if (!isset($this->properties['depends_on'])) $this->properties['depends_on'] = array();
      $this->properties['depends_on'][$pkg->package()->name()] = implode(',', $items);
    }
  }

  /**
   * Add the given package to our list of installed packages
   *
   * @param CriticalI_Project_InstallOperation $install  The install object
   * @param CriticalI_Package $pkg The package to add
   */
  protected function install_package_in_list($install, $pkg) {
    // package list
    if (!isset($this->properties['packages']))
      $this->properties['packages'] = array();
    $this->properties['packages'][$pkg->package()->name()] = $pkg->version_string();
    
    // add the manifest
    if (!isset($this->properties['manifests']))
      $this->properties['manifests'] = array();
    $this->properties['manifests'][$pkg->package()->name()] = serialize($install->file_list());

    // rebuild the package list
    $this->packageList = null;
  }
  
  /**
   * Add any specified classes to the uninstallers property for a package
   *
   * @param CriticalI_Package $pkg The package to add classes for
   */
  protected function install_uninstallers($pkg) {
    $pkgDir = $GLOBALS['CRITICALI_ROOT'] . '/' . $pkg->installation_directory();

    $uninstallers = array();

    $matches = CriticalI_Globber::match($pkgDir,
      $pkg->property('project.uninstall.hooks', CriticalI_Defaults::PROJECT_UNINSTALL_HOOKS));

    foreach ($matches as $hookFile) {
      $hookClass = CriticalI_ClassUtils::class_name($hookFile, $pkgDir);
      if (!empty($hookClass)) $uninstallers[] = $hookClass;
    }

    if ($uninstallers) {
      if (!isset($this->properties['uninstallers']))
        $this->properties['uninstallers'] = array();

      $this->properties['uninstallers'][$pkg->package()->name()] = implode(',', $uninstallers);
    }
  }

  /**
   * Run any registered uninstallers for a package
   *
   * @param CriticalI_Project_PackageVersion $pkg  The package to run uninstallers for
   */
  protected function run_uninstallers_for($pkg) {
    $classes = $pkg->property('uninstallers', array());
    
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
        
        $uninstaller->uninstall($this, $pkg->package()->name());
        
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
  protected function uninstall_files($manifest) {
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
   * Remove dependencies for a package from this project's properties
   *
   * @param string $packageName The package to remove dependencies for
   */
  protected function uninstall_dependency_list($packageName) {
    if (isset($this->properties['depends_on']) &&
        isset($this->properties['depends_on'][$packageName])) {
      unset($this->properties['depends_on'][$packageName]);
    }
  }

  /**
   * Remove the given package from our list of installed packages
   *
   * @param string $packageName The package to remove
   */
  protected function uninstall_package_in_list($packageName) {
    // clean the uninstallers property
    if (isset($this->properties['uninstallers']) &&
        isset($this->properties['uninstallers'][$packageName]))
      unset($this->properties['uninstallers'][$packageName]);

    // clean the manifests property
    if (isset($this->properties['manifests']) &&
        isset($this->properties['manifests'][$packageName]))
      unset($this->properties['manifests'][$packageName]);

    // clean the packages property
    if (isset($this->properties['packages']) &&
        isset($this->properties['packages'][$packageName]))
      unset($this->properties['packages'][$packageName]);
    
    // rebuild the package list
    $this->packageList = null;
  }

  /**
   * Remove any listings in the init_files property for the removed files
   * listed in $manifest
   *
   * @param array $manifest The manifest of recently removed files
   */
  protected function uninstall_init_script_listings($manifest) {
    $init_files = $this->property('init_files', false);
    $expanded = $init_files ? explode(',', $init_files) : array();

    $map = array();
    foreach ($expanded as $file) { $map[$file] = 1; }
    
    $regex = ($this->type == self::INSIDE_PUBLIC) ? "private[\\/\\\\]vendor" : 'vendor';
    
    foreach ($manifest as $file) {
      if (preg_match("/\\A{$regex}[\\/\\\\]+(.+)\\z/", $file, $matches)) {
        $shortFile = $matches[1];
        if (isset($map[$shortFile]))
          $this->remove_init_file($shortFile);
      }
    }
  }
  
  /**
   * Add a file to the list in the init_files property. This method also
   * prevents files from being added to the list more than once.
   *
   * @param string $file The file to add
   */
  protected function add_init_file($file) {
    $init_files = $this->property('init_files', false);
    
    $expanded = $init_files ? explode(',', $init_files) : array();
    
    if (array_search($file, $expanded) === false)
      $expanded[] = $file;
    
    $this->properties['init_files'] = implode(',', $expanded);
  }
  
  /**
   * Remove a file from the list in the init_files property.
   *
   * @param string $file The file to add
   */
  protected function remove_init_file($file) {
    $init_files = $this->property('init_files', false);
    
    $expanded = $init_files ? explode(',', $init_files) : array();
    
    while (($where = array_search($file, $expanded)) !== false) {
      array_splice($expanded, $where, 1);
    }
    
    $this->properties['init_files'] = implode(',', $expanded);
  }

}

?>