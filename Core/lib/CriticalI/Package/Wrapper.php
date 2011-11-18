<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A Wrapper contains an archived package version.
 */
class CriticalI_Package_Wrapper {
  protected $location;
  protected $properties;
  protected $name;
  protected $packageName;
  protected $packageVersion;
  
  /**
   * Constructor
   *
   * @param string $location The path or URL to the wrapper file
   * @param string $name Can be used to override the base file name when the wrapper is a temp file
   */
  public function __construct($location, $name = null) {
    $this->location = $location;
    $this->name = $name ? $name : basename($location, '.cip');

    $this->properties = null;
    $this->packageName = null;
    $this->packageVersion = null;
  }
  
  /**
   * Return the path or URL to the wrapper
   * @return string
   */
  public function location() {
    return $this->location;
  }
  
  /**
   * Return the name of this wrapper (the combined package name and
   * version)
   * @return string
   */
  public function name() {
    return $this->name;
  }
  
  /**
   * Return the contained package name
   * @return string
   */
  public function package_name() {
    if (!$this->packageName) {
      $this->packageName = $this->name;

      $ver = $this->package_version();
      if (substr($this->packageName, 0-strlen('-'.$ver)) == ('-'.$ver))
        $this->packageName = substr($this->packageName, 0, 0-strlen('-'.$ver));
    
      $this->packageName = $this->property('package.name', $this->packageName);
    }
    
    return $this->packageName;
  }
  
  /**
   * Return the contained package version number
   * @return string
   */
  public function package_version() {
    if (!$this->packageVersion) {
      $this->packageVersion = $this->property('package.version', '0.0.0');
      $this->packageVersion = implode('.',
        CriticalI_Package_Version::canonify_version($this->packageVersion));
    }
    
    return $this->packageVersion;
  }
  
  /**
   * Return the value of a property for the wrapped package version
   *
   * @param string $name The name of the property to retrieve
   * @param mixed  $default The default value for the property if not found
   * @return mixed
   */
  public function property($name, $default = null) {
    if (is_null($this->properties))
      $this->load_properties();
    return isset($this->properties[$name]) ? $this->properties[$name] : $default;
  }

  /**
   * Utility for creating a new Wrapper from a package version
   *
   * @param string $outfile The output location
   * @param CriticalI_Paackage_Version $version The version to wrap
   * @return CriticalI_Package_Wrapper
   */
  public static function create($outfile, $version) {
    $wrapper = new CriticalI_Package_Wrapper($outfile);
    
    $wrapper->wrap($version);
    
    return $wrapper;
  }
  
  /**
   * Extract a wrapped package to the given directory
   *
   * @param string $directory The output location
   */
  public function unwrap($directory) {
    // make sure we can read zip files
    $this->assert_zip_exists();
    
    // open the archive
    $zip = new ZipArchive();
    if (($code = $zip->open($this->location, ZipArchive::CREATE)) !== true)
      throw new CriticalI_Package_ZipError($this->location, null, $this->zip_status($zip), $code);
    
    // extract the package contents
    if (!$zip->extractTo($directory))
      throw new CriticalI_Package_ZipError($this->location, null, $this->zip_status($zip));
    
    // clean up
    if (!$zip->close())
      throw new CriticalI_Package_ZipError($this->location, null, $this->zip_status($zip));
  }
  
  /**
   * Load the properties from the archive
   */
  protected function load_properties() {
    // make sure we can read zip files
    $this->assert_zip_exists();
    
    $tmp = CriticalI_Util::tempfile('props');
    
    // open the archive
    $zip = new ZipArchive();
    if (($code = $zip->open($this->location, ZipArchive::CREATE)) !== true)
      throw new CriticalI_Package_ZipError($this->location, null, $this->zip_status($zip), $code);
  
    // extract just the package.ini file
    if ($stats = $zip->statName('package.ini')) {
      if (($data = $zip->getFromIndex($stats['index'])) === false)
        throw new CriticalI_Package_ZipError($this->location, null, $this->zip_status($zip));
      if (@file_put_contents($tmp, $data) === false)
        throw new Exception("Could not write to file: $tmp");
    }
  
    // clean up
    if (!$zip->close())
      throw new CriticalI_Package_ZipError($this->location, null, $this->zip_status($zip));

    // parse it
    $this->properties = CriticalI_ConfigFile::read($tmp);
  }
  
  /**
   * Throws an exception if this version of PHP does not have the right
   * ZIP support
   */
  protected function assert_zip_exists() {
    if (!class_exists('ZipArchive'))
      throw new Exception("This version of PHP appears to have been built " .
        "without zip support or it does not provide the ZipArchive class. " .
        "PHP must provide zip support in order to work with wrapped packages.");
  }
  
  /**
   * Called internally to assemble a new wrapper
   *
   * @param CriticalI_Paackage_Version $version The version to wrap
   */
  protected function wrap($version) {
    // make sure we can read and write zip files
    $this->assert_zip_exists();
    
    // create the archive
    $zip = new ZipArchive();
    if (($code = $zip->open($this->location, ZipArchive::CREATE)) !== true)
      throw new CriticalI_Package_ZipError($this->location, null, $this->zip_status($zip), $code);
    
    // insert the package contents
    $this->wrap_version_files($version, $zip);
    
    // clean up
    if (!$zip->close())
      throw new CriticalI_Package_ZipError($this->location, null, $this->zip_status($zip));
  }
  
  /**
   * Insert files from a package version into a new wrapper
   */
  protected function wrap_version_files($version, $zip) {
    $pkgDir = $GLOBALS['CRITICALI_ROOT'] . '/' . $version->installation_directory();
    
    $allFiles = $this->recursive_list($pkgDir);
    
    foreach ($allFiles as $file) {
      if (is_dir($file)) {
        $success = $zip->addEmptyDir($file);
      } else {
        $success = $zip->addFile("$pkgDir/$file", $file);
      }
      
      if (!$success)
        throw new CriticalI_Package_ZipError($this->location, $file, $this->zip_status($zip));
    }
  }
  
  /**
   * Recursively list the contents of a directory
   */
  protected function recursive_list($dir) {
    $files = array();
    
    $dh = opendir($dir);
    if ($dh === false)
      throw new Exception("Could not access directory $dir");
    
    while (($fname = readdir($dh)) !== false) {
      $children = false;
      
      if (is_dir("$dir/$fname")) {
        // ignore hidden directories
        if ($fname[0] == '.')
          continue;
        
        // descend
        $children = $this->recursive_list("$dir/$fname");
        
        $fname .= '/'; // required to indicate directory
      }
      
      $files[] = $fname;
        
      if ($children) {
        foreach ($children as $c) { $files[] = "$fname$c"; }
      }
    }
    
    closedir($dh);
    
    return $files;
  }
  
  /**
   * Return a status message from a zip object (if possible)
   */
  public function zip_status($zip) {
    if (method_exists($zip, 'getStatusString'))
      return $zip->getStatusString();
    return null;
  }
  
}

?>