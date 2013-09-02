<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * SimpleDoc_Documentor is the interface for generating documentation
 * on a set of files and directories.
 *
 * @package simpledoc
 */
class SimpleDoc_Documentor {

  protected $scanner;
  protected $outputLocation;
  protected $title;
  protected $generator;

  /**
   * Constructor
   */
  public function __construct() {
    $this->scanner = new SimpleDoc_FileScanner();
    $this->outputLocation = getcwd();
    $this->title = '';
    $this->generator = new SimpleDoc_Generator_Default();
  }
  
  /**
   * Set the output location
   * @param string $dir The directory where documentation should be output
   */
  public function set_output_location($dir) {
    $this->outputLocation = $dir;
  }
  
  /**
   * Get the documentation title to use
   * @return string
   */
  public function title() {
    return $this->title;
  }
  
  /**
   * Set the documentation title to use
   * @param string $title The title to use
   */
  public function set_title($title) {
    $this->title = $title;
  }
  
  /**
   * Add a directory to the list of files to document
   *
   * @param string $directory The directory to document
   * @param string $prefix    The path prefix (if any) to omit from the file
   * names when present in the documentation. For example, a prefix of
   * `"/usr/local/php/"` would cause a the file `"/usr/local/php/lib/php/PEAR.php"`
   * to be referred to as `"lib/php/PEAR.php"` in the documentation.
   * @param string $packageName The default package name to use for the files in the directory
   */
  public function document_directory($directory, $prefix = null, $packageName = null) {
    $this->scanner->set_default_package($packageName ? $packageName : 'default');
    $this->scanner->set_file_prefix($prefix ? $prefix : '');
    
    $was = error_reporting(E_ERROR | E_WARNING | E_PARSE);

    $this->scan_directory($directory);

    error_reporting($was);
  }

  /**
   * Output the documentation
   */
  public function output_documents() {
    $this->generator->set_output_location($this->outputLocation);
    $this->generator->generate($this, $this->scanner);
  }
  
  /**
   * Scan a directory
   */
  protected function scan_directory($directory) {
    $dh = @opendir($directory);
    if ($dh === false)
      throw new Exception("Could not scan directory \"$directory\"");
    
    while ($entry = readdir($dh)) {
      $path = "$directory/$entry";
      $isDir = is_dir($path);

      if ($entry[0] == '.' && $isDir)
        continue;
      
      if ($isDir)
        $this->scan_directory($path);
      else
        $this->scanner->scan($path);
    }
    
    closedir($dh);
  }
  
}
