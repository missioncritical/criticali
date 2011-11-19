<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * CriticalI_Package_List is the collection of installed packages.  It is a
 * singleton whose instance can be obtained by calling the static
 * function list().  The actual instance behaves like many other objects
 * in the system in that it is a first class object that behaves like an
 * array.  Packages are keyed in the list by their name.
 */
class CriticalI_Package_List implements IteratorAggregate, ArrayAccess {
  protected static $list = null;
  
  protected $packages;
  protected $packageInfo;
  
  /**
   * Constructor.
   *
   * This class may not be directly instantiated.
   */
  protected function __construct($load = true) {
    $this->packages = array();
    
    if (!$load) {
      $this->packageInfo = array('commands'=>array());
      return;
    }
    
    CriticalI_RepositoryLock::read_lock();
    $this->packageInfo = CriticalI_ConfigFile::read("$GLOBALS[CRITICALI_ROOT]/.packages");
    
    foreach($this->packageInfo['packages'] as $package=>$version) {
      $this->packages[$package] = new CriticalI_Package($package, $version,
                                                      $this->packageInfo['directories']);
    }
  }
  
  /**
   * Returns the shared list instance
   * @return CriticalI_Package_List
   */
  public static function get() {
    if (!self::$list)
      self::$list = new CriticalI_Package_List();
    return self::$list;
  }
  
  /**
   * Forces CriticalI_Package_List to skip loading of packages and present
   * an empty list for subsequent class to get.
   */
  public static function clear() {
    self::$list = new CriticalI_Package_List(false);
  }
  
  /**
   * Rebuild the installation list file and repopulate the list
   */
  public static function rebuild() {
    global $CRITICALI_ROOT;
    CriticalI_RepositoryLock::write_lock();
    
    $data = array('packages'=>array(), 'directories'=>array(), 'commands'=>array());
    
    $dh = opendir($CRITICALI_ROOT);
    if ($dh === false)
      throw new Exception("Cannot access criticali root directory \"$CRITICALI_ROOT\"");
    
    while (($filename = readdir($dh)) !== false) {
      try {
        if ($filename == '.' || $filename == '..') continue;
        $path = "$CRITICALI_ROOT/$filename";
        if (!is_dir($path)) continue;
        if (!file_exists("$path/package.ini")) continue;
        
        $pkg = new CriticalI_Package_Directory($filename, $path);
        $name = $pkg->name();
        $version = $pkg->version();
      
        if (isset($data['packages'][$name]))
            $data['packages'][$name] = self::add_version_to_list($data['packages'][$name], $version);
        else
          $data['packages'][$name] = $version;
        $data['directories']["$name-$version"] = $filename;
        if ($pkg->has_commands()) {
          if (isset($data['commands'][$name]))
            $data['commands'][$name] = self::add_version_to_list($data['commands'][$name], $version);
          else
            $data['commands'][$name] = $version;
        }
        
      } catch (Exception $e) {
        fwrite(STDERR, "Caught exception ".get_class($e).": ".$e->getMessage()."\nIgnoring package \"$filename\".");
      }
    }
    
    closedir($dh);
    
    CriticalI_ConfigFile::write("$CRITICALI_ROOT/.packages", $data);
    
    self::$list = new CriticalI_Package_List();
  }
  
  /**
   * Convenience method for including a given package and its
   * dependencies in the runtime autoload directory list.  The package
   * must be installed for this to work (otherwise an exception will
   * result).
   *
   * @param string $package  The name of the package to add
   * @param string $version  Optional version specification (same as for dependencies)
   */
  public static function add_package_to_autoloader($package, $version = null) {
    $version = empty($version) ? '*' : $version;
    $list = self::get();
    if (!isset($list[$package]))
      throw new CriticalI_MissingPackageError($package);

    $ver = $list[$package]->satisfy_dependency($version);
    if (!$ver)
      throw new CriticalI_MissingPackageVersionError($package, $version);
    
    $path = $GLOBALS['CRITICALI_ROOT'] . '/' . $ver->installation_directory() . '/lib';
    if (in_array($path, $GLOBALS['CRITICALI_SEARCH_DIRECTORIES']))
      return; // already included
    
    $GLOBALS['CRITICALI_SEARCH_DIRECTORIES'][] = $path;
    $GLOBALS['INCLUDE_PATH'] .= $GLOBALS['PATH_SEPARATOR'] . $path;
    ini_set('include_path', $GLOBALS['INCLUDE_PATH']);
    
    // process dependencies
    foreach ($ver->property('dependencies', array()) as $depPkg=>$depVer) {
      self::add_package_to_autoloader($depPkg, $depVer);
    }
  }
  
