<?php
// Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package cache */

/**
 * Indicates a request for an unsupported cache engine
 */
class Cache_UnsupportedEngineError extends Exception {
  public function __construct($name) {
    parent::__construct("Cache engine \"$name\" is not supported.");
  }
}

?>