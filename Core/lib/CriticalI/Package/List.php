<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

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