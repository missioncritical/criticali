<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * A Form Object is the base type for items which may be contained within a form
 */
abstract class Form_Object {
  
  protected $form;
  protected $name;
  protected $value;
  
  
  /**
   * Constructor
   *
   * @param string $form       The containing form
   * @param string $name       The name of this object
   */
  public function __construct($form, $name) {
    $this->form = $form;
    $this->name = $name;
  }

  /**
   * Return this object's containing form
   * @return Form_Base
   */
  public function form() {
    return $this->form;
  }

  /**
   * Return this object's name
   * @return string
   */
  public function name() {
    return $this->name;
  }
  
  /**
   * Return this object's value
   * @return mixed
   */
  public function value() {
    return $this->value;
  }
  
  /**
   * Set this object's value
   * @param mixed $value
   * @param boolean $fromControl When true, indicates the value is being assigned from a control
   */
  public function set_value($value, $fromControl = false) {
    $this->value = $value;
  }
  
  /**
   * Assign this object's value to another object
   * @param mixed &$object The object to assign the value to
   */
  public function assign_to(&$object) {
    $attr = $this->name();
    
    if (is_array($object))
      $object[$attr] = $this->value();
    else
      $object->$attr = $this->value();
  }

}
