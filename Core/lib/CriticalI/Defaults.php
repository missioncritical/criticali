<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A collection of built-in default values which are shared between
 * classes.
 */
class CriticalI_Defaults {
  
  const COMMAND_GLOB = 'cmd/*/Command/*.php';
  const CORE_COMMAND_GLOB = 'cmd/CriticalI/Command/*.php';
  
  const LIBRARY_INSTALL_FROM = 'lib';
  const LIBRARY_INSTALL_GLOB = '*';
  
  const PROJECT_INSTALL_HOOKS = 'lib/*/Hook/ProjectInstall.php';
  const PROJECT_UNINSTALL_HOOKS = 'lib/*/Hook/ProjectUninstall.php';

  const INIT_HOOKS = 'lib/*/Hook/Init.php';
  
  /**
   * Constructor -- instantiation is not allowed
   */
  private function __constructor() {
    throw new Exception("CriticalI_Defaults may not be instantiated.");
  }
}

?>