  /**
   * Perform the set of operations prescribed by a
   * CriticalI_ChangeManager_Plan
   *
   * @param CriticalI_ChangeManager_Plan $plan The plan to perform
   */
  public static function perform($plan) {
    $sysRemove = false;
    $sysAdd = false;
    
    // remove any requested packages
    foreach ($plan->remove_list() as $pkg) {
      // test for system upgrade
      if ($pkg->package()->name() == 'criticali')
        $sysRemove = $pkg;
      else
        self::remove($pkg);
    }
    
    // add any requested packages
    foreach ($plan->add_list() as $pkg) {
      // test for system upgrade
      if ($pkg->package()->name() == 'criticali')
        $sysAdd = $pkg;
      else
        self::add($pkg->wrapper());
    }
    
    // last step is any needed system upgrade
    if ($sysRemove && $sysAdd)
      self::system_upgrade($sysAdd, $sysRemove);
    elseif ($sysRemove || $sysAdd)
      fwrite(STDERR, "Ignored package criticali. The system package may " .
        "not be directly added or removed.\n");
  }

  /**
   * Add (install) a new wrapped package to the repository
   *
   * This is a low level method that does not perform any dependency
   * checking. For higher level functionality and validation, construct a
   * CriticalI_ChangeManager_Plan and pass it to the perform() method.
   *
   * @param CriticalI_Package_Wrapper $wrappedPackage The package to install
   */
  public static function add($wrappedPackage) {
    CriticalI_RepositoryLock::write_lock();

    // get the package name
    $name = $wrappedPackage->package_name();

    // and version
    $version = $wrappedPackage->package_version();

    // determine the destination directory
    $destination = $GLOBALS['CRITICALI_ROOT'] . '/' . $name . '-' . $version;
    if (file_exists($destination))
      throw new Exception("Directory $destination already exists.");
    
    // cannot add another core system
    if ($name == 'criticali')
      throw new Exception("Addition of another criticali system package not allowed");

    // the exact same version cannot be installed twice
    $installed = self::get();
    if (isset($installed[$name]) && isset($installed[$name][$version]))
      throw new Exception("Package $name version $version is already installed.");
    
    // create the directory
    if (!mkdir($destination, 0777))
      throw new Exception("Failed to create directory $destination");
    
    // unload it
    $wrappedPackage->unwrap($destination);
    
    // update the stored packages file
    self::add_version_to_packages_file(new CriticalI_Package_Directory("$name-$version", $destination),
      "$name-$version");
    
    // invalidate the list
    self::$list = false;
  }
  
  /**
   * Remove (uninstall) a package version from the repository
   *
   * This is a low level method that does not perform any dependency
   * checking. For higher level functionality and validation, construct a
   * CriticalI_ChangeManager_Plan and pass it to the perform() method.
   *
   * @param CriticalI_Package_Version $packageVersion The package to remove
   */
  public static function remove($packageVersion) {
    CriticalI_RepositoryLock::write_lock();

    // get the package name
    $name = $packageVersion->package()->name();

    // and version
    $version = $packageVersion->version_string();

    // determine the directory where it is installed
    $directory = $GLOBALS['CRITICALI_ROOT'] . '/' .$packageVersion->installation_directory();
    if (!is_dir($directory))
      throw new Exception("Directory $directory does not exist.");
    
    // cannot remove the core system
    if ($name == 'criticali' || $packageVersion->installation_directory() == 'Core')
      throw new Exception("Removal of criticali system package not allowed");
    
    // remove the directory
    self::delete_all_and_remove_directory($directory);
    
    // update the stored packages file
    self::remove_version_from_packages_file($name, $version);
    
    // invalidate the list
    self::$list = false;
  }

