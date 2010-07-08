<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A package directory represents an uninstalled directory for a package.
 * It can be used to query the package for information.
 */
class CriticalI_Package_Directory {
  protected $directory;
  protected $properties;
  protected $name;
  protected $version;
  
  /**
   * Constructor
   *
   * @param string $name Package name or folder name containing the package
   * @param string $dir  The directory containing the package
   */
  public function __construct($name, $dir) {
    $this->directory = $dir;
    $version = $this->property('package.version', '0.0.0');
    $this->version = implode('.', CriticalI_Package_Version::canonify_version($version));
    $this->name = $name;
    if (substr($this->name, 0-strlen('-'.$this->version)) == ('-'.$this->version))
      $this->name = substr($this->name, 0, 0-strlen('-'.$this->version));
    $this->name = $this->property('package.name', $this->name);
  }
  
  /**
   * Return the directory being examined
   * @return string
   */
  public function directory() {
    return $this->directory;
  }

  /**
   * Return the package name
   * @return string
   */
  public function name() {
    return $this->name;
  }

  /**
   * Return the package version
   * @return string
   */
  public function version() {
    return $this->version;
  }

  /**
   * Return the value of a property for this package version
   *
   * @param string $name The name of the property to retrieve
   * @param mixed  $default The default value for the property if not found
   * @return mixed
   */
  public function property($name, $default = null) {
    if (is_null($this->properties))
      $this->properties = CriticalI_ConfigFile::read($this->directory."/package.ini");
    return isset($this->properties[$name]) ? $this->properties[$name] : $default;
  }
  
  /**
   * Test this directory to see if it has installed commands
   */
  public function has_commands() {
    $glob = $this->property('command.glob', CriticalI_Defaults::COMMAND_GLOB);
    return CriticalI_Globber::match($this->directory, $glob) ? true : false;
  }

}

?>