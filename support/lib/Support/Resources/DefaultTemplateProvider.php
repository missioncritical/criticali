<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * The default template engine provider.  This provider returns a simple
 * class which uses PHP's built-in templating abilities.
 */
class Support_Resources_DefaultTemplateProvider implements Support_Resources_TemplateProvider {
  /**
   * Return a template engine instance.
   *
   * @return object
   */
  public function get() {
    $engine = new Support_Resources_DefaultTemplateEngine();
    $here = dirname(__FILE__);
    $engine->template_dir  = Cfg::get('template_dir', "$here/../../../views");
    return $engine;
  }
}

/**
 * The default template engine
 */
class Support_Resources_DefaultTemplateEngine {
  public $template_dir = '.';
  protected $variables;
  protected $helpers;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->variables = array();
    $this->helpers = array();
  }
  
  /**
   * Output a template
   *
   * @param string $template  The path to the template, relative to template_dir
   * @param string $cache_id  Optional cache identifier
   * @param string $compile_id Optional compile identifier
   */
  public function display($template, $cache_id = null, $compile_id = null) {
    $file = $this->template_dir . '/' . $template;
    if (!file_exists($file))
      throw new Exception("Missing template file \"$template\".");
    
    extract($this->variables);
    include($file);
  }
  
  /**
   * Return a processed template as a string
   *
   * @param string $template  The path to the template, relative to template_dir
   * @param string $cache_id  Optional cache identifier
   * @param string $compile_id Optional compile identifier
   *
   * @return string
   */
  public function fetch($template, $cache_id = null, $compile_id = null) {
    if (!ob_start())
      throw new Exception("Could not begin output buffer.");
      
    try {
      $this->display($template, $cache_id, $compile_id);
    } catch (Exception $e) {
      ob_end_clean();
      throw $e;
    }
    
    return ob_get_clean();
  }

  /**
   * Assign a variable to be available to any template processed by this engine
   *
   * @param string $variable  The name of the variable
   * @param mixed  $value     The value to assign
   */
  public function assign($variable, $value) {
    $this->variables[$variable] = $value;
  }
  
  /**
   * Return the value assigned to the named variable, or all assigned
   * variables if not provided.
   *
   * @param string $variable  The name of the variable to retrieve
   *
   * @return mixed  The value of the variable or an associated array of all variables
   */
  public function get_template_vars($variable = null) {
    return is_null($variable) ? $this->variables : $this->variables[$variable];
  }
  
  /**
   * Register a list of helper functions to be made available to the view
   *
   * @param array $helpers  An array of Helper_MethodInfo objects
   */
  public function register_helpers($helpers) {
    if (is_array($helpers))
      $this->helpers = $helpers;
  }
  
  /**
   * Called when an unknown function is invoked
   */
  public function __call($name, $args) {
    // see if we have a helper by that name
    if (isset($this->helpers[$name])) {
      
      return call_user_func_array($this->helpers[$name]->callback, $args);
      
    } else {
      throw new Exception("Invoked unknown method \"$name\".");
    }
  }
  
}

?>