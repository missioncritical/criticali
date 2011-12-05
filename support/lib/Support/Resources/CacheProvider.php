<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Interface for providing a cache store for Support_Resources
 */
interface Support_Resources_CacheProvider {
  /**
   * Return an interface to a cache store.
   *
   * @return object
   */
  public function get();
}

?>