<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * The default cache provider returns
 * Support_Resources_DefaultCacheStore
 */
class Support_Resources_DefaultCacheProvider implements Support_Resources_CacheProvider {
  protected $cacheStore;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->cacheStore = new Support_Resources_DefaultCacheStore();
  }

  /**
   * Return a cache store instance.
   *
   * @return object
   */
  public function get() {
    return $this->cacheStore;
  }
}

/**
 * Support_Resources_DefaultCacheStore provides the same public interface
 * as Cache_Store, but is not actually backed by a cache (i.e. no data is
 * stored/cached). This allows consumers of the class to use the same
 * interface whether or not the system where they are running has any
 * caching set up.
 */
class Support_Resources_DefaultCacheStore {

  /**
   * Return the value for <var>key</var>.
   *
   * This implementation always returns null unless a callback is
   * provided. If a callback is provided, it is called and its value
   * returned.
   *
   * @param mixed $key The key to obtain the value for
   * @param mixed $options An array of options or a cache profile name (ignored)
   * @param callback $callback The optional callback
   * @return mixed
   */
  public function get($key, $options = null, $callback = null) {
    if (is_callable($callback))
      return call_user_func($callback);
    else
      return null;
  }
  
  /**
   * Store a value for <var>key</var>.
   *
   * No value is actually stored. All parameters are ignored.
   *
   * @param mixed $key The key set the value for
   * @param mixed $data The data to store
   * @param mixed $options An array of options or a cache profile name
   */
  public function set($key, $data, $options = null) {
  }
  
  /**
   * Test for the existence of <var>key</var> in the cache.
   *
   * Always returns false
   *
   * @param mixed $key The key set the value for
   * @param mixed $options An array of options or a cache profile name (ignored)
   */
  public function exists($key, $options = null) {
    return false;
  }

  /**
   * Remove <var>key</var> and its data from the cache.
   *
   * Like set, this does not have any actual effect. All parameters are
   * ignored.
   *
   * @param mixed $key The key to remove
   * @param mixed $options An array of options or a cache profile name
   */
  public function expire($key, $options = null) {
  }

}

?>