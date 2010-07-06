<?php

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
