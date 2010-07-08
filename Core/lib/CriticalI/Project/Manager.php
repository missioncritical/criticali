<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

class CriticalI_Project_Manager {
  /**
   * Initialize a project
   *
   * @param string $name  Project name or directory (relative to cwd)
   * @param int $type CriticalI_Project::INSIDE_PUBLIC or CriticalI_Project::OUTSIDE_PUBLIC
   * @return CriticalI_Project
   */
  public static function init($name, $type) {
    self::mkdir($name);

    if ($type == CriticalI_Project::INSIDE_PUBLIC) {
      self::mkdir("$name/private");
      self::mkdir("$name/private/vendor");
      self::write_htaccess("$name/private/.htaccess");
      CriticalI_ConfigFile::write("$name/private/vendor/.packages", array());
    } else {
      self::mkdir("$name/vendor");
      self::mkdir("$name/public");
      CriticalI_ConfigFile::write("$name/vendor/.packages", array());
    }
    
    return new CriticalI_Project($name);
  }
  
  /**
   * Load a project
   *
   * @param string $dir  Directory to load (or cwd if not present)
   * @return CriticalI_Project
   */
  public static function load($name = null) {
    if (empty($name)) $name = getcwd();
    if ($name === false)
      $name = '.';
    return new CriticalI_Project($name);
  }
  
  /**
   * Writes a default .htaccess file.  Will not clobber an existing file.
   *
   * @param string $path  The path to the .htaccess file
   */
  protected static function write_htaccess($path) {
    if (file_exists($path)) {
      trigger_error("Will not overwrite existing htaccess file in $path.", E_USER_WARNING);
      return;
    }
    
    if (($fh = fopen($path, 'wb')) === false) {
      trigger_error("Cannot create file $path.", E_USER_WARNING);
      return;
    }
    
    $contents = "Order Allow,Deny\nDeny from all\n";
    
    if (fwrite($fh, $contents, strlen($contents)) === false)
      trigger_error("Could not write to file $path.", E_USER_WARNING);
    
    fclose($fh);
  }
  
  /**
   * Create a directory, if needed, and verify the path is a directory
   * @param string $path  The directory to make
   */
  protected static function mkdir($path) {
    if (!file_exists($path)) {
      if (!mkdir($path, 0777))
        throw new Exception("Could not create directory $path.");
    }
    if (!is_dir($path))
      throw new Exception("$path is not a directory.");
  }
}

?>