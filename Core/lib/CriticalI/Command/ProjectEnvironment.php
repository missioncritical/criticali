<?php

/**
 * A base class for writing commands that must run within a project's
 * environment (as opposed to just within the repository environment).
 */
abstract class CriticalI_Command_ProjectEnvironment extends CriticalI_Command {
  
  protected $project;
  
  /**
   * Return the directory that contains the non-public files for the project
   */
  protected function project_private_dir() {
    return $this->project->directory() .
      ($this->project->type() == CriticalI_Project::INSIDE_PUBLIC ? '/private' : '');
  }
   
  /**
   * Return the directory that contains the public files for the project
   */
  protected function project_public_dir() {
    return $this->project->directory() .
      ($this->project->type() == CriticalI_Project::INSIDE_PUBLIC ? '' : '/public');
  }
   
  /**
   * Set up the environment to pull in the project settings.  Generally
   * you will want to call this at the beginning of your run_command
   * method.  The parameter it accepts is the package directory.  If no
   * directory is given, the current working directory is assumed.
   *
   * @param string $package_dir  The package directory
   */
  protected function init_environment($package_dir = null) {
    // load the project
    $this->project = CriticalI_Project_Manager::load($package_dir);
    
    $prjDir = $this->project_private_dir();
    
    // we don't want to loose some of the repository-related settings, so
    // we can't just suck in the project init file, we have to do the
    // equivalent steps separately
    
    // autoload path
    foreach (array('models', 'controllers', 'config', 'lib', 'vendor') as $dir) {
      $path = "$prjDir/$dir";
      if (!in_array($path, $GLOBALS['CRITICALI_SEARCH_DIRECTORIES'])) {
        $GLOBALS['CRITICALI_SEARCH_DIRECTORIES'][] = $path;
        $GLOBALS['INCLUDE_PATH'] .= $GLOBALS['PATH_SEPARATOR'] . $path;
        ini_set('include_path', $GLOBALS['INCLUDE_PATH']);
      }
    }
    
    // logging config
    // TDB: do we force cwd to project dir to work with log4php config files?
    if (Cfg::exists('logging/config'))
      define('LOG4PHP_CONFIG_FILENAME', Cfg::get('logging/config'));
    
    // package init files
    if (file_exists("$prjDir/vendor/.packages")) {
      $data = CriticalI_ConfigFile::read("$prjDir/vendor/.packages");
      if (isset($data['init_files'])) {
        $scripts = explode(',', $data['init_files']);
        foreach ($scripts as $script) { include_once($script); }
      }
    }
    
    // environment.php
    if (file_exists("$prjDir/config/environment.php"))
      include_once("environment.php");
  }
  
}

?>