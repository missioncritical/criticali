<?php
// Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package cache */

/**
 * A cache store provider for use with Support_Resources
 */
class Cache_Provider implements Support_Resources_CacheProvider {

  protected $cacheStore;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->cacheStore = new Cache_Store();
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

?>