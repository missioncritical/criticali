<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

abstract class ActiveRecord_Extension {
  /**
   * Get method names to add to the model.
   */
  public function get_proxies ( $model ) {
    return array ();
  }
  
  /**
   * Initialize model after proxies are added.
   */
  public function extend_model ( $model ) {
  }
}
