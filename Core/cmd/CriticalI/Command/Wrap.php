<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Wrap command
 */
class CriticalI_Command_Wrap extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('wrap', 'Wrap a package for remote deployment', <<<DESC
  criticali wrap [options] package1 [...packageN]
  
Wrap the package(s) for deployment on a remote repository.
DESC
, array(new CriticalI_OptionSpec('version', CriticalI_OptionSpec::REQUIRED, 'number', 'Wrap the specified version (by default, only the most recent version is wrapped)'),
        new CriticalI_OptionSpec('out-dir', CriticalI_OptionSpec::REQUIRED, 'directory', 'Directory to place the wrapped package(s) in'),
        new CriticalI_OptionSpec('quiet', CriticalI_OptionSpec::NONE, null, 'Suppress output of status messages')));
  }

  /**
   * Run the command
   */
  protected function run_command() {
    if (count($this->args) < 1) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }
    
    $success = true;
    
    $pkgList = CriticalI_Package_List::get();
    
    foreach($this->args as $pkgName) {
      if (!isset($pkgList[$pkgName]))
        throw new CriticalI_MissingPackageError($pkgName);
      $pkg = $pkgList[$pkgName];
      
      if (isset($this->options['version'])) {
        if (!isset($pkg[$this->options['version']]))
          throw new CriticalI_MissingPackageVersionError($pkgName, $this->options['version']);
        $ver = $pkg[$this->options['version']];
      } else {
        $ver = $pkg->newest();
      }
      
      $where = isset($this->options['out-dir']) ? $this->options['out-dir'] : '.';
      
      $success |= $this->wrap_package($pkg, $ver, $where);
    }
    
    if (!$success)
      exit(1);
  }
  
  /**
   * Wraps a package
   */
  protected function wrap_package($pkg, $ver, $dir) {
    $dest = $dir . '/' . $pkg->clean_name() . '-' . $ver->version_string() . '.cip';
    
    if (file_exists($dest)) {
      fwrite(STDERR, "Skipping package ".$pkg->name()." because file $dest already exists.\n");
      return false;
    }
    
    if (!isset($this->options['quiet']))
      print "Packaging " . $pkg->name() . " " . $ver->version_string() . "...\n";
    
    CriticalI_Package_Wrapper::create($dest, $ver);
    
    return true;
  }

}

?>