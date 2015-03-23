<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * SimpleDoc_Generator_Base is a base class for writing documentation
 * generators for simpledoc. A generator is responsible for writing the
 * final output files from the compiled documentation information provided
 * by a SimpleDoc_FileScanner.
 *
 * @package simpledoc
 */
abstract class SimpleDoc_Generator_Base {
  
  protected $outputLocation;
  protected $documentor;
  protected $scanner;
  protected $templateDir;
  protected $compileDir;
  protected $templateExtensions;
  protected $layout;
  protected $_runtime_variables;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->templateDir = dirname(__FILE__) . "/../../../doc-templates/default";
    $this->templateExtensions = array('php'=>'php');
    $this->layout = 'default';
    $this->_runtime_variables = array();
  }
  
  /**
   * Set the output location
   * @param string $dir The directory where documentation should be output
   */
  public function set_output_location($dir) {
    $this->outputLocation = $dir;
  }

  /**
   * Generate the documentation
   */
  public function generate($documentor, $scanner) {
    $this->documentor = $documentor;
    $this->scanner = $scanner;
    
    if (!file_exists($this->outputLocation))
      mkdir($this->outputLocation, 0777, true);
    
    $this->init_template_engines();
    
    $this->run();
    
    if ($this->compileDir && is_dir($this->compileDir))
      $this->rm_rd($this->compileDir);
  }
  
  /**
   * Classes implementing a generator must provide a run method which
   * produces the documentation.
   */
  protected abstract function run();
  
  /**
   * Return the name of the default layout
   * return string
   */
  protected function layout() {
    return $this->layout;
  }
  
  /**
   * Set the name of the default layout
   * @param string $name The layout name
   */
  protected function set_layout($name) {
    $this->layout = $name;
  }

  /**
   * Ensure a value is safe for use in a file name
   * @param string $value The value to use
   * @return string A copy of the original value with any unsafe or unpermitted
   * characters converted to underscores.
   */
  protected function file_safe($name) {
    return preg_replace('/[^,.0-9A-Z_a-z-]+/', '_', $name);
  }
  
  /**
   * Remove a directory and all files in it
   *
   * @param string $path The path to the directory to remove
   * @return bool
   */
  protected function rm_rd($path) {
    if (!is_dir($path))
      return unlink($path);
    
    $ok = true;
    $dh = opendir($path);
    if ($dh === false)
      return false;
    
    while ($ok && $entry = readdir($dh)) {
      if ($entry == '.' || $entry == '..')
        continue;
      
      $epath = "$path/$entry";
      if (is_dir($epath))
        $ok = $this->rm_rd($epath);
      else
        $ok = unlink($epath);
    }
    
    closedir($dh);
    
    $ok = rmdir($path);
    
    return $ok;
  }
  
  /**
   * Copy a file from the template directory to the output location.
   *
   * If `$src` is a directory instead of a file, the directory will
   * be copied recursively.
   *
   * @param string $src The path to the source file, relative to the
   * template directory
   * @param string $dest The path to copy the file to, relative to the
   * output location. If `$dest` is not provided, it will be the same
   * as `$src`
   */
  protected function cp($src, $dest = null) {
    if (!$dest) $dest = $src;
    
    $destPrefix = $this->outputLocation . '/';
    $src = $this->templateDir . '/' . $src;
    
    // see if we're copying a directory
    if (is_dir($src)) {
      // make the destination directory
      if (!is_dir($destPrefix . $dest))
        mkdir($destPrefix . $dest, 0777, true);
      
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
   * Render a template to the output directory
   *
   * ### Options
   *   * **layout** - The name of the layout to use
   *   * **skip_block_render** - Don't generate the content for the `block` template variable
   *
   * @param string $template The name of the template to render
   * @param string $dest     The file to output to. Defaults to the name of the template.
   * @param array  $options  Rendering options, see above
   */
  protected function render($template, $dest = null, $options = null) {
    if (is_array($dest) && $options === null) {
      $options = $dest;
      $dest = null;
    }
    if (!$dest) $dest = $template;
    $this->set_path_to_root($dest);
    
    list($tpl, $layout) = $this->prepare_for_render($template, $options);
    
    $destPath = $this->outputLocation . '/' . $dest;
    if (!is_dir(dirname($destPath)))
      mkdir(dirname($destPath), 0777, true);
    
    file_put_contents($destPath, $tpl->fetch($layout));
  }
  
  /**
   * Determine the path to the root output directory for the current
   * render operation.
   */
  protected function set_path_to_root($dest) {
    $path = pathinfo($dest, PATHINFO_DIRNAME);

    $path = preg_replace("/^\\/+/", '', $path);
    
    $parts = explode('/', $path);
    $rootParts = array();
    
    foreach($parts as $item) {
      $rootParts[] = $item == '.' ? $item : '..';
    }
    
    $this->path_to_root = implode('/', $rootParts);
    
    if (strlen($this->path_to_root) > 0)
      $this->path_to_root .= '/';
  }
  
  /**
   * Prepare template and layout for rendering.
   * 
   * @param string $template   The name of the template to render
   * @param array  $options    Any additional rendering options
   * @return array             Array of array($prepared_template, $layout_name)
   */
  protected function prepare_for_render($template, $options) {
    $ext = $this->extension_for_template($template);

    $tpl = Support_Resources::template_engine($this->templateExtensions[$ext]);
    if (method_exists($tpl, 'clear_all_assign'))
      $tpl->clear_all_assign();
      
    $hasLayout = true;
    $this->blocks = array();

    $this->assign_template_vars($tpl);
    
    $layout = (isset($options['layout']) && $options['layout'] === false) ? false : $this->layout();
    $layoutFile = "layouts/$layout.$ext";
    
    if ((!$layout) || (!file_exists($this->templateDir . '/' . $layoutFile))) {
      $hasLayout = false;
      $layoutFile = $template . '.' . $ext;
    }

    $this->set_template_defaults($tpl);
    $this->load_helpers($tpl);
    
    if ($hasLayout && (!@$options['skip_block_render'])) {
      $content = $tpl->fetch($template . '.' . $ext);
      
      $this->blocks = $tpl->get_template_vars('blocks');
      if (!is_array($this->blocks))
        $this->blocks = array();
      $this->blocks['content'] = $content;
      
      $tpl->assign('blocks', $this->blocks);
    }

    return array($tpl, $layoutFile);
  }
  
  /**
   * Return the extension to use for a named template
   */
  protected function extension_for_template($template) {
    foreach ($this->templateExtensions as $ext=>$engine) {
      if (is_file($this->templateDir . '/' . $template . '.' . $ext))
        return $ext;
    }
    
    return false;
  }
  
  /**
   * Assigns all public properties of the class as local variables in the
   * template
   *
   * @param object $tpl  The template to assign variables on
   */
  protected function assign_template_vars($tpl) {
    // all public properties are imported as variables

    // virtual ones
    foreach ($this->_runtime_variables as $name=>$value) {
      $tpl->assign($name, $value);
    }
    
    // and real ones
    $ref = new ReflectionObject($this);
    foreach ($ref->getProperties() as $prop) {
      if ($prop->isPublic()) {
        $name = $prop->getName();
        $tpl->assign($name, $this->$name);
      }
    }
  }
  
  /**
   * Set various template variables if they have not been set explicitly.
   * 
   * @param $template The template object
   */
  protected function set_template_defaults($tpl) {
    $tpl->template_dir  = $this->templateDir;
    
    if (isset($tpl->compile_dir))
      $tpl->compile_dir   = $this->compileDir;
    if (isset($tpl->plugins_dir))
      $tpl->plugins_dir[] = $this->templateDir . '/helper_plugins';
    
    $tpl->assign('controller', $this);
  }

  /**
   * Load helpers if the helper package is present and the template
   * engine supports them
   */
  protected function load_helpers($tpl) {
    if ((!class_exists('Helper_Loader')) || (!method_exists($tpl, 'register_helpers')))
      return;

    // mimick web configuration for Helper_Loader class
    if (!$GLOBALS['CRITICALI_RUNTIME_SEARCH_DIRECTORIES'])
      $GLOBALS['CRITICALI_RUNTIME_SEARCH_DIRECTORIES'] = array();
    
    $loader = new Helper_Loader();
    
    $options = array();

    $libDir = dirname(__FILE__) . '/../..';
    $options['directories'] = array($libDir=>"$libDir/SimpleDoc/Helper");
    
    if (class_exists('CriticalI_Package_List')) {
      $pkgList = CriticalI_Package_List::get();
      if (isset($pkgList['helper']))
        $options['directories'][
          "$GLOBALS[CRITICALI_ROOT]/" . $pkgList['helper']->newest()->installation_directory() . "/lib"] = 
          "$GLOBALS[CRITICALI_ROOT]/" . $pkgList['helper']->newest()->installation_directory() .
          "/lib/Helper/DefaultHelpers";
    }
    
    $loader->load($options);
    
    foreach ($loader->helpers() as $helper) {
      $helper->set_controller($this);
      $helper->set_template_engine($tpl);
    }
    
    $tpl->register_helpers($loader->helper_functions());
  }

  /**
   * Initialize template engines
   */
  protected function init_template_engines() {
    if (class_exists('CriticalI_Package_List') && (!class_exists('Smarty'))) {
      $pkgList = CriticalI_Package_List::get();
      if (isset($pkgList['smarty']))
        CriticalI_Package_List::add_package_to_autoloader('smarty');
      include_once('Smarty/Hook/Init.php');
    }
    
    if (!class_exists('Smarty'))
      return;
    
    $this->compileDir = $this->outputLocation . '/.compile';
    if (!file_exists($this->compileDir))
      mkdir($this->compileDir, 0777, true);
    
    $this->templateExtensions['tpl'] = 'smarty';
  }

  /**
   * Invoked when an attempt is made to set an undeclared instance
   * variable
   *
   * @param string $name The name of the variable
   * @param mixed $value The value to set
   */
  public function __set($name, $value) {
    $this->_runtime_variables[$name] = $value;
  }
  
  /**
   * Invoked when an attempt is made to retrieve the value of an
   * undeclared instance variable
   *
   * @param string $name The name of the variable
   *
   * @return mixed
   */
  public function &__get($name) {
    return $this->_runtime_variables[$name];
  }

  /**
   * Invoked when an attempt is made to call isset or empty for an
   * undeclared instance variable
   *
   * @param string $name The name of the variable
   *
   * @return boolean
   */
  public function __isset($name) {
    return isset($this->_runtime_variables[$name]);
  }

  /**
   * Invoked when an attempt is made to unset an undeclared instance
   * variable
   *
   * @param string $name The name of the variable
   */
  public function __unset($name) {
    unset($this->_runtime_variables[$name]);
  }

}
