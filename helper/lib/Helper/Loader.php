<?php
// Copyright (c) 2008-2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package helper */

/**
 * Helper_Loader handles locating and loading helper classes in a project.
 */
class Helper_Loader {
  
  protected $helpers;
  protected $methods;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->helpers = null;
    $this->methods = null;
  }
  
  /**
   * Locate and load some or all of the available helper classes.
   *
   * Options are:
   *  - <b>only</b>: If specified, load only the named helpers
   *  - <b>except</b>: If specified, load all but the named helpers
   *  - <b>directories</b>: An optional array of directories to search in place of the system defaults. Note that if a key is given for an entry, it is used as the base directory for determining the class name.
   *
   * @param array $options An associative array of options (see above)
   */
  public function load($options = null) {
    if (!is_array($options)) $options = array();
    
    Support_Util::validate_options($options, array('only'=>1, 'except'=>1, 'directories'=>1));

    // set the defaults
    $options = array_merge(array(
        'directories'=>Cfg::get('helper/directories',
          array("$GLOBALS[ROOT_DIR]/helper_plugins"=>"$GLOBALS[ROOT_DIR]/helper_plugins",
                "$GLOBALS[ROOT_DIR]/vendor"=>"$GLOBALS[ROOT_DIR]/vendor/Helper/DefaultHelpers"))
      ), $options);
      
    // initialize state
    $this->helpers = array();
    $this->methods = array();
    
    $directories = $options['directories'];

    $this->init_include_path($directories);
    
    // walk the directories
    foreach ($directories as $base=>$dir) {
      $class_names = $this->classes_in_directory($dir, (is_numeric($base) ? '' : $base));
      
      foreach ($class_names as $class_name) {
        $helper_name = $this->helper_name($class_name);
        
        if (isset($options['only'])) {
          if (in_array($helper_name, $options['only']))
            $this->try_add_helper($class_name);
            
        } elseif (isset($options['except'])) {
          if (!in_array($helper_name, $options['except']))
            $this->try_add_helper($class_name);
            
        } else {
          $this->try_add_helper($class_name);
        }
      }
    }
    
  }
  
  /**
   * Return the list of loaded helper classes indexed by helper name.
   *
   * @return array
   */
  public function helpers() {
    if (is_null($this->helpers))
      throw new Exception("Helpers have not been loaded.");
    
    return $this->helpers;
  }
  
  /**
   * Return the combined list of helper functions indexed by function name.
   *
   * @return array
   */
  public function helper_functions() {
    if (is_null($this->helpers))
      throw new Exception("Helpers have not been loaded.");
    
    return $this->methods;
  }

  /**
   * Ensure that the given list of directories are appropriately present
   * in the include path.
   */
  protected function init_include_path($directories) {
    foreach ($directories as $base=>$dir) {
      // choose a base if none is given
      if (is_numeric($base)) {
        // guess a base for the directory
        $prefix = '';
        $parts = $this->split_path($dir, $prefix);
        
        // folders with an initial capital may be part of the name
        while ($parts && (preg_match('/^[A-Z]/', $parts[count($parts)-1]))) {
          array_pop($parts);
        }
        
        $base = $prefix . implode('/', $parts);
      }
      
      // ensure the base is in the search path
      if (!in_array($base, $GLOBALS['CRITICALI_RUNTIME_SEARCH_DIRECTORIES'])) {
        $GLOBALS['CRITICALI_RUNTIME_SEARCH_DIRECTORIES'][] = $base;

        $GLOBALS['INCLUDE_PATH'] .= $GLOBALS['PATH_SEPARATOR'] . $base;
        ini_set('include_path', $GLOBALS['INCLUDE_PATH']);
      }
    }
  }
  
  /**
   * Return the individual directories from a path as a list
   *
   * @param string $path The path to split
   * @param string $prefix Output parameter for any non-path prefix (such as '/' or 'C:\\')
   * @return array
   */
  protected function split_path($path, &$prefix = null) {
    // normalize the path
    $path = str_replace("\\", '/', $path);
    
    // no absolute paths or other prefixes for our purposes
    $prefix = '';
    $matches = array();
    if (preg_match("/^([^:\\/]+:)(.*)$/", $path, $matches)) {
      $prefix .= $matches[1];
      $path = $matches[2];
    } if (preg_match("/^(\\/\\/[^\\/]+)(\\/.*)$/", $path, $matches)) {
      $prefix .= $matches[1];
      $path = $matches[2];
    } if (substr($path, 0, 1) == '/') {
      $prefix .= '/';
      $path = substr($path, 1);
    }
    
    // split it
    return explode('/', $path);
  }
  
  /**
   * Return the named classes found in a directory
   *
   * @param string $dir The directory to scan
   * @param string $base The optional class name base to use with the directory
   */
  protected function classes_in_directory($dir, $base = '') {
    $found = array();
    
    $dh = @opendir($dir);
    if ($dh === false) {
      Support_Resources::logger(get_class($this))->error("Could not read directory $dir.");
      return $found;
    }
    
    while (($file = readdir($dh)) !== false) {
      $path = "$dir/$file";
      if ( (substr($path, -10) == 'Helper.php') && (!is_dir($path)) ) {
        $class_name = $this->class_name($path, $base);
        if (class_exists($class_name))
          $found[] = $class_name;
      }
    }
    
    closedir($dh);
    
    return $found;
  }
  
  /**
   * Convert a path into the class name that should be contained within the file
   *
   * @param string $filename  The filename to convert
   * @param string $base      The optional base path to remove from the beginning
   */
  protected function class_name($filename, $base = '') {
    // normalize the paths
    $filename = str_replace("\\", '/', $filename);
    $base = empty($base) ? '' : str_replace("\\", '/', $base);
    
    // strip the base
    if (substr($filename, 0, strlen($base)) == $base)
      $filename = substr($filename, strlen($base));
      
    // split it
    $parts = $this->split_path($filename);
    
    // folders without an initial capital are not part of the name
    while ($parts && (!preg_match('/^[A-Z]/', $parts[0]))) {
      array_shift($parts);
    }
    
    // no extension for the last element
    if ($parts) {
      $last = array_pop($parts);
      if (($pos = strrpos($last, '.')) !== false)
        $last = substr($last, 0, $pos);
      if (!empty($last)) $parts[] = $last;
    }
    
    // assemble the class name
    return ($parts ? implode('_', $parts) : '');
  }

  /**
   * Add a helper to our list
   */
  protected function try_add_helper($class_name) {
    $helper_name = $this->helper_name($class_name);
    
    if (isset($this->helpers[$helper_name]))
      return;
    
    try {
      
      $helper = new $class_name();
      if (!$helper instanceof Helper_Base)
        throw new Exception("$class_name is not an instance of Helper_Base");
      
      $this->helpers[$helper_name] = $helper;
      $this->methods = array_merge($this->methods, $helper->helper_functions());
      
    } catch (Exception $e) {
      Support_Resources::logger(get_class($this))->error("Failed to load helper class $class_name: ".
        $e->getMessage());
    }
  }
  
  /**
   * Return the helper name for a class name
   */
  protected function helper_name($class_name) {
    $helper_name = $class_name;
    if (substr($helper_name, -6) == 'Helper')
      $helper_name = substr($helper_name, 0, -6);
    
    return Support_Inflector::underscore(str_replace('_', '/', $helper_name));
  }
  
}
