<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * A Form_Object_Container is a Form_Object which contains a collection
 * of fields or other form objects
 */
abstract class Form_Object_Container extends Form_Object {
  
  protected $objects = array();
  
  /**
   * Add an object to the collection
   *
   * @param Form_Object $form_object  The object to add
   */
  public function add_object($form_object) {
    $this->objects[$form_object->name()] = $form_object;
  }
  
  /**
   * Remove a previously added object by name
   *
   * @param string $name The name of the object to remove
   */
  public function remove_object($name) {
    unset($this->objects[$name]);
  }
  
  /**
   * Returns the count of objects in this container
   * @return int
   */
  public function object_count() {
    return count($this->objects);
  }
  
  /**
   * Return a list of objects in this container
   * @return array
   */
  public function objects() {
    return $this->objects;
  }
  
  /**
   * Return the named object from this container
   *
   * @param string $name The name of the object to return
   * @return Form_Object
   */
  public function object($name) {
    return isset($this->objects[$name]) ? $this->objects[$name] : null;
  }

  /**
   * Set this object's value
   * @param boolean $fromControl When true, indicates the value is being assigned from a control
   */
  public function set_value($value, $fromControl = false) {
    parent::set_value($value, $fromControl);
    
    // set the value for children as well
    if (is_array($value)) {
      foreach ($this->objects as $obj) { $obj->set_value($value[$obj->name()], $fromControl); }
    } elseif (is_object($value)) {
      foreach ($this->objects as $obj) { $attr = $obj->name(); $obj->set_value($value->$attr, $fromControl); }
    } else {
      foreach ($this->objects as $obj) { $obj->set_value(null, false); }
    }
  }

  /**
   * Assign this object's value to another object
   * @param mixed &$object The object to assign the value to
   */
  public function assign_to(&$object) {
    $attr = $this->name;
    
    if (is_array($object)) {
      
      if (isset($object[$attr])) {
        foreach ($this->objects as $child) { $child->assign_to($object[$attr]); }
      } else {
        $blank = array();
        foreach ($this->objects as $child) { $child->assign_to($blank); }
        $object[$attr] = $blank;
      }
      
    } else {
      foreach ($this->objects as $child) { $child->assign_to($object->$attr); }
    }
    
  }

  /**
   * Exclude performs the same task as remove_object, but returns a
   * reference to this container
   *
   * @param string $name The name of the object to remove
   * @return Form_Object_Container Returns a reference to this container
   */
  public function exclude($name) {
    $this->remove_object($name);
    return $this;
  }
  
}