  /**
   * Upgrade the criticali system to a new wrapped package
   *
   * This is a low level method that does not perform any dependency
   * checking. For higher level functionality and validation, construct a
   * CriticalI_ChangeManager_Plan and pass it to the perform() method.
   *
   * @param CriticalI_Package_Wrapper $to The system package to install
   * @param CriticalI_Package_Version $from The system package to remove
   */
  public static function system_upgrade($to, $from) {
    CriticalI_RepositoryLock::write_lock();

    // some sanity checking
    if ($to->package()->name() != 'criticali')
      throw new Exception("Can only upgrade the system to a version of criticali");

    if ($from->installation_directory() != 'Core')
      throw new Exception("The criticali system to upgrade must be installed in a directory named 'Core'");

    // get the version we're going to
    $version = $to->version_string();

    // begin as a normal install to a temporary directory
    
    // determine the temporary directory
    $ROOT = self::clean_root_directory();
    $destination = $ROOT . '/criticali-' . $version;
    if (file_exists($destination))
      throw new Exception("Directory $destination already exists.");
    
    // create the directory
    if (!mkdir($destination, 0777))
      throw new Exception("Failed to create directory $destination");
    
    // unload it
    $to->wrapper()->unwrap($destination);
    
    // now, swap the directories
    $oldDir = $ROOT . '/criticali-' . $from->version_string() .'.bak';
    if (!rename($ROOT . '/Core', $oldDir))
      throw new Exception("Could not move currently installed system to $oldDir");
    if (!rename($destination, $ROOT . '/Core'))
      throw new Exception("Could not move new system to Core");
    
    // clean up the old files
    self::delete_all_and_remove_directory($oldDir);
    
    // update the stored packages file
    self::update_system_in_packages_file($from->version_string(), $version);
    
    // invalidate the list
    self::$list = false;
  }
 
  /**
   * Update the installation list file with a single added package directory
   * @param CriticalI_Package_Directory $pkg The newly added directory
   * @param string $basedir The bare directory name (no path) where it was installed
   */
  protected static function add_version_to_packages_file($pkg, $basedir) {
    global $CRITICALI_ROOT;
    CriticalI_RepositoryLock::write_lock();
    
    $data = CriticalI_ConfigFile::read("$CRITICALI_ROOT/.packages");
    
    $name = $pkg->name();
    $version = $pkg->version();
    
    if (isset($data['packages'][$name]))
        $data['packages'][$name] = self::add_version_to_list($data['packages'][$name], $version);
    else
      $data['packages'][$name] = $version;

    $data['directories']["$name-$version"] = $basedir;

    if ($pkg->has_commands()) {
      if (isset($data['commands'][$name]))
        $data['commands'][$name] = self::add_version_to_list($data['commands'][$name], $version);
      else
        $data['commands'][$name] = $version;
    }
    
    CriticalI_ConfigFile::write("$CRITICALI_ROOT/.packages", $data);
  }

  /**
   * Update the installation list file by removing a single deleted package directory
   * @param string $name The package name
   * @param string $version The version name
   */
  protected static function remove_version_from_packages_file($name, $version) {
    global $CRITICALI_ROOT;
    CriticalI_RepositoryLock::write_lock();
    
    $data = CriticalI_ConfigFile::read("$CRITICALI_ROOT/.packages");
    
    if (isset($data['packages'][$name])) {
      $data['packages'][$name] = self::remove_from_version_list($data['packages'][$name], $version);
      if (!$data['packages'][$name])
        unset($data['packages'][$name]);
    }

    unset($data['directories']["$name-$version"]);
    
    if (isset($data['commands'][$name])) {
      $data['commands'][$name] = self::remove_from_version_list($data['commands'][$name], $version);
      if (!$data['commands'][$name])
        unset($data['commands'][$name]);
    }
    
    CriticalI_ConfigFile::write("$CRITICALI_ROOT/.packages", $data);
  }

