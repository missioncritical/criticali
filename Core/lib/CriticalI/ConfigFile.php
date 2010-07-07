<?php

/**
 * Indicates a config file could not be successfully read
 */
class CriticalI_ConfigFileReadError extends CriticalI_ConfigFileError {
  public function __construct($filename) {
    parent::__construct("Could not read config file \"$filename\"");
  }
}

/**
 * Indicates a config file could not be successfully written
 */
class CriticalI_ConfigFileWriteError extends CriticalI_ConfigFileError {
  public function __construct($filename) {
    parent::__construct("Could not read write to config file \"$filename\"");
  }
}

/**
 * Indicates a config file specified an incorrect version
 */
class CriticalI_ConfigFileVersionError extends CriticalI_ConfigFileError {
  public function __construct($filename) {
    parent::__construct("Incorrect version or no version specified in config file \"$filename\"");
  }
}

/**
 * Utilities for working with config files used by criticali.
 */
class CriticalI_ConfigFile {
  const CONFIG_VERSION = 1;
  
  /**
   * Constructor.
   *
   * Not allowed.  All methods are static.
   */
  private function __construct() {
    throw new Exception("Cannot instantiate CriticalI_ConfigFile class.");
  }
  
  /**
   * Read a config file and return the resulting array of data.
   *
   * @param string $filename  The filename to read
   * @return array
   */
  public static function read($filename) {
    if (!defined('_Q'))
      define('_Q', "\"");
    
    $data = parse_ini_file($filename, true);
    if ($data === false)
      throw new CriticalI_ConfigFileReadError($filename);
    if ((!isset($data['version'])) || ($data['version'] != self::CONFIG_VERSION))
      throw new CriticalI_ConfigFileVersionError($filename);
    
    return $data;
  }
  
  /**
   * Write configuration data to a file.
   *
   * @param string $filename  The filename to write to
   * @param array  $data      Structured data as returned by CriticalI_ConfigFile::read()
   * @param string $msg       Optional warning message to include at the beginning of the file
   */
  public static function write($filename, $data, $msg = null) {
    if (empty($msg))
      $msg = "This is an automatically generated file.\nEDIT AT YOUR OWN RISK!";
    
    $fh = fopen($filename, 'wb');
    if ($fh === false)
      throw new CriticalI_ConfigFileWriteError($filename);
    
    try {
      $data['version'] = self::CONFIG_VERSION;
    
      $msg = '; ' . str_replace("\n", "\n; ", wordwrap($msg)) . "\n";
      
      if (fwrite($fh, $msg, strlen($msg)) === false)
        throw new CriticalI_ConfigFileWriteError($filename);
      
      $sections = array();
      
      // output the actual data
      foreach ($data as $key=>$value) {
        // nested arrays indicate sections
        if (is_array($value)) {
          $sections[$key] = $value;
        } else {
        
          // everything else is a normal entry
          $line = "$key=".self::quote($value) . "\n";
          if (fwrite($fh, $line, strlen($line)) === false)
            throw new CriticalI_ConfigFileWriteError($filename);
          
        }
      }
      
      foreach ($sections as $section=>$sectionData) {
        $line = "[$section]\n";
        if (fwrite($fh, $line, strlen($line)) === false)
          throw new CriticalI_ConfigFileWriteError($filename);
          
        foreach ($sectionData as $key=>$value) {
          $line = "$key=".self::quote($value) . "\n";
          if (fwrite($fh, $line, strlen($line)) === false)
            throw new CriticalI_ConfigFileWriteError($filename);
        }
      }
      
      fclose($fh);
      
    } catch (Exception $e) {
      fclose($fh);
      throw $e;
    }
  }
  
  /**
   * Quotes a value for output to an ini file
   *
   * @param mixed $value  The value to quote
   * @return string
   */
  public static function quote($value) {
    switch (gettype($value)) {
      case 'integer':
      case 'double':
        return $value;
      
      case 'boolean':
        return ($value ? 'True' : 'False');
      
      case 'NULL':
        return '';
      
      case 'string':
        return '"' . str_replace('"', '"_Q"', $value) . '"';
      
      default:
        return '"' . str_replace('"', '"_Q"', serialize($value)) . '"';
    }
  }
  
}

?>