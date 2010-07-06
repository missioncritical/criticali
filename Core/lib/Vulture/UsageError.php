<?php

/**
 * General usage exceptions
 */
class Vulture_UsageError extends Exception {
  public function __construct($msg) {
    parent::__construct($msg);
  }
}

?>