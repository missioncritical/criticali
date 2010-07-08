<?php
// Copyright (c) 2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package migration */

/**
 * Utility for running migrations
 */
class Migration_Runner {
  
  protected $directory;
  protected $migrations;
  
  /**
   * Create a new runner for a given directory and optional scope name.
   *
   * @param string $dir   The directory containing migration files
   * @param string $scope Optional scope name
   */
  public function __construct($dir, $scope = null) {
    $this->directory = $dir;
    $this->migrations = new Migration_List($dir, $scope);
    
    // normalize directory format
    if ((strlen($this->directory) > 0) && (substr($this->directory, -1) != '/') &&
        (substr($this->directory, -1) != "\\"))
      $this->directory .= '/';
  }
  
  /**
   * Run migrations.
   *
   * If no version is given, runs all available migrations which have not yet been run (runs migrations up to the latest), otherwise runs and/or reverses migrations up to the specified version number.
   *
   * @param string $version  Optional target version number
   */
  public function run($version = null) {
    // if no version is provided, use the last version
    if (is_null($version))
      $version = $this->migrations->last_version();
      
    // first perform anything up to the target version that has not yet been performed
    foreach ($this->migrations as $migration) {
      if ( (!$migration->performed) && (strnatcmp($migration->version, $version) <= 0) )
        $this->perform_migration($migration);
    }
    
    // then undo anything beyond the target version
    foreach (array_reverse($this->migrations->keys()) as $name) {
      $migration = $this->migrations[$name];
      if (strnatcmp($migration->version, $version) > 0)
        $this->reverse_migration($migration);
    }
  }
  
  /**
   * Return the current version number
   */
  public function version() {
    return $this->migrations->performed_version();
  }
  
  /**
   * Perform a migration
   */
  protected function perform_migration($migration) {
    $obj = $this->instantiate_migration($migration);
    $obj->up();
    $migration->performed = true;
    $migration->save_or_fail();
  }
  
  /**
   * Undo a migration
   */
  protected function reverse_migration($migration) {
    $obj = $this->instantiate_migration($migration);
    $obj->down();
    $migration->performed = false;
    $migration->destroy();
  }
  
  /**
   * Instantiate the class for a migration
   */
  protected function instantiate_migration($migration) {
    $klass = $migration->class_name();
    $filename = $this->directory . $migration->name . '.php';
    require_once($filename);
    
    if (!class_exists($klass))
      throw new Migration_InvalidMigrationError($filename, "does not define expected class \"$klass\"");
    
    $obj = new $klass();
    if (!($obj instanceof Migration_Base))
      throw new Migration_InvalidMigrationError($filename,
        "does not define an instance of Migration_Base");
    
    return $obj;
  }

}

?>