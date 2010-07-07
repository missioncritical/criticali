<?php

/**
 * General usage exceptions
 */
class CriticalI_UsageError extends Exception {
  public function __construct($msg) {
    parent::__construct($msg);
  }
}

?>