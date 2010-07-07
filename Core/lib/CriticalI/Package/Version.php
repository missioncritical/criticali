<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * A Version is the most granular form of a package.  It represents a
 * single installed version.
 */
class CriticalI_Package_Version {
  protected $package;
  protected $major;
  protected $minor;
  protected $revision;
  protected $directory;
  protected $properties;
  
  /**
   * Constructor
   *
   * @param CriticalI_Package $package  The containing package
   * @param int $major Major version number
   * @param int $minor Minor version number
   * @param int $revision Revision number
   * @param string $dir Installation directory, relative to CRITICALI_ROOT
   */
  public function __construct($package, $major, $minor, $revision, $dir) {
    $this->package = $package;
    $this->major = $major;
    $this->minor = $minor;
    $this->revision = $revision;
    $this->directory = $dir;
    $this->properties = null;
  }
  
  /**
   * Return the containing package
   * @return CriticalI_Package
   */
  public function package() {
    return $this->package;
  }
  
  /**
   * Return the installation directory (relative to CRITICALI_ROOT)
   * @return string
   */
  public function installation_directory() {
    return $this->directory;
  }
  
  /**
   * Return the value of a property for this package version
   *
   * @param string $name The name of the property to retrieve
   * @param mixed  $default The default value for the property if not found
   * @return mixed
   */
  public function property($name, $default = null) {
    if (is_null($this->properties))
      $this->properties = CriticalI_ConfigFile::read("$GLOBALS[CRITICALI_ROOT]/".$this->directory."/package.ini");
    return isset($this->properties[$name]) ? $this->properties[$name] : $default;
  }
  
  /**
   * Return the version number as an array with three elements (major,
   * minor, and revision numbers).
   * @return array
   */
  public function version() {
    return array($this->major, $this->minor, $this->revision);
  }
  
  /**
   * Return the version number as a string.
   * @return string
   */
  public function version_string() {
    return $this->major.'.'.$this->minor.'.'.$this->revision;
  }
  
  /**
   * Compares a canonified version array to this version.  Returns -1, 0,
   * or -1 when this version is less than, equal, or greater than the
   * supplied version, respectively.
   * @param array $ver  The version number to compare
   * @return int
   */
  public function compare_version_number($ver) {
    if ($this->major == $ver[0]) {
      if ($this->minor == $ver[1]) {
        if ($this->revision == $ver[2])
          return 0;
        else
          return $this->revision < $ver[2] ? -1 : 1;
      } else {
        return $this->minor < $ver[1] ? -1 : 1;
      }
    } else {
      return $this->major < $ver[0] ? -1 : 1;
    }
  }
  
  /**
   * Compares a canonified version dependency specification to this
   * version.  Returns -1, 0, or -1 when this version is less than,
   * within, or greater than the specified range, respectively.
   *
   * @param CriticalI_Pacakge_VersionSpec $ver  The version specification to compare
   * @return int
   */
  public function compare_version_specification($ver) {
    if ($ver->any) return 0;
    
    if ($this->major == $ver->start[0]) {
      
      if ($this->minor == $ver->start[1]) {

        if ($this->revision == $ver->start[2]) {
          return 0;
        } else {
          if ($this->revision < $ver->start[2]) {
            return $ver->minus ? 0 : -1;
          } else {
            if ($ver->end) return ($this->compare_version_number($ver->end) <= 0 ? 0 : 1);
            return ($ver->exact || $ver->minus) ? 1 : 0;
          }
        }

      } else {
        if ($this->minor < $ver->start[1]) {
          return $ver->minus ? 0 : -1;
        } else {
          if ($ver->end) return ($this->compare_version_number($ver->end) <= 0 ? 0 : 1);
          return ($ver->exact || $ver->minus) ? 1 : 0;
        }
      }
      
    } else {
      if ($this->major < $ver->start[0]) {
        return $ver->minus ? 0 : -1;
      } else {
        if ($ver->end) return ($this->compare_version_number($ver->end) <= 0 ? 0 : 1);
        return $ver->plus ? 0 : 1;
      }
    }
  }

  /**
   * Utility function to return the canonical three-part version number
   * from a string
   * @param string $ver  The version string to canonify
   * @return array
   */
  public static function canonify_version($ver) {
      $canonical = explode('.', $ver, 3);
      if (!isset($canonical[0])) $canonical[0] = 0;
      if (!isset($canonical[1])) $canonical[1] = 0;
      if (!isset($canonical[2])) $canonical[2] = 0;
      return $canonical;
  }
  
  /**
   * Returns a comparable version spec object given a version dependency
   * string.  See CriticalI_Package::satisify_dependency for formation
   * information.
   *
   * @param string $version  The version dependency string
   * @return CriticalI_Package_VersionSpec
   */
  public static function canonify_version_specification($version) {
    return new CriticalI_Package_VersionSpec($version);
  }
  
  /**
   * Comparison function for sorting Version objects
   */
  public static function compare_versions($a, $b) {
    return $a->compare_version_number($b->version());
  }
  
  /**
   * Comparison function for sorting version strings
   */
  public static function compare_version_strings($a, $b) {
    return self::compare_version_arrays(self::canonify_version($a), self::canonify_version($b));
  }
  
  /**
   * Comparison function for sorting canonified version arrays
   */
  public static function compare_version_arrays($a, $b) {
    if ($a[0] == $b[0]) {
      if ($a[1] == $b[1]) {
        if ($a[2] == $b[2])
          return 0;
        else
          return $a[2] < $b[2] ? -1 : 1;
      } else {
        return $a[1] < $b[1] ? -1 : 1;
      }
    } else {
      return $a[0] < $b[0] ? -1 : 1;
    }
  }
}

/**
 * Internally used to represent a parsed version dependency specification
 */
class CriticalI_Package_VersionSpec {
  public $start;
  public $end;
  public $exact;
  public $plus;
  public $minus;
  public $any;
  
  public function __construct($str) {
    $this->start = null;
    $this->end = null;
    $this->exact = false;
    $this->plus = false;
    $this->minus = false;
    $this->any = false;
    
    if ($str == '*') {
      $this->any = true;
      return;
    }
    
    if (substr($str, -1) == '!') {
      $str = substr($str, 0, -1);
      $this->exact = true;
    } elseif (substr($str, -1) == '+') {
      $str = substr($str, 0, -1);
      $this->plus = true;
    } elseif (substr($str, -1) == '-') {
      $str = substr($str, 0, -1);
      $this->minus = true;
    } elseif (strpos($str, '-') !== false) {
      $parts = explode('-', $str, 2);
      $this->end = CriticalI_Package_Version::canonify_version($parts[1]);
      $str = $parts[0];
    }
    
    $this->start = CriticalI_Package_Version::canonify_version($str);
    
    if ($this->end && (CriticalI_Package_Version::compare_version_arrays($this->start, $this->end) > 0)) {
      $tmp = $this->start;
      $this->start = $this->end;
      $this->end = $tmp;
    }
  }
}

?>