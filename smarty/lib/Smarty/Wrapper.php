<?php
/** @package smarty */

/**
 * A wrapper class for Smarty to provide the register_helpers function
 */
class Smarty_Wrapper {
  
  protected $smarty;
  
  /**
   * Constructor
   */
  public function __construct($smarty = null) {
    $this->smarty = $smarty ? $smarty : new Smarty();
  }
  
  /**
   * Register a list of helper functions to be made available to the view
   *
   * @param array $helpers  An array of Helper_MethodInfo objects
   */
  public function register_helpers($helpers) {
    foreach ($helpers as $helper) {
      if ($helper->type == Helper_MethodInfo::MODIFIER_FUNCTION) {
        $this->smarty->register_modifier($helper->name, $helper->callback);
        
      } elseif ($helper->type == Helper_MethodInfo::BLOCK_FUNCTION) {
        $obj = new Smarty_Wrapper_Block($helper);
        $this->smarty->register_block($helper->name, array($obj, 'invoke'));

      } else {
        $obj = new Smarty_Wrapper_Function($helper);
        $this->smarty->register_function($helper->name, array($obj, 'invoke'));
      }
    }
  }

  /**
   * Forward all undefined function calls to smarty
   */
  public function __call($name, $args) {
    return call_user_func_array(array($this->smarty, $name), $args);
  }

  /**
   * Forward all undefined property access to smarty
   */
  public function __get($name) {
    return $this->smarty->$name;
  }

  /**
   * Forward all undefined property mutation to smarty
   */
  public function __set($name, $value) {
    $this->smarty->$name = $value;
  }

  /**
   * Forward all undefined property set checking to smarty
   */
  public function __isset($name) {
    return isset($this->smarty->$name);
  }

  /**
   * Forward all undefined property unsetting to smarty
   */
  public function __unset($name) {
    unset($this->smarty->$name);
  }

}

/**
 * Base class for helper wrappers
 */
abstract class Smarty_Wrapper_Base {
  protected $helper;
  
  /**
   * Constructor
   */
  public function __construct($helper) {
    $this->helper = $helper;
  }
  
  /**
   * Populate and return the argument list given a set of parameters and
   * a number of arguments expected at the end of the method signature.
   */
  protected function assemble_args($params, $minArgs) {
    $methodParams = $this->helper->parameter_names;
    $defaults = $this->helper->defaults;
    
    $args = array();
    
    if (count($methodParams) >= $minArgs)
      array_splice($methodParams, count($methodParams) - $minArgs, $minArgs);
    
    foreach ($methodParams as $p) {
      if (isset($params[$p]))
        $args[] = $params[$p];
      elseif (isset($defaults[$p]))
        $args[] = $defaults[$p];
      else
        throw new Exception("Missing required parameter \"$p\" in function \"".$this->helper->name.'"');
    }

    return $args;
  }
}

/**
 * Implements the interface expected by Smarty for standard helper functions
 */
class Smarty_Wrapper_Function extends Smarty_Wrapper_Base {

  /**
   * Invoked by smarty
   */
  public function invoke($params, &$smarty) {
    $args = $this->assemble_args($params, 1);
    $args[] = $params;
    
    return call_user_func_array($this->helper->callback, $args);
  }
  
}

/**
 * Implements the interface expected by Smarty for block helper functions
 */
class Smarty_Wrapper_Block extends Smarty_Wrapper_Base {

  /**
   * Invoked by smarty
   */
  public function invoke($params, $content, &$smarty, &$repeat) {
    $args = $this->assemble_args($params, 3);
    $args[] = $content;
    $args[] = &$repeat;
    $args[] = $params;
    
    return call_user_func_array($this->helper->callback, $args);
  }
  
}
