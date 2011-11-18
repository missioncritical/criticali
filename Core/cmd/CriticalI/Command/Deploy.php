<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Wrap command
 */
class CriticalI_Command_Deploy extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('deploy', 'Deploy a a wrapped package', <<<DESC
  criticali deploy directory package_file1 [...package_fileN]
  
Deploys the wrapped package(s) to a directory acting as a remote
repistory.
DESC
    );
  }

  /**
   * Run the command
   */
  protected function run_command() {
    if (count($this->args) < 2) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }
    
    $root = array_shift($this->args);
    
    if (!is_dir($root)) {
      fwrite(STDERR, "$root must be a directory.\n");
      exit(1);
    }
    
    foreach ($this->args as $packageFile) {
      $this->deploy_package($packageFile, $root);
    }

  }
  
  /**
   * Deploy a package
   */
  protected function deploy_package($packageFile, $destination) {
    if (!is_file($packageFile))
      throw new Exception("$packageFile not found.\n");
    
    $wrapper = new CriticalI_Package_Wrapper($packageFile);
    
    $name = $wrapper->package_name();
    $version = $wrapper->package_version();
    $cleanName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
    
    // build the property information for the package
    $properties = array(
      'name'=>$name,
      'version'=>$version,
      'path'=>"$cleanName/$cleanName-$version.cip",
      'properties'=>array()
    );
    
    foreach (array('package.name', 'package.version', 'package.summary',
      'package.description', 'dependencies') as $prop) {
        $val = $wrapper->property($prop);
        if (!is_null($val)) $properties['properties'][$prop] = $val;
    }
    
    // the deployment directory must exist
    if (!is_dir("$destination/$cleanName")) {
      if (!mkdir("$destination/$cleanName", 0777))
        throw new Exception("Could not create directory $destination/$cleanName\n");
    }
    
    // put the file in place
    if (!copy($packageFile, "$destination/$cleanName/$cleanName-$version.cip"))
      throw new Exception("Could not copy package file $packageFile to directory.");
    
    // now, update the index

    // start with the existing data
    $index = array();
    if (file_exists("$destination/criticali-index")) {
      if (($data = file_get_contents("$destination/criticali-index")) === false)
        throw new Exception("Could not load $destination/criticali-index");

      if ( (($index = unserialize($data)) == false) || (!is_array($index)) )
        throw new Exception("Could not understand the contents of $destination/criticali-index");
    }
    
    // remove any duplicates
    for ($i = count($index) - 1; $i >= 0; $i--) {
      if ($index[$i]['name'] == $name && $index[$i]['version'] == $version)
        array_splice($index, $i, 1);
    }
    
    // add our information
    $index[] = $properties;
    usort($index, array($this, 'sort_index'));
    
    // save it
    if (file_put_contents("$destination/criticali-index", serialize($index)) == false)
      throw new Exception("Could not save index to $destination/criticali-index");
  }
  
  /**
   * Sort callback
   */
  public function sort_index($a, $b) {
    if (($cmp = strcmp($a['name'], $b['name'])) == 0)
      return strcmp($a['version'], $b['version']);
    else
      return $cmp;
  }

}

?>