<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * CriticalI_Project_PackageList is the collection of installed packages
 * in a project. This class behaves like many other objects in the system
 * in that it is a first class object that behaves like an array. Similar
 * to CriticalI_Package_List, Packages are keyed in the list by their
 * name.
 */
class CriticalI_Project_PackageList implements IteratorAggregate, ArrayAccess {
  
  protected $project;
  protected $packages;
  
  /**
   * Constructor.
   *
   * @param CriticalI_Project $project The project the list belongs to
   */
  public function __construct($project) {
    $this->project = $project;
    $this->packages = array();
    
    $this->construct_list();
  }
  
  /**
   * Build the list of packages from our Project
   */
  protected function construct_list() {
    $names = $this->project->packages();
    $manifests = $this->project->property('manifests', array());
    $dependencies = $this->project->property('depends_on', array());
    $uninstallers = $this->project->property('uninstallers', array());
    
    foreach ($names as $packageName=>$version) {
      $properties = array();
      
      if (isset($manifests[$packageName]))
        $properties['manifest'] = $manifests[$packageName];
      
      $properties['dependencies'] = array();
      if (isset($dependencies[$packageName])) {
        foreach (explode(',', $dependencies[$packageName]) as $pair) {
          list($n,$v) = explode('=', $pair);
          $properties['dependencies'][$n] = $v;
        }
      }
      
      $properties['uninstallers'] = array();
      if (isset($uninstallers[$packageName])) {
        foreach(explode(',', $uninstallers[$packageName]) as $klass) {
          $properties['uninstallers'][] = $klass;
        }
      }
      
      $this->packages[$packageName] = new CriticalI_Project_Package($packageName, $version, $properties);
    }
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
  
}

?>