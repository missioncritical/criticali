<?php

/**
 * A collection of built-in default values which are shared between
 * classes.
 */
class Vulture_Defaults {
  
  const COMMAND_GLOB = 'cmd/*/Command/*.php';
  const CORE_COMMAND_GLOB = 'cmd/Vulture/Command/*.php';
  
  const LIBRARY_INSTALL_FROM = 'lib';
  const LIBRARY_INSTALL_GLOB = '*';
  
  const PROJECT_INSTALL_HOOKS = 'lib/*/Hook/ProjectInstall.php';
  const PROJECT_UNINSTALL_HOOKS = 'lib/*/Hook/ProjectUninstall.php';

  const INIT_HOOKS = 'lib/*/Hook/Init.php';
  
  /**
   * Constructor -- instantiation is not allowed
   */
  private function __constructor() {
    throw new Exception("Vulture_Defaults may not be instantiated.");
  }
}

?>