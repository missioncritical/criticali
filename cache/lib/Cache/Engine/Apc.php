<?php
// Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package cache */

/**
 * The APC cache engine. This engine requires the APC extension
 * to be installed. Items stored with the APC engine use the APC-provided
 * variable cache mechanism for storage.
 */
class Cache_Engine_Apc implements Cache_Engine {
  
  protected $logger;
  
  /**
   * Constructor
   */
  public function __constructor() {
    $this->logger = false;
  }
  
  /**
   * Return the cache item object for the provided key.
   *
   * @param string $key The key to retrieve the item for
   * @param array $options Any cache options provided by the requestor
   * @return Cache_ItemBase
   */
  public function item_for_key($key, $options) {
    return new Cache_Engine_Apc_Item($this, $key, $options);
  }
  
  /**
   * Returns false to indicate this engine's item do not provide locking
   *
   * @return boolean
   */
  public function has_locking() {
    return false;
  }
  
  /**
   * Clear all items from the cache
   *
   * @param array $options Any cache options provided by the requestor
   */
  public function clear($options = null) {
    if (!apc_clear_cache('user'))
      $this->logger()->error("Failed to clear apc cache");
  }
  
  /**
   * Called statically to ensure this engine is supported before
   * constructing it. The apc engine is only supported when the
   * APC extension is also present.
   *
   * @return boolean
   */
  public static function is_supported() {
    if (function_exists('apc_clear_cache') && function_exists('apc_delete') &&
        function_exists('apc_exists') && function_exists('apc_fetch') &&
        function_exists('apc_store'))
      return true;
    
    return false;
  }
  
  /**
   * Return a logger instance for the class
   */
  public function logger() {
    if (!$this->logger) {
      $this->logger = Support_Resources::logger('APC Cache');
    }
    
    return $this->logger;
  }
}

/**
 * The key object for the apc cache engine.
 */
class Cache_Engine_Apc_Item extends Cache_ItemBase {
  protected $engine;
  protected $options;

  /**
   * Constructor
   */
  public function __construct($engine, $key, $options) {
    parent::__construct($key);
    
    $this->engine = $engine;
    $this->options = $options;
  }
  
  /**
   * Test for the existence of the key in the cache. Return true if it
   * exists, false otherwise.
   *
   * @return boolean
   */
  public function exists() {
    return apc_exists($this->key());
  }
  
  /**
   * Return the value associated with the key from the cache
   *
   * If the key does not have a value, this function returns null.
   *
   * @return mixed
   */
  public function read() {
    $data = apc_fetch($this->key(), $success);

    return ($success ? $data : null);
  }
  
  /**
   * Set the value associated with the key
   *
   * @param mixed $data The value to associate with the key
   */
  public function write($data) {
    // set the value
    if (isset($this->options['ttl']) && ($this->options['ttl'] != 0))
      $written = apc_store($this->key(), $data, intval($this->options['ttl']));
    else
      $written = apc_store($this->key(), $data);
    
    if ($written === false)
      $this->engine->logger()->error("Error writing to APC cache");
  }

  /**
   * Remove the key from the cache
   */
  public function remove() {
    apc_delete($this->key());
  }
  
}

?>