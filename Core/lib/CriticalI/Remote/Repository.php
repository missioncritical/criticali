<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * CriticalI_Remote_Repository is used to interact with a remote collection
 * of packages available for installation.
 */
class CriticalI_Remote_Repository  {
  
  protected static $defaultRemotes = null;
  
  protected $url;
  protected $index;
  
  /**
   * Constructor
   *
   * @param string $url The base URL of the repository
   */
  public function __construct($url) {
    $this->url = $url;
    $this->index = null;
  }
  
  /**
   * Returns the index of packages available from the remote
   * @param boolean $reload If true, forces the index to be reloaded (a cached version cannot be returned)
   * @return array
   */
  public function index($reload = false) {
    if ($reload || is_null($this->index)) {
      if (($fh = fopen($this->url . '/criticali-index', 'rb')) === false)
        throw new Exception("Could not access the index of remote repository ".$this->url);
      
      $data = '';
      $chunk = fread($fh, 4096);
      while (($chunk !== false) && (strlen($chunk) > 0)) {
        $data .= $chunk;
        $chunk = fread($fh, 4096);
      }
      
      if ($chunk === false)
        throw new Exception("Error while reading index from remote repository ".$this->url);
      
      $this->index = unserialize($data);
      
      if ($this->index === false)
        throw new Exception("Invalid or corrupt index retrieved from repository ".$this->url);
    }
    
    return $this->index;
  }
  
  /**
   * Fetches the named package version from the remote and stores it at
   * the provided location.
   * @param string $packageName The name of the package to retrieve
   * @param string $packageVersion The version of the package to retrieve
   * @param string $filename The file name where the package should be stored
   */
  public function fetch_package($packageName, $packageVersion, $filename) {
    // determine where to get this from
    $idx = $this->index();
    
    foreach ($idx as $pkgInfo) {
      if ($pkgInfo['name'] == $packageName && $pkgInfo['version'] == $packageVersion) {

        $path = $this->url . '/' . $pkgInfo['path'];

        if (copy($path, $filename) === false)
          throw new Exception("Failed retrieving package $packageName $packageVersion from repository ".
            $this->url);
        
        return;
      }
    }
    
    throw new Exception("Package $packageName $packageVersion is not available from ".$this->url);
  }
  
  /**
   * Return the default list of remote repositories
   */
  public static function default_remotes() {
    if (!self::$defaultRemotes) {
      self::$defaultRemotes = array();
      
      $rawRemotes = CriticalI_Property::get('remotes', CriticalI_Defaults::REMOTES);
      if (trim($rawRemotes)) {
      
        foreach(explode("\n", $rawRemotes) as $remote) {
          self::$defaultRemotes[] = new CriticalI_Remote_Repository(trim($remote));
        }
      }
      
    }
    
    return self::$defaultRemotes;
  }
  
}
