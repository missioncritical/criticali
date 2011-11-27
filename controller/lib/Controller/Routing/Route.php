<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

/**
 * Represents a complete route in the system. A route is comprised of one
 * or more segments.
 */
class Controller_Routing_Route {

  protected $postion;
  protected $segmentsHead;
  protected $constraints;
  protected $defaults;
  protected $name;

  /**
   * Constructor
   *
   * @param int $position Route position
   * @param Controller_Routing_Segment $segments The list of segments in the route
   * @param array $constraints Any constraints on the route
   * @param array $defaults Any default parameters for the route
   * @param string $name The route name, if any
   */
  public function __construct($position = null, $segments = null, $constraints = array(),
    $defaults = array(), $name = null) {
    
    $this->position = $position;
    $this->segmentsHead = $segments;
    $this->constraints = is_array($constraints) ? $constraints : array();
    $this->defaults = is_array($defaults) ? $defaults : array();
    $this->name = $name;
  }

  /**
   * Return this route's position
   *
   * @return int
   */
  public function position() {
    return $this->position;
  }
  
  /**
   * Set this route's position
   *
   * @param int $position The new position value
   */
  public function set_position($position) {
    $this->position = $position;
  }
  
  /**
   * Return this route's constraints
   *
   * @return array
   */
  public function constraints() {
    return $this->constraints;
  }
  
  /**
   * Set this route's constraints
   *
   * @param array $constraints The new constraints
   */
  public function set_constraints($constraints) {
    $this->constraints = $constraints;
  }

  /**
   * Return this route's default parameters
   *
   * @return array
   */
  public function defaults() {
    return $this->defaults;
  }
  
  /**
   * Set this route's default parameters
   *
   * @param array $defaults The new default values
   */
  public function set_defaults($defaults) {
    $this->defaults = $defaults;
  }

  /**
   * Return this route's name
   *
   * @return string
   */
  public function name() {
    return $this->name;
  }
  
  /**
   * Set this route's name
   *
   * @param string $name The new name
   */
  public function set_name($name) {
    $this->name = $name;
  }

  /**
   * Return this route's first segment
   *
   * @return Controller_Routing_Segment
   */
  public function first_segment() {
    return $this->segmentsHead;
  }
  
  /**
   * Set this route's first segment
   *
   * @param Controller_Routing_Segment $segment The new first segment
   */
  public function set_first_segment($segment) {
    $this->segmentsHead = $segment;
  }
  
  /**
   * Determine if this route matches the given URL
   *
   * @param string $url The URL to match
   * @param string $method The request method (e.g. "GET")
   * @param array &$params Input/output parameter request parameters
   * @return boolean
   */
  public function match($url, $method, &$params) {
    if (!$this->passes_method_constraints($method))
      return false;
    
    $newParams = is_array($params) ? array_merge($this->defaults, $params) : $this->defaults;
    
    $segment = $this->segmentsHead;
    while ($segment) {
      if (!$segment->match($url, $newParams, $remainder))
        return false;
      
      $url = $remainder;
      $segment = $segment->next();
    }
    
    if (strlen($url) > 0)
      return false;
    
    if (!$this->passes_parameter_constraints($newParams))
      return false;
    
    $params = $newParams;
    return true;
  }
  
  /**
   * Tests if a request method matches any constraints set on this route
   *
   * @param string $method The request method to test
   * @return boolean
   */
  public function passes_method_constraints($method) {
    if (!isset($this->constraints['method']))
      return true;
    
    $method = strtolower($method);

    if (is_array($this->constraints['method'])) {
      foreach ($this->constraints['method'] as $meth) {
        if (strtolower($meth) == $method)
          return true;
      }
      
      return false;
    }

    return strtolower($this->constraints['method']) == $method;
  }

  /**
   * Tests if a request method matches any parameter constraints set on
   * this route
   *
   * @param array $params The parameters to test
   * @return boolean
   */
  public function passes_parameter_constraints($params) {
    foreach ($this->constraints as $param=>$pattern) {
      if ($param == 'method')
        continue;
      
      if ($pattern[0] == '/') {
        if (!preg_match($pattern, @$params[$param]))
          return false;
      
      } else {
        if ($pattern != @$params[$param])
          return false;
      }
      
    }
    
    return true;
  }

}

?>