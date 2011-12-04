<?php
// Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package cache */

/**
 * The memcache cache engine. This engine requires the Memcache extension
 * to be installed. Items stored with the memcache engine use memcached
 * for storage.
 *
 * The memcache cache accepts two cache-specific options:
 *  - <b>memcache_host:</b> The host where memcached is accepting connections (or unix:///path/to/socket)
 *  - <b>memcache_port:</b> The port where memcache is listening
 */
class Cache_Engine_Memcache implements Cache_Engine {
  
  protected $memcaches;
  protected $logger;
  
  /**
   * Constructor
   */
  public function __constructor() {
    $this->memcaches = array();
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
    return new Cache_Engine_Memcache_Item($this, $key, $options);
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
    $memcache = $this->get_memcache($options);
    
    if (!$memcache->flush())
      $this->logger()->error("Failed to flush memcache (".$this->get_server_description($options).")");
  }
  
  /**
   * Called statically to ensure this engine is supported before
   * constructing it. The memcache engine is only supported when the
   * memcache extension is also present.
   *
   * @return boolean
   */
  public static function is_supported() {
    if (class_exists('Memcache'))
      return true;
    
    return false;
  }
  
  /**
   * Returns the Memcache instance for a given set of options
   *
   * @param array $options The options to use
   */
  public function get_memcache($options = null) {
    list($host, $port) = $this->get_connection_options($options);
    
    $key = "$host:$port";
    
    if (!isset($this->memcaches[$key])) {
      $memcache = new Memcache();

      if ($memcache->connect($host, $port) === false) {
        $this->logger()->error("Could not connect to memcache (".
          $this->get_server_description($options).")");
        return null;
      }

      $this->memcaches[$key] = $memcache;
    }
    
    return $this->memcaches[$key];
  }

  /**
   * Returns the server description for a given set of options
   *
   * @param array $options The options to use
   */
  public function get_server_description($options = null) {
    list($host, $port) = $this->get_connection_options($options);
    return ($port === 0 ? $host : "$host:$port");
  }
  
  /**
   * Return host and port connection values for a given set of options
   *
   * @param array $options The options to use
   * @return array In the form array($host, $port)
   */
  public function get_connection_options($options = null) {
    if (is_array($options) && isset($options['memcache_host']))
      $host = $options['memcache_host'];
    else
      $host = Cfg::get('cache/memcache_host', 'localhost');

    $defaultPort = (substr($host, 0, 7) == 'unix://' ? 0 : 11211);
    
    if (is_array($options) && isset($options['memcache_port']))
      $port = $options['memcache_port'];
    else
      $port = Cfg::get('cache/memcache_port', $defaultPort);
    
    return array($host, $port);
  }
  
  /**
   * Return a logger instance for the class
   */
  public function logger() {
    if (!$this->logger) {
      $this->logger = Support_Resources::logger('Memcache');
    }
    
    return $this->logger;
  }
}

/**
 * The key object for the memcache cache engine.
 */
class Cache_Engine_Memcache_Item extends Cache_ItemBase {
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
    // get the Memcache instance
    $memcache = $this->engine->get_memcache($this->options);
    if (!$memcache)
      return false;
    
    // have to get the value to test
    $val = $memcache->get($this->key());
    
    return (($val === false) ? false : true);
  }
  
  /**
   * Return the value associated with the key from the cache
   *
   * If the key does not have a value, this function returns null.
   *
   * @return mixed
   */
  public function read() {
    // get the Memcache instance
    $memcache = $this->engine->get_memcache($this->options);
    if (!$memcache)
      return null;
    
    // get the value
    $val = $memcache->get($this->key());
    
    return (($val === false) ? null : $val);
  }
  
  /**
   * Set the value associated with the key
   *
   * @param mixed $data The value to associate with the key
   */
  public function write($data) {
    // get the Memcache instance
    $memcache = $this->engine->get_memcache($this->options);
    if (!$memcache)
      return;
    
    // set the value
    if (isset($this->options['ttl']))
      $written = $memcache->set($this->key(), $data, 0, $this->expire_value());
    else
      $written = $memcache->set($this->key(), $data);
    
    if ($written === false)
      $this->engine->logger()->error("Error writing to cache (".
        $this->engine->get_server_description($this->options).")");
  }

  /**
   * Remove the key from the cache
   */
  public function remove() {
    // get the Memcache instance
    $memcache = $this->engine->get_memcache($this->options);
    if (!$memcache)
      return;
    
    // delete the value
    $memcache->delete($this->key());
  }
  
  /**
   * Returns the expire time value to use for calls to memcache
   */
  protected function expire_value($ttl) {
    if (isset($this->options['ttl']) && ($this->options['ttl'] != 0))
      return time() + intval($this->options['ttl']);
    else
      return 0;
  }
  
}

?>