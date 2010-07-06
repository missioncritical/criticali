<?php

/**
 * ActiveRecord_ProxyMethod encapsulates a proxied method for an
 * ActiveRecord object.  See ActiveRecord_Base::add_method_proxy() for
 * more information.
 */
class ActiveRecord_ProxyMethod extends ActiveRecord_Proxy {
  protected $name;
  protected $callback;
  protected $requiredArgumentCount;
  protected $maximumArgumentCount;
  
  /**
   * Constructor
   *
   * @param string $name  The name to masquerade as
   * @param callback $callback The callback to invoke
   */
  public function __construct($name, $callback) {
    $this->name = $name;
    $this->callback = $callback;
    
    if (is_array($this->callback)) {
      $className = is_object($this->callback[0]) ? get_class($this->callback[0]) : $this->callback[0];
      $meth = new ReflectionMethod($className, $this->callback[1]);
      $this->requiredArgumentCount = $meth->getNumberOfRequiredParameters() - 2;
      $this->maximumArgumentCount = $meth->getNumberOfParameters() - 2;
    } else {
      $func = new ReflectionFunction($this->callback);
      $this->requiredArgumentCount = $func->getNumberOfRequiredParameters() - 2;
      $this->maximumArgumentCount = $func->getNumberOfParameters() - 2;
    }
    
    if ($this->requiredArgumentCount < 0)
      $this->requiredArgumentCount = 0;
    if ($this->maximumArgumentCount < 0)
      $this->maximumArgumentCount = 0;
  }
  
  /**
   * Return the name of this proxy
   * @return string
   */
  public function name() { return $this->name; }
  
  /**
   * Return the number of arguments required by the method
   * @return int
   */
  public function required_parameter_count() { return $this->requiredArgumentCount; }
  
  /**
   * Return the maximum number of arguments accepted
   * @return int
   */
  public function maximum_parameter_count() { return $this->maximumArgumentCount; }
  
  /**
   * Invoke the proxy
   *
   * @param ActiveRecord_Base $record  The object the method is being invoked on
   * @param array $attributes  Attribute set of the object
   * @param array $cached_attributes Cached attribute set of the object
   * @param array $args The arguments to pass
   * @return mixed  The return value from the call
   */
  public function invoke($record, &$attributes, &$cached_attributes, $args) {
    $this->record = $record;
    $this->attributes =& $attributes;
    $this->cached_attributes =& $cached_attributes;
    
    array_unshift($args, $record, $this);
    
    return call_user_func_array($this->callback, $args);
  }

}

?>