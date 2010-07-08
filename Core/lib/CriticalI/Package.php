<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A Package is a collection of one or more installed package versions.
 * It can be treated as a list (array) of installed versions.  Accessing
 * versions by numeric index provides a list of installed version in
 * ascending order.  Accessing versions by string index allows you access
 * them by version number.
 */
class CriticalI_Package implements IteratorAggregate, ArrayAccess {
  protected $name;
  protected $versions;
  
  /**
   * Constructor
   *
   * @param string $name  The name of the package
   * @param string $verString A comma-separated list of installed version numbers
   * @param array  $dirs      List of install directories keyed by package-version
   */
  public function __construct($name, $verString, $dirs = array()) {
    $this->name = $name;
    $this->versions = array();
    $this->populate_versions_from_string($verString, $dirs);
  }
  
  /**
   * Return the name of the package
   * @return string
   */
  public function name() {
    return $this->name;
  }
  
  /**
   * Populates the list of versions from a string containing a comma-
   * separated list of installed version numbers.
   * @param string $str  The versions string
   * @param array  $directories List of install directories keyed by package-version
   */
  public function populate_versions_from_string($str, $directories) {
    foreach(explode(',', $str) as $rawVersion) {
      list($maj, $min, $rev) = CriticalI_Package_Version::canonify_version($rawVersion);
      $dir = (isset($directories[$this->name."-$maj.$min.$rev"]) ?
        $directories[$this->name."-$maj.$min.$rev"] : ($this->name."-$maj.$min.$rev"));
      $this->versions[] = new CriticalI_Package_Version($this, $maj, $min, $rev, $dir);
      usort($this->versions, array('CriticalI_Package_Version', 'compare_versions'));
    }
  }
  
  /**
   * Return the oldest version of this package
   * @return CriticalI_Package_Version
   */
  public function oldest() {
    return $this->versions[0];
  }
  
  /**
   * Return the newest version of the package
   * @return CriticalI_Package_Version
   */
  public function newest() {
    return $this->versions[count($this->versions) - 1];
  }
  
  /**
   * Returns a comma-separated list of version strings for the versions
   * this package contains.
   * @return string
   */
  public function versions_string() {
    $strs = array();
    foreach ($this->versions as $ver) { $strs[] = $ver->version_string(); }
    return implode(',', $strs);
  }
  
  /**
   * Return the version that satisfies the dependency required by the
   * given version dependency string or null if not found.
   *
   * Version dependency strings have a number of modifiers that affect
   * how they will be interpreted.  If no modifiers are specified, any
   * version number that is greater than or equal to the specified
   * version is considered a match as long as the major version numbers
   * are the same.  That is, if 1.5.287 is specified, versions 1.5.287
   * and 1.6.4 will match, however 1.5.14 and 2.3.0 will not.  If the
   * version number is followed by an exclamation mark (e.g. 1.5.287!),
   * only the exact version number will be considered a match.  If the
   * version number is followed by a plus sign (e.g. 1.5.287+), any
   * version number greater than or equal to that version will be
   * considered a match, even if the major version numbers differ.  If
   * the version number is followed by a minus sign (e.g. 1.5.287-), any
   * version number less than or equal to that version will be considered
   * a match, even if the major version numbers differ.  A range may also
   * be specified using a dash to separate the numbers (e.g.
   * 1.5.287-1.6.2), in which case any version number between or
   * including the two end numbers shall be considered a match.  Finally,
   * an asterisk (*) may be specified to indicate any version.
   *
   * @param string $version  The version dependency specification
   * @return CriticalI_Package_Version
   */
  public function satisfy_dependency($version) {
    $spec = CriticalI_Package_Version::canonify_version_specification($version);
    
    // find the first newest version that satisfies the dependency
    for ($i = count($this->versions) - 1; $i >= 0; $i--) {
      $result = $this->versions[$i]->compare_version_specification($spec);
      if ($result < 0) return null;
      if ($result == 0) return $this->versions[$i];
    }
    
    return null;
  }
  
  /**
   * Returns the numeric index of a given version number, or false if it
   * does not exist.
   * @param string $ver The version number to search for (as a string)
   * @return int
   */
  public function index_of_version($ver) {
    $version = CriticalI_Package_Version::canonify_version($ver);
    foreach ($this->versions as $idx=>$test) {
      $result = $test->compare_version_number($version);
      if ($result == 0) return $idx;
      if ($result > 0) return false;
    }
    return false;
  }

  /**
   * Return an iterator for the versions list
   * @return ArrayIterator
   */
  public function getIterator() {
    return new ArrayIterator($this->versions);
  }
  
  /**
   * Tests for existence of an array index
   * @param string $idx  The index to test
   * @return boolean
   */
  public function offsetExists($idx) {
    if (is_string($idx)) {
      return ($this->index_of_version($idx) !== false);
    } else {
      return isset($this->versions[$idx]);
    }
  }
  
  /**
   * Retrieves the value at an array index.
   * @param string $idx  The index to get
   * @return CriticalI_Package_Version
   */
  public function offsetGet($idx) {
    if (is_string($idx)) {
      return $this->versions[$this->index_of_version($idx)];
    } else {
      return $this->versions[$idx];
    }
  }
  
  /**
   * Sets the value at an array index
   * @param string $idx   The index to set
   * @param CriticalI_Package_Version $value The value to set
   */
  public function offsetSet($idx, $value) {
    // special case for an empty value
    if (empty($value)) {
      $this->offsetUnset($idx);
      return;
    }
    if (!($value instanceof CriticalI_Package_Version))
      throw new Exception("Invalid parameter supplied.  CriticalI_Package_Version required, but received ".get_class($value));
    if ($value->parent() !== $this)
      throw new Exception("Invalid parameter supplied.  Version is not a part of this package.");
    
    if (is_string($idx)) {
      $actualIdx = $this->index_of_version($idx);
      if ($actualIdx === false)
        $this->versions[] = $value;
      else
        $this->versions[$actualIdx] = $value;
    } else {
      $this->versions[$idx] = $value;
    }
    
    usort($this->versions, array('CriticalI_Package_Version', 'compare_versions'));
  }
  
  /**
   * Deletes an entry at an array index
   * @param string $idx  The index to unset
   */
  public function offsetUnset($idx) {
    if (is_string($idx)) {
      unset($this->versions[$this->index_of_version($idx)]);
    } else {
      unset($this->versions[$idx]);
    }

    usort($this->versions, array('CriticalI_Package_Version', 'compare_versions'));
  }
  
}

?>