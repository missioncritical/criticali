<?php
// Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package cache */

/**
 * Cache_Store provides the public interface for working with the cache
 * system. Internally it uses one or more engines to service requests.
 *
 * *info here about expiration_check callbacks*
 */
class Cache_Store {
  
  protected $engines;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->engines = array();
  }
  
  /**
   * Return the value for <var>key</var>.
   *
   * If a callback is provided, it is called if <var>key</var> does not
   * exist, and the value the callback returns is set in the cache and
   * returned. This is useful not only for conditionally production the
   * cache value, but if the underlying cache engine supports locking,
   * this will also prevent multiple simultaneous attempts (in different
   * processes or threads) to produce the value for the same key.
   *
   * Options are:
   *  - <b>engine:</b> The cache engine to use
   *  - <b>ttl:</b> Maximum time to live (in seconds)
   *  - <b>expiration_check:</b> The expiration check callback
   *  - Any engine-specific options
   *
   * @param mixed $key The key to obtain the value for
   * @param mixed $options An array of options or a cache profile name
   * @param callback $callback The optional callback
   * @return mixed
   */
  public function get($key, $options = null, $callback = null) {
    // clean the parameters
    $origKey = $key;
    $key = $this->key_value($key);
    $options = $this->valid_options($options);
    $create = is_callable($callback);
    
    $engine = $this->engine($options);
    $locking = $engine->has_locking();

    // get the item
    $item = $engine->item_for_key($key, $options);
    if ($locking) $item->lock(Cache_ItemBase::LOCK_READ, $create);

    // see if it exists
    if ( (!is_null($data = $item->read())) &&
          $this->is_valid($item, $options, $origKey, $data, $locking, false) ) {
      if ($locking) $item->unlock();
      return $data;

    // create it
    } elseif ($create) {
      if ($locking) $item->lock(Cache_ItemBase::LOCK_WRITE, $create);

      // requires a second check after the lock upgrade
      if ( $locking && (!is_null($data = $item->read())) &&
           $this->is_valid($item, $options, $origKey, $data, $locking, true) ) {
        // someone populated the key after all
        if ($locking) $item->unlock();
        return $data;
      
      } else {
        // ok to create
        $data = call_user_func($callback);
        $item->write($data);
        if ($locking) $item->unlock();
        return $data;
      }
      
    }

    // doesn't exist
    if ($locking) $item->unlock();
    return null;

  }
  
  /**
   * Store a value for <var>key</var>.
   *
   * Options are:
   *  - <b>engine:</b> The cache engine to use
   *  - <b>ttl:</b> Maximum time to live (in seconds)
   *  - Any engine-specific options
   *
   * @param mixed $key The key set the value for
   * @param mixed $data The data to store
   * @param mixed $options An array of options or a cache profile name
   */
  public function set($key, $data, $options = null) {
    // clean the parameters
    $key = $this->key_value($key);
    $options = $this->valid_options($options);
    
    $engine = $this->engine($options);
    $locking = $engine->has_locking();

    // get the item
    $item = $engine->item_for_key($key, $options);
    if ($locking) $item->lock(Cache_ItemBase::LOCK_WRITE, true);

    // create it
    $item->write($data);

    if ($locking) $item->unlock();
  }
  
  /**
   * Test for the existence of <var>key</var> in the cache.
   *
   * Options are:
   *  - <b>engine:</b> The cache engine to use
   *  - Any engine-specific options
   *
   * @param mixed $key The key set the value for
   * @param mixed $options An array of options or a cache profile name
   */
  public function exists($key, $options = null) {
    // clean the parameters
    $key = $this->key_value($key);
    $options = $this->valid_options($options);
    
    $engine = $this->engine($options);
    $locking = $engine->has_locking();

    // get the item
    $item = $engine->item_for_key($key, $options);
    if ($locking) $item->lock(Cache_ItemBase::LOCK_READ, false);

    // see if it exists
    $exists = $item->exists();
    
    // clean up
    if ($locking) $item->unlock();
    return $exists;
  }
  
  /**
   * Remove <var>key</var> and its data from the cache.
   *
   * Options are:
   *  - <b>engine:</b> The cache engine to use
   *  - Any engine-specific options
   *
   * @param mixed $key The key to remove
   * @param mixed $options An array of options or a cache profile name
   */
  public function expire($key, $options = null) {
    // clean the parameters
    $key = $this->key_value($key);
    $options = $this->valid_options($options);
    
    $engine = $this->engine($options);
    $locking = $engine->has_locking();

    // get the item
    $item = $engine->item_for_key($key, $options);
    if ($locking) $item->lock(Cache_ItemBase::LOCK_WRITE, true);

    // destroy it
    $item->remove();

    if ($locking) $item->unlock();
  }
  
  /**
   * Return the correct set of options to use for a provided options
   * argument
   *
   * @param mixed $options An array of options or a cache profile name
   * @return array
   */
  protected function valid_options($options) {
    // if this is a cache profile name, get the profile
    if (is_string($options))
      $options = Cfg::get("cache/profiles/$options");

    // options must be an array
    $options = is_array($options) ? $options : array();
    
    // merge in the defaults
    return array_merge(array('engine'=>'memory', 'ttl'=>0), $options);
  }
  
  /**
   * Return the engine specified in the options.
   *
   * The conversion from an engine name to an implementing class is
   * "Cache_Engine_" . Support_Inflector::camelize($name).
   */
  protected function engine($options) {
    $engineName = isset($options['engine']) ? $options['engine'] : 'memory';
    
    if (!isset($this->engines[$engineName])) {
      $class = 'Cache_Engine_' . Support_Inflector::camelize($engineName);
      if ( (!class_exists($class)) || (!call_user_func(array($class, 'is_supported'))) )
        throw new Cache_UnsupportedEngineError($engineName);

      $engine = new $class();
      if (!$engine instanceof Cache_Engine)
        throw new Cache_UnsupportedEngineError($engineName);

      $this->engines[$engineName] = $engine;
    }
    
    return $this->engines[$engineName];
  }
  
  /**
   * Returns a clean key value
   *
   * @return string
   */
  protected function key_value($key) {
    if (is_string($key) || is_int($key))
      return $key;
    elseif (is_numeric($key))
      return strval($key);
    else
      return serialize($key);
  }
  
  /**
   * Tests if an item is considered valid (has not yet expired)
   *
   * @param Cache_ItemBase $item The cache item object
   * @param array $option Caching options
   * @param mixed $key The key that was passed in
   * @param mixed $data The data associated with the key
   * @param boolean $locking Flag indicating whether or not the engine supports locking
   * @param boolean $locking Flag indicating whether or not the item is exclusively locked
   * @return boolean
   */
  protected function is_valid($item, $options, &$key, &$data, $locking, $exLocked) {

    $mtime = $item->mtime();

    // check ttl
    if ($options['ttl']) {
      if ( ($mtime !== false) && ($mtime < (time() - $options['ttl'])) ) {
        $this->conditionally_remove($item, $mtime, $key, $data, $locking, $exLocked);
        return false;
      }
    }
    
    // check expiration_check
    if (isset($options['expiration_check']) && is_callable($options['expiration_check'])) {
      if (call_user_func_array($options['expiration_check'], array(&$key, &$data)) === false) {
        $this->conditionally_remove($item, $mtime, $key, $data, $locking, $exLocked);
        return false;
      }
    }
    
    return true;
  }
  
  /**
   * Conditionally removes an item based on it being unchanged
   */
  protected function conditionally_remove($item, $mtime, &$key, &$data, $locking, $exLocked) {
    if ((!$locking) || $exLocked) {
      $item->remove();
    } else {
      // this is a bit tricky, we need to get an exclusive lock to delete the item
      $item->lock(Cache_ItemBase::LOCK_WRITE, false);
    
      $mtime2 = $item->mtime();
      $data2 = $item->read();
    
      /* If the data and modification time don't match, it indicates a race condition.
         The condition will be resolved correctly if a data callback has been provided.
         Otherwise, the best we can do at this stage is to proceed the same as if we
         had been granted the exclusive lock first, in which case we would have returned
         null and the cache data would have been replaced once we gave up our read lock. */

      if ($mtime == $mtime2 && $data == $data2)
        $item->remove();
    }
  }
  
}


?>