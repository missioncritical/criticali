<?php
// Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package cache */

/**
 * Cache_Store provides the public interface for working with the cache
 * system. Internally it uses one or more engines to service requests.
 *
 * It's possible to construct and use an instance of Cache_Store on its
 * own, however, the preferred method for working with the cache is to
 * call Support_Resources::cache() (see {@link Support_Resources}).
 *
 * There are currently three supported options that are not
 * engine-specific. They are:
 *  - <b>engine:</b> The cache engine to use
 *  - <b>ttl:</b> Maximum time to live (in seconds)
 *  - <b>expiration_check:</b> The expiration check callback
 *
 * The <var>engine</var> option specifies which of the available cache
 * engines to use. Engine names are lower case. They correspond to a
 * class name of Cache_Engine_<i>camel_case_engine_name</i>. For example,
 * the "memory" engine is implemented by the class Cache_Engine_Memory.
 * New engines may be added by creating new classes of the corresponding
 * name. This package provides the following engines:
 *  - apc
 *  - file
 *  - memcache
 *  - memory
 *
 * Note that the apc and memcache engines require the APC and Memcache
 * extensions, respectively. If the corresponding extension is not
 * installed, you will not be able to use that engine. See the
 * documentation of the corresponding engine class for more information
 * on what additional options the engine accepts.
 *
 * The <var>expiration_check</var> option specifies a callback which, if
 * provided, the get() method will use to check the validity of a cached
 * data item before returning it. The callback is passed two parameters:
 * a reference to the key value get() was called with, and a reference to
 * the data value it is about to return. If the expiration check callback
 * returns a boolean false value, the cached item is expired and will not
 * be returned to get's caller.
 *
 * The expiration check and data callback accepted by get() can be
 * combined to more easily handle certain caching scenarios. As a simple
 * example, let's say you're writing a blog application. To show off the
 * caching functionality, let's imagine you've done some kind of fancy
 * threading that takes a while to assemble, so you want to cache the
 * result and only regenerate when you have to. You could choose to
 * implement something like this:
 * <code>
 *   class Post extends ActiveRecord_Base {
 *     protected function init_class() {
 *       $this->has_many('comments');
 *     }
 *
 *     public function threaded_comments() {
 *       $cache = Support_Resources::cache();
 *
 *       // use a cached value when possible
 *       $data = $cache->get(array('post_comments'=>$this->id),
 *         array('expiration_check'=>array($this, 'is_data_valid')),
 *         array($this, 'rethread_comments'));
 *
 *       return $data['value'];
 *     }
 *
 *     public function is_data_valid(&$key, &$data) {
 *       if ($this->comments_updated_at > $data['updated_at'])
 *         return false;
 *
 *       return true;
 *     }
 *
 *     public function rethread_comments() {
 *       $comments = $this->comments;
 *
 *       // do some complicated massaging of $comments...
 *
 *       return array('value=>$comments, 'updated_at'=>$this->comments_updated_at);
 *     }
 *   }
 * </code>
 *
 * Admittedly, the example is a bit contrived. It does show usage of the
 * two callback features, but in reality, if your own application is in
 * charge of handling updates to comments on a post, it would be better
 * to simply expire the cached item whenever new comments come in.
 * However, the expiration_check callback can be very handy for when
 * items may expire due to events outside of the control of your
 * application. For example, when a configuration file is changed or if
 * database entries may be created by other applications.
 *
 * <b>Configuration</b>
 *
 * Any of the supported options (with the exception of expiration_check)
 * may also be specified in the config file as a way to control the
 * defaults for cache storage. Cache configuration options are named the
 * same as the options that may be passed to Cache_Store, but they must
 * be inside the <var>cache</var> section. That is to say, the following
 * configuration options are supported:
 *  - cache/engine
 *  - cache/ttl
 *
 * Engine-specific options may also be specified this way (e.g.
 * cache/cache_dir for the file engine).
 *
 * Profiles may be created by placing the configuration options for that
 * profile in cache/profiles/<i>profile_name</i>/<i>options...</i>. For
 * example, you could create the following options:
 *  - cache/profiles/example/engine = memcache
 *  - cache/profiles/example/ttl = 3600
 *  - cache/profiles/example/host = localhost
 *
 * You could then use that profile by passing just its name, as in:
 * <code>
 *   $cache = Support_Resources::cache();
 *   $cache->set('somekey', 'somevalue', 'example');
 * </code>
 */
class Cache_Store {
  
  protected $engines;
  protected $logger;
  
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
      $this->logger()->debug("cache hit for $key");
      return $data;

    // create it
    } elseif ($create) {
      if ($locking) $item->lock(Cache_ItemBase::LOCK_WRITE, $create);

      // requires a second check after the lock upgrade
      if ( $locking && (!is_null($data = $item->read())) &&
           $this->is_valid($item, $options, $origKey, $data, $locking, true) ) {
        // someone populated the key after all
        if ($locking) $item->unlock();
        $this->logger()->debug("cache hit for $key");
        return $data;
      
      } else {
        // ok to create
        $data = call_user_func($callback);
        $this->logger()->debug("cache write for $key");
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
    $this->logger()->debug("cache write for $key");
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
    $this->logger()->debug("cache expire $key");
    $item->remove();

    if ($locking) $item->unlock();
  }
  
  /**
   * Remove all items from the cache.
   *
   * This removes all items from a cache engine.
   *
   * Options are:
   *  - <b>engine:</b> The cache engine to remove items from
   *  - Any engine-specific options
   *
   * @param mixed $options An array of options or a cache profile name
   */
  public function clear($options = null) {
    // clean the parameters
    $options = $this->valid_options($options);
    
    $engine = $this->engine($options);

    // clear it
    $this->logger()->debug("clear cache");
    $engine->clear($options);
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
    return array_merge(array(
      'engine'=>Cfg::get('cache/engine', 'memory'),
      'ttl'=>Cfg::get('cache/ttl', 0)),
      $options);
  }
  
  /**
   * Return the engine specified in the options.
   *
   * The conversion from an engine name to an implementing class is
   * "Cache_Engine_" . Support_Inflector::camelize($name).
   */
  protected function engine($options) {
    $engineName = isset($options['engine']) ? $options['engine'] : Cfg::get('cache/engine', 'memory');
    
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
  
  /**
   * Return a logger instance
   */
  protected function logger() {
    if (!$this->logger)
      $this->logger = Support_Resources::logger('Cache');
    return $this->logger;
  }
  
}


?>