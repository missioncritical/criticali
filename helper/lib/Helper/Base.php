<?php
// Copyright (c) 2008-2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package helper */

/**
 * Helper_Base is an abstract class for defining view helpers
 */
abstract class Helper_Base {
  
  protected $controller;
  protected $template_engine;
  protected $function_list;
  
  /**
   * Set the associated controller
   */
  public function set_controller($controller) {
    $this->controller = $controller;
  }
  
  /**
   * Return the associated controller
   */
  public function controller() {
    return $this->controller;
  }
  
  /**
   * Set the associated template engine
   */
  public function set_template_engine($template_engine) {
    $this->template_engine = $template_engine;
  }
  
  /**
   * Return the associated template engine
   */
  public function template_engine() {
    return $this->template_engine;
  }
  
  /**
   * Populates the function_list, modifier_list, and block_list members
   * with their default values from the class definition.
   */
  protected function populate_function_lists() {
    $this->function_list = array();
    
    $exclude = array('controller'=>1,'set_controller'=>1,'template_engine'=>1,
      'set_template_engine'=>1,'helper_functions'=>1,'standard_functions'=>1,
      'modifier_functions'=>1,'block_functions'=>1,'disable_helper_function'=>1,
      'standard_function'=>1,'modifier_function'=>1,'block_function'=>1);
    
    $class_name = get_class($this);
    $ref = new ReflectionClass($this);
    
    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
      if (isset($exclude[$method->name]))
        continue;
      
      $params = array();
      $defaults = array();
      foreach ($method->getParameters() as $param) {
        $params[] = $param->name;
        
        if ($param->isOptional())
          $defaults[$param->name] = $param->getDefaultValue();
      }
      
      $helper_name = $method->name;
      $type = Helper_MethodInfo::STANDARD_FUNCTION;
      
      if (substr($method->name, 0, 6) == 'block_') {
        $helper_name = substr($method->name, 6);
        $type = Helper_MethodInfo::BLOCK_FUNCTION;
        
      } elseif (substr($method->name, 0, 9) == 'modifier_') {
        $helper_name = substr($method->name, 9);
        $type = Helper_MethodInfo::MODIFIER_FUNCTION;

      }

      $this->function_list[$helper_name] = new Helper_MethodInfo($helper_name, $type, $class_name,
        $method->name, $params, $defaults, array($this, $method->name));
      
    }
  }

  /**
   * Returns the list of all helper functions defined by this helper class.
   * The list is returned as an associative array with the array keys the
   * function names and the values instances of Helper_MethodInfo classes.
   *
   * @return array
   */
  public function helper_functions() {
    if (!is_array($this->function_list))
      $this->populate_function_lists();
    
    return $this->function_list;
  }
  
  /**
   * Returns a subset of the function list filtered by the selected
   * function type
   */
  protected function filter_function_list($type) {
    if (!is_array($this->function_list))
      $this->populate_function_lists();
    
    $filtered = array();
    foreach ($this->function_list as $key=>$item) {
      if ($item->type == $type)
        $filtered[$key] = $item;
    }
    
    return $filtered;
  }

  /**
   * Returns the filter list key for the given method name
   */
  protected function key_for_method($name) {
    if (!is_array($this->function_list))
      $this->populate_function_lists();
    
    foreach ($this->function_list as $key=>$item) {
      if ($item->method_name == $name)
        return $key;
    }
    
    return false;
  }

  /**
   * Returns the list of all standard helper functions defined by this
   * helper class. The list is returned as an associative array with the
   * array keys the function names and the values instances of
   * Helper_MethodInfo classes.
   *
   * @return array
   */
  public function standard_functions() {
    return $this->filter_function_list(Helper_MethodInfo::STANDARD_FUNCTION);
  }

  /**
   * Returns the list of modifier functions defined by this helper class.
   * The list is returned as an associative array with the array keys the
   * function names and the values instances of Helper_MethodInfo classes.
   *
   * @return array
   */
  public function modifier_functions() {
    return $this->filter_function_list(Helper_MethodInfo::MODIFIER_FUNCTION);
  }
  
  /**
   * Returns the list of modifier functions defined by this helper class.
   * The list is returned as an associative array with the array keys the
   * function names and the values instances of Helper_MethodInfo classes.
   *
   * @return array
   */
  public function block_functions() {
    return $this->filter_function_list(Helper_MethodInfo::BLOCK_FUNCTION);
  }
  
  /**
   * Remove the named method from the list of defined helper functions.
   * This is useful for public functions which are not intended to be
   * exposed as helpers.
   *
   * @param string $name The name of the method to disable
   */
  public function disable_helper_function($name) {
    $key = $this->key_for_method($name);
    
    if ($key)
      unset($this->function_list[$key]);
  }
  
  /**
   * Define the named method as a standard helper function. This is
   * useful for functions which might contain a block_ or modifier_
   * prefix.
   *
   * @param string $name The name of the method to define
   */
  public function standard_function($name) {
    $this->revise_or_add_function_definition($name, $name, Helper_MethodInfo::STANDARD_FUNCTION);
  }
  
  /**
   * Define the named method as a modifier helper function. This is
   * useful for functions which do not have a modifier_ prefix.
   *
   * @param string $name The name of the method to define
   */
  public function modifier_function($name) {
    $helper_name = $name;
    if (substr($name, 0, 9) == 'modifier_')
      $helper_name = substr($name, 9);
    
    $this->revise_or_add_function_definition($name, $helper_name, Helper_MethodInfo::MODIFIER_FUNCTION);
  }
  
  /**
   * Define the named method as a block helper function. This is
   * useful for functions which do not have a block_ prefix.
   *
   * @param string $name The name of the method to define
   */
  public function block_function($name) {
    $helper_name = $name;
    if (substr($name, 0, 6) == 'block_')
      $helper_name = substr($name, 6);
    
    $this->revise_or_add_function_definition($name, $helper_name, Helper_MethodInfo::BLOCK_FUNCTION);
  }
  
  /**
   * Updates the properties of a function in the function list with the
   * revised information or adds it as needed.
   */
  protected function revise_or_add_function_definition($method, $helper_name, $type) {
    $key = $this->key_for_method($method);
    
    if ($key) {
      $info = $this->function_list[$key];
      $info->type = $type;
      $info->name = $helper_name;

      if ($key != $helper_name) {
        unset($this->function_list[$key]);
        $this->function_list[$helper_name] = $info;
      }

    } else {
      list($params, $defaults) = $this->method_parameter_names_and_defaults($method);
      
      $this->function_list[$helper_name] = new Helper_MethodInfo($helper_name, $type,
        get_class($this), $method, $params, $defaults, array($this, $method));
    }
  }
  
  /**
   * Return the parameter names and default values for a method of this
   * class
   */
  protected function method_parameter_names_and_defaults($name) {
    $params = array();
    $defaults = array();
    
    $method = new ReflectionMethod($this, $name);

    foreach ($method->getParameters() as $param) {
      $params[] = $param->name;
      
      if ($param->isOptional())
        $defaults[$param->name] = $param->getDefaultValue();
    }
    
    return array($params, $defaults);
  }
  
}
