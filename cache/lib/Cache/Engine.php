<?php
// Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package cache */

/**
 * Cache_Engine defines the interface that cache implementations must
 * conform to.
 *
 * The interface is very simple, only one method is involved. The work is
 * in implementing the object it returns, which must be an instance of
 * {@link Cache_ItemBase} or provide the same methods as that class.
 *
 * Note that what the above is saying is that the engine class does not
 * return cached values directly. It returns an object which can be used
 * to test for, read, or write the value associated with the requested
 * key. So, even if a value does not exist in the cache, the engine must
 * return an object which can be used to create it.
 */
interface Cache_Engine {

  /**
   * Return the cache item object for the provided key.
   *
   * @param string $key The key to retrieve the item for
   * @param array $options Any cache options provided by the requestor
   * @return Cache_ItemBase
   */
  public function item_for_key($key, $options);
  
  /**
   * Return true if this Engine's items provide locking
   *
   * @return boolean
   */
  public function has_locking();
  
  /**
   * Called statically to ensure this engine is supported before
   * constructing it. Most engines will want to always return true, but
   * in some cases it may be a good place to check that all needed
   * extensions are present.
   *
   * @return boolean
   */
  public static function is_supported();
  
}

?>