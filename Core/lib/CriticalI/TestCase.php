<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * This class exists as a convenience for writing PHPUnit tests for the
 * criticali framework and packages.  Currently its only special behavior
 * is to turn off global variable "backup" operations by PHPUnit.  The
 * problem is that some classes in the framework rely on global data
 * (largely related to bootstrapping, notably the default configuration
 * data implementation), and resetting global data without resetting the
 * state of static class members puts things in an inconsistent state.
 * The means if you're using this test case and global data, you're
 * responsible for cleaning up after yourself.  Most classes, however,
 * use no global data directly but do rely heavily on the framework which
 * may use a handful of global variables, so this is seems like a fair
 * trade-off.
 */
abstract class CriticalI_TestCase extends PHPUnit_Framework_TestCase {
  /**
   * Constructor
   * @param  string $name
   * @param  array  $data
   * @param  string $dataName
   */
  public function __construct($name = NULL, array $data = array(), $dataName = '') {
    parent::__construct($name, $data, $dataName);
    $this->backupGlobals = false;
  }
}

?>