  /**
   * Update the criticali system version listed in the package directory
   * @param string $oldVersion The old version
   * @param string $newVersion The new version
   */
  protected static function update_system_in_packages_file($oldVersion, $newVersion) {
    global $CRITICALI_ROOT;
    CriticalI_RepositoryLock::write_lock();
    
    $data = CriticalI_ConfigFile::read("$CRITICALI_ROOT/.packages");
    
    $data['packages']['criticali'] = $newVersion;
    
    if (isset($data['packages'][$name]))
        $data['packages'][$name] = self::add_version_to_list($data['packages'][$name], $version);
    else
      $data['packages'][$name] = $version;

    if (isset($data['directories'][$oldVersion]))
      unset($data['directories'][$oldVersion]);
    $data['directories']["criticali-$newVersion"] = 'Core';

    CriticalI_ConfigFile::write("$CRITICALI_ROOT/.packages", $data);
  }

  /**
   * Add a version to a version list string and maintain sort order
   *
   * @param string $list  The comma-separated list of versions
   * @param string $value The value to add
   */
  protected static function add_to_version_list($list, $value) {
    $items = explode(',', $list);
    $items[] = $value;
    usort($items, array('CriticalI_Package_Version', 'compare_version_strings'));
    return implode(',', $items);
  }
  
  /**
   * Remove a version from a version list string and maintain sort order
   *
   * @param string $list  The comma-separated list of versions
   * @param string $value The value to remove
   */
  protected static function remove_from_version_list($list, $value) {
    $items = explode(',', $list);
    $items[] = $value;
    
    while(($idx = array_search($value, $items)) !== false) {
      array_splice($items, $idx, 1);
    }
    
    // sort order unchanged
    return implode(',', $items);
  }

  /**
   * Equivalent to performing "rm -rf" on a directory
   * @param string $directory The directory to remove
   */
  protected static function delete_all_and_remove_directory($directory) {
    $dh = opendir($directory);
    if ($dh === false)
      throw new Exception("Could not access directory $directory");
    
    while (($fname = readdir($dh)) !== false) {
      if (($fname == '.') || ($fname == '..'))
        continue;
      
      if (is_dir("$directory/$fname"))
        self::delete_all_and_remove_directory("$directory/$fname");
      else {
        if (!unlink("$directory/$fname"))
          throw new Exception("Could not remove file $directory/$fname");
      }
    }
    
    closedir($dh);
    
    if (!rmdir($directory))
      throw new Exception("Could not remove directory $directory");
  }
  
  /**
   * Return a cleaned version of the global variable $CRITICALI_ROOT for
   * use in installations
   */
  protected static function clean_root_directory() {
    return preg_replace("/[\\\\\\/]+[^\\\\\\/]+[\\\\\\/]+..[\\\\\\/]*\\z/", '',
      $GLOBALS['CRITICALI_ROOT']);
  }

  /**
   * Return an iterator for the package list
   * @return ArrayIterator
   */
  public function getIterator() {
    return new ArrayIterator($this->packages);
  }
  
  /**
   * Tests for existence of an array index
   * @param string $idx  The index to test
   * @return boolean
   */
  public function offsetExists($idx) {
    return isset($this->packages[$idx]);
  }
  
  /**
   * Retrieves the package at an array index.
   * @param string $idx  The index to get
   * @return CriticalI_Package
   */
  public function offsetGet($idx) {
    return $this->packages[$idx];
  }
  
  /**
   * Sets the value at an array index
   * @param string $idx   The index to set
   * @param CriticalI_Package $value The value to set
   */
  public function offsetSet($idx, $value) {
    $this->packages[$idx] = $value;
  }
  
  /**
   * Deletes an entry at an array index
   * @param string $idx  The index to unset
   */
  public function offsetUnset($idx) {
    unset($this->packages[$idx]);
  }
  
  /**
   * Return the list of package versions that declare commands
   * @return array
   */
  public function commandVersions() {
    return $this->find_matching_versions($this->packageInfo['commands']);
  }
  
  /**
   * Searches the list of packages to assemble a collection of Versions
   * from an array of specs.
   */
  protected function find_matching_versions($specs) {
    $matches = array();
    
    foreach ($specs as $packageName=>$verString) {
      if (isset($this->packages[$packageName])) {
        $package = $this->packages[$packageName];
        $versions = explode(',', $verString);
        foreach ($versions as $versionNumber) {
          if (isset($package[$versionNumber]))
            $matches[] = $package[$versionNumber];
        }
      }
    }
    
    return $matches;
  }
  
}

?>