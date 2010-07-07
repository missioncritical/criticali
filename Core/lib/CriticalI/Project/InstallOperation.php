<?php

/**
 * Utilities for tracking and performing install operations for a given
 * package in a project.
 */
class Vulture_Project_InstallOperation {
  protected $project;
  protected $packageVersion;
  protected $dependencies;
  protected $filesAdded;
  
  /**
   * Constructor
   *
   * @param Vulture_Project $project  The project the install operation is for
   * @param Vulture_Package_Version $packageVersion The package being installed
   */
  public function __construct($project, $packageVersion) {
    $this->project = $project;
    $this->packageVersion = $packageVersion;
    $this->dependencies = array();
    $this->filesAdded = array();
  }
  
  /**
   * Return the project associated with this installation
   * @return Vulture_Project
   */
  public function project() {
    return $this->project;
  }
  
  /**
   * Return the package being installed
   * @param Vulture_Project_Version
   */
  public function package_version() {
    return $this->packageVersion;
  }
  
  /**
   * Return the dependency string for installation
   */
  public function dependency_string() {
    return implode(',', $this->dependencies);
  }
  
  /**
   * Return the set of files added during the installation
   */
  public function file_list() {
    return $this->filesAdded;
  }
  
  /**
   * Add an item to the dependency list
   *
   * @param string $pkgName  The name of the required package
   * @param string $versionSpec The version specification associated with the package
   */
  public function add_dependency_item($pkgName, $versionSpec) {
    $this->dependencies[] = "$pkgName=$versionSpec";
  }
  
  /**
   * Copy a file into the project.  Creates any needed directories.  If a
   * directory is given as the source, the entire directory is copied
   * recursively to the destination.  Note that when copying directories
   * recursively, any child directories whose names begin with a dot are
   * ignored.  If the destination is given as a relative path, it is
   * assumed to be relative to the project root.
   *
   * @param string $src  Source file
   * @param string $dest Destination filename
   */
  public function copy($src, $dest) {
    $destPrefix = '';
    if (!$this->is_absolute($dest))
      $destPrefix = $this->project->directory() . '/';
      
    if ($this->project->status_listener())
      $this->project->status_listener()->debug($this->project, null, "Copying $src to $destPrefix$dest");
    
    // make the requisite directories
    $destDir = dirname($dest);
    if (!file_exists($destPrefix . $destDir))
      $this->mkdir($destDir);
    
    // files can't be turned into directories
    if (!is_dir($destPrefix . $destDir))
      throw new Exception("Cannot copy file to $destPrefix$dest.  $destPrefix$destDir is not a directory.");
      
    // see if we're copying a directory
    if (is_dir($src)) {
      // make the destination directory
      $this->mkdir($dest);
      
      // recurse into it
      if (($dh = opendir($src)) === false)
        throw new Exception("Cannot access directory $src.");
      
      while (($entry = readdir($dh)) !== false) {
        // ignore hidden directories
        if (($entry[0] == '.') && (is_dir("$src/$entry")))
          continue;
        $this->copy("$src/$entry", "$dest/$entry");
      }
      
      closedir($dh);
      
    } else {
      // just a file, so copy the data
      $fhIn = false;
      $fhOut = false;
    
      try {
        if (($fhIn = fopen($src, 'rb')) === false)
          throw new Exception("Could not open file $src.");
        if (($fhOut = fopen($destPrefix . $dest, 'wb')) === false)
          throw new Exception("Could not open file $destPrefix$dest.");
        
        $this->filesAdded[] = $dest;
        
        $data = fread($fhIn, 4096);
        while (($data !== false) && (strlen($data) > 0)) {
          if (fwrite($fhOut, $data, strlen($data)) === false)
            throw new Exception("Error writing to file $destPrefix$dest.");
          $data = fread($fhIn, 4096);
        }
        if ($data === false)
          throw new Exception("Error reading from file $src.");
      
        fclose($fhIn);
        fclose($fhOut);
      
      } catch (Exception $e) {
        if ($fhIn) fclose($fhIn);
        if ($fhOut) fclose($fhOut);
        throw $e;
      }
    }
  }

  /**
   * Make a directory in the project.  Creates any needed directories
   * parent directories.  If the directory is given as a relative path,
   * it is assumed to be relative to the project root.
   *
   * @param string $dirname  The directory to make
   */
  public function mkdir($dirname) {
    $destPrefix = '';
    if (!$this->is_absolute($dirname))
      $destPrefix = $this->project->directory() . '/';
        
    if ($this->project->status_listener())
      $this->project->status_listener()->debug($this->project, null,
        "Creating directory $destPrefix$dirname");
    
    // make the requisite directories
    $checkDir = $dirname;
    $dirsToMake = array();
    while ($checkDir && (!file_exists($destPrefix . $checkDir))) {
      $dirsToMake[] = $checkDir;
      $checkDir = dirname($checkDir);
    }

    while (count($dirsToMake) > 0) {
      $newDir = array_pop($dirsToMake);
      if (!mkdir($destPrefix . $newDir, 0777))
        throw new Exception("Could not make directory $destPrefix$newDir.");
      $this->filesAdded[] = $newDir;
    }
  }
  
  /**
   * Abort a failed installation.  Removes all files that were created
   * and any directories that were created as long as they contain no
   * files (after removing added files).
   */
  public function abort() {
    $projectPrefix = $this->project->directory() . '/';
    
    if ($this->project->status_listener())
      $this->project->status_listener()->debug($this->project, null, "Aborting installation");
    
    // remove added files
    $dirs = array();
    foreach ($this->filesAdded as $file) {
      $fullname = $this->is_absolute($file) ? $file : ($projectPrefix . $file);
      if (is_dir($fullname))
        $dirs[] = $fullname;
      elseif (file_exists($fullname))
        unlink($filename);
    }
    
    rsort($dirs);
    foreach ($dirs as $dir) {
      if ($this->directory_entry_count($dir) === 0)
        rmdir($dir);
    }
  }
  
  /**
   * Test a path to see if it is absolute
   *
   * @param string $path  The path to test
   */
  protected function is_absolute($path) {
    return (preg_match("/^(?:[a-zA-Z]:)?[\\/\\\\]/", $path) > 0);
  }
  
  /**
   * Count the number of entries in a directory (not counting self and
   * parent)
   *
   * @param string $dir  The directory to check
   * @return int Count of entries or false on error
   */
  protected function directory_entry_count($dir) {
    $dh = opendir($dir);
    if ($dh === false) return false;
    
    $count = 0;
    while (($entry = readdir($dh)) !== false) {
      if ($entry != '.' && $entry != '..')
        $count++;
    }
    
    closedir($dh);
    
    return $count;
  }

}

?>