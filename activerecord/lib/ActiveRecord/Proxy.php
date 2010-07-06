<?php

/**
 * ActiveRecord_Proxy allows for limited access to some protected methods
 * of an ActiveRecord object.  See ActiveRecord_Base::add_method_proxy()
 * for more information.
 */
class ActiveRecord_Proxy {
  protected $record;
  protected $attributes;
  protected $cached_attributes;
  protected $metaInf;
  
  /**
   * Initialize (or re-initialize) the proxy
   *
   * @param ActiveRecord_Base $record  The object being proxied
   * @param array $attributes  Attribute set of the object
   * @param array $cached_attributes Cached attribute set of the object
   * @param object $metaInf  The class information object
   */
  public function initialize($record, &$attributes, &$cached_attributes, $metaInf = null) {
    $this->record = $record;
    $this->attributes =& $attributes;
    $this->cached_attributes =& $cached_attributes;
    $this->metaInf = $metaInf;
  }
  
  /**
   * Calls read_attribute on the object the method was originally invoked on.
   *
   * @param string $name  The attribute name
   *
   * @return mixed
   */
  public function read_attribute($name) {
    $value = isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    $col = $this->record->column_for_attribute($name);
    return $col ? $col->type_cast($value) : $value;
  }

  /**
   * Calls read_attribute_before_type_cast on the object the method was originally invoked on.
   *
   * @param string $name  The attribute name
   *
   * @return mixed
   */
  public function read_attribute_before_type_cast($name) {
    return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
  }

  /**
   * Calls write_attribute on the object the method was originally invoked on.
   *
   * @param string $name  The attribute name
   * @param mixed  $value The new value for the attribute
   */
  public function write_attribute($name, $value) {
    $col = $this->record->column_for_attribute($name);
    $this->attributes[$name] = $col ? $col->reverse_type_cast($value) : $value;
  }
  
  /**
   * Calls has_cached_attribute on the object the method was originally invoked on.
   *
   * @param string $name  The attribute name
   * @return boolean
   */
  public function has_cached_attribute($name) {
    return is_null($this->cached_attributes) ? false : isset($this->cached_attributes[$name]);
  }
  
  /**
   * Calls read_cached_attribute on the object the method was originally invoked on.
   *
   * @param string $name  The attribute name
   * @return mixed
   */
  public function read_cached_attribute($name) {
    if ( is_null($this->cached_attributes) && (!isset($this->cached_attributes[$name])) )
      return null;
    return $this->cached_attributes[$name];
  }
  
  /**
   * Calls write_cached_attribute on the object the method was originally invoked on.
   *
   * @param string $name  The attribute name
   * @param mixed  $value The attribute value to cache
   */
  public function write_cached_attribute($name, $value) {
    if (is_null($this->cached_attributes))
      $this->cached_attributes = array();
    $this->cached_attributes[$name] = $value;
  }

  /**
   * Calls add_proxy_method on the object the method was originally invoked on.
   *
   * @param string $name       The name of the method to proxy
   * @param callback $callback The callback to invoke for the method
   */
  public function add_method_proxy($name, $callback) {
    if (!$this->metaInf) $this->metaInf = ActiveRecord_InfoMgr::meta_info($this->record);
    if (is_null($this->metaInf->proxied_methods)) $this->metaInf->proxied_methods = array();
    $this->metaInf->proxied_methods[$name] = new ActiveRecord_ProxyMethod($name, $callback);
  }
  
  /**
   * Add a validation to the class
   *
   * @param ActiveRecord_Validation $validation The validation object to add
   */
  public function add_validation($validation) {
    if (!($validation instanceof ActiveRecord_Validation))
      throw new Exception("Expected and instance of ActiveRecord_Validation");

    if (!$this->metaInf) $this->metaInf = ActiveRecord_InfoMgr::meta_info($this->record);
    if (is_null($this->metaInf->validations)) $this->metaInf->valiations = array();
    $this->metaInf->validations[] = $validation;
  }

}

?>