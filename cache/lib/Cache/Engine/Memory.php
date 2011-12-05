<?php
// Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package cache */

/**
 * The in-memory cache engine. Items stored in the in-memory cache are
 * lost at the end of the current request.
 */
class Cache_Engine_Memory implements Cache_Engine {
  
  protected $cache;
  
  /**
   * Constructor
   */
  public function __constructor() {
    $this->cache = array();
  }

  /**
   * Return the cache item object for the provided key.
   *
   * @param string $key The key to retrieve the item for
   * @param array $options Any cache options provided by the requestor
   * @return Cache_ItemBase
   */
  public function item_for_key($key, $options) {
    return new Cache_Engine_Memory_Item($this->cache, $key);
  }
  
  /**
   * Returns false to indicate this engine's items do not provide locking
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
    $this->cache = array();
  }
  
  /**
   * Called statically to ensure this engine is supported before
   * constructing it. The memory engine is always supported.
   *
   * @return boolean
   */
  public static function is_supported() {
    return true;
  }
  
}

/**
 * The key object for the memory cache engine.
 */
class Cache_Engine_Memory_Item extends Cache_ItemBase {
  protected $cache;

  /**
   * Constructor
   */
  public function __construct(&$cache, $key) {
    parent::__construct($key);
    
    $this->cache =& $cache;
  }
  
  /**
   * Test for the existence of the key in the cache. Return true if it
   * exists, false otherwise.
   *
   * @return boolean
   */
  public function exists() {
    return isset($this->cache[$this->key()]);
  }
  
  /**
   * Return the value associated with the key from the cache
   *
   * If the key does not have a value, this function returns null.
   *
   * @return mixed
   */
  public function read() {
    return isset($this->cache[$this->key()]) ? $this->cache[$this->key()] : null;
  }
  
  /**
   * Set the value associated with the key
   *
   * @param mixed $data The value to associate with the key
   */
  public function write($data) {
    $this->cache[$this->key()] = $data;
  }

  /**
   * Remove the key from the cache
   */
  public function remove() {
    unset($this->cache[$this->key()]);
  }
  
}

?>