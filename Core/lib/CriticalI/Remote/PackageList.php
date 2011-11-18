<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * CriticalI_Remote_PackageList is the collection of available packages
 * from one ore more remote repositories. This class behaves like many
 * other objects in the system in that it is a first class object that
 * behaves like an array. Similar to CriticalI_Package_List, Packages
 * are keyed in the list by their name.
 */
class CriticalI_Remote_PackageList implements IteratorAggregate, ArrayAccess {
  
  protected $remotes;
  protected $packages;
  
  /**
   * Constructor.
   *
   * @param array $remotes The collection of CriticalI_Remote_Repository objects to source the list from
   */
  public function __construct($remotes) {
    $this->remotes = is_array($remotes) ? $remotes : array($remotes);
    $this->packages = array();
    
    $this->construct_list();
  }
  
  /**
   * Build the list of packages from our remotes
   */
  protected function construct_list() {
    foreach ($this->remotes as $remote) {

      try {
        
        $index = $remote->index();
        foreach ($index as $entry) {
          
          $name = $entry['name'];
          
          // if we have this package already, add to it
          if (isset($this->packages[$name]))
            $this->packages[$name]->add_version($entry, $remote);
            
          // otherwise, add a new package
          else
            $this->packages[$name] = new CriticalI_Remote_Package($entry, $remote);
        }
        
      } catch (Exception $e) {
        trigger_error($e->getMessage(), E_USER_WARNING);
      }
      
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