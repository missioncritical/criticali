<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Handles writing new default values to a config.php file
 */
class CriticalI_Project_ConfigFileWriter {
  
  protected $filename;
  protected $skipWrite;
  protected $data;
  protected $origRaw;
  protected $rawFile;
  protected $rawSuffix;
  
  /**
   * Constructor
   */
  public function __construct($filename) {
    $this->filename = $filename;
    $this->skipWrite = false;
    $this->load_config();
  }
  
  /**
   * If no corresponding key in the configuration is set, defines a new value for it.
   *
   * @param string $key   The key to (potentially) set a default value for
   * @param string $value The new default value
   *
   * @return boolean Returns true if a value was set, false otherwise
   */
  public function set_default($key, $value) {
    $wrote = false;
    $config =& $this->data;
    $keys = explode('/', $key);
    $len = count($keys);
    
    for ($i = 0; $i < $len; $i++) {
      $test = $keys[$i];
      
      if (!isset($config[$test])) {
        
        $wrote = true;
        if ($i == ($len - 1)) {
          $config[$test] = $value;
          if (substr($this->rawFile, -1) != "\n") $this->rawFile .= "\n";
          $this->rawFile .= $this->assign_expr($keys) . "\"" . $this->slash($value) . "\";\n";
        } else {
          $config[$test] = array();
          if (substr($this->rawFile, -1) != "\n") $this->rawFile .= "\n";
          $this->rawFile .= $this->assign_expr(array_slice($keys, 0, $i+1)) . "array();\n";
        }

      } elseif (!is_array($config)) {
        return false;
      } else {
        $config =& $config[$test];
      }
    }
    
    return $wrote;
  }
  
  /**
   * Set the key to a new value, irrespective of any existing values.
   *
   * @param string $key   The key to set the value for
   * @param string $value The new value
   */
  public function set_value($key, $value) {
    $config =& $this->data;
    $keys = explode('/', $key);
    $len = count($keys);
    
    for ($i = 0; $i < $len; $i++) {
      $test = $keys[$i];
      
      if (!isset($config[$test])) {
        
        if ($i == ($len - 1)) {
          $config[$test] = $value;
          if (substr($this->rawFile, -1) != "\n") $this->rawFile .= "\n";
          $this->rawFile .= $this->assign_expr($keys) . "\"" . $this->slash($value) . "\";\n";
        } else {
          $config[$test] = array();
          if (substr($this->rawFile, -1) != "\n") $this->rawFile .= "\n";
          $this->rawFile .= $this->assign_expr(array_slice($keys, 0, $i+1)) . "array();\n";
        }

      } else {
        
        if ($i == ($len - 1)) {
          
          if ($config[$test] != $value) {
            $config[$test] = $value;
            if (substr($this->rawFile, -1) != "\n") $this->rawFile .= "\n";
            $this->rawFile .= $this->assign_expr($keys) . "\"" . $this->slash($value) . "\";\n";
          }

        } else {
          
          if (!is_array($config[$test])) {
            // overwrite the existing value
            $config[$test] = array();
            if (substr($this->rawFile, -1) != "\n") $this->rawFile .= "\n";
            $this->rawFile .= $this->assign_expr(array_slice($keys, 0, $i+1)) . "array();\n";
          } else {
            $config =& $config[$test];
          }
          
        }
      }
    }
  }
  
  /**
   * Writes any changes made out to the file
   */
  public function write_file() {
    if (!$this->skipWrite)
      file_put_contents($this->filename, $this->rawFile . $this->rawSuffix);
  }

  /**
   * Writes back the original file content (as it was loaded when this
   * class was created).
   */
  public function revert_file() {
    if (!$this->skipWrite)
      file_put_contents($this->filename, $this->origRaw);
  }

  /**
   * Returns an assignment expression for a given array of keys to use in
   * the output file.
   */
  protected function assign_expr($keys) {
    $escaped = array();
    foreach ($keys as $k) { $escaped[] = $this->slash($k); }
    return "\$APP_CONFIG[\"" . implode("\"][\"", $escaped) ."\"] = ";
  }
  
  /**
   * Adds slashes needed to enclose a value safely in double quotes in
   * PHP.
   * @param string $value  The value to escape
   * @return string
   */
  protected function slash($value) {
    $escaped =  addcslashes($value, "'\"\0\$\\");
    $escaped = preg_replace_callback("/(\\\\*)#\\{([^}]+)\\}/s",
      array($this, 'expand_raw_value'), $escaped);
    return $escaped;
  }
  
  /**
   * Handles {...} expansion in values
   */
  protected function expand_raw_value($matches) {
    $m = $matches[0];
    if (substr($m, 0, 2) == "\\\\") {
      if (substr($m, 0, 3) == "\\\\#")
        return substr($m, 2);
      return $m;
    }
    
    return $matches[1] . '".' . $matches[2] . '."';
  }
  
  /**
   * Load the configuration data from the file
   */
  protected function load_config() {
    global $APP_CONFIG;
    
    if (!file_exists($this->filename)) {
      $this->data = array();
      $this->rawFile = "<?php\n";
      $this->rawSuffix = "?>";
      return;
    }
    
    $APP_CONFIG = array();
    require($this->filename);

    $this->data = $APP_CONFIG;
    if (!is_array($this->data)) $this->data = array();
    
    $raw = file_get_contents($this->filename);
    if ($raw === false) {
      printf(STDERR, "Could not read configuration file $this->filename\n");
      $this->skipWrite = true;
      return;
    }
    
    $this->origRaw = $raw;
    $this->rawFile = $raw;
    $this->rawSuffix = '';

    if (($pos = strrpos($raw, '<?')) !== false) {
      $start = substr($raw, 0, $pos + 2);
      $rest = substr($raw, $pos + 2);
      
      if (substr($rest, 0, 3) == 'php') {
        $start .= substr($rest, 0, 3);
        $rest = substr($rest, 3);
      }
      
      if (($pos = strpos($rest, '?>')) !== false) {
        $this->rawFile = $start . substr($rest, 0, $pos);
        $this->rawSuffix = substr($rest, $pos);
      }
    }
  }
  
}

?>