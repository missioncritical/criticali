<?php
// Copyright (c) 2009-2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Doc command
 */
class SimpleDoc_Command_Doc extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('doc', 'Generate documentation for the named packages in the repository', <<<DESC
  criticali doc [options] package1 [...packageN]
  criticali doc [options] --all-packages
  
Generate documentation for the given packages in the repository.
DESC
, array(new CriticalI_OptionSpec('version', CriticalI_OptionSpec::REQUIRED, 'number', 'Generate documentation for the specified version (by default, only the most recent version is documented)'),
        new CriticalI_OptionSpec('output', CriticalI_OptionSpec::REQUIRED, 'directory', 'Output documentation to the named directory.  Default is "docs".'),
        new CriticalI_OptionSpec('all-packages', CriticalI_OptionSpec::NONE, null, 'Generate documentation for all installed packages.'),
        new CriticalI_OptionSpec('title', CriticalI_OptionSpec::REQUIRED, 'title', 'Global title for the generated documentation.'),
        new CriticalI_OptionSpec('index', CriticalI_OptionSpec::REQUIRED, 'file', 'The file to use as the index content for the generated document set.')));
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    CriticalI_Package_List::add_package_to_autoloader('simpledoc');
    
    if ( (isset($this->options['all-packages']) && count($this->args) > 0) ||
         ((!isset($this->options['all-packages'])) && count($this->args) < 1) ) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }
    
    $pkgList = CriticalI_Package_List::get();
    
    $toRun = array();
    
    // all-packages mode
    if (isset($this->options['all-packages'])) {
      
      foreach ($pkgList as $pkg) {
        if (isset($this->options['version'])) {
          if (isset($pkg[$this->options['version']]))
            $toRun[] = array($pkg, $pkg[$this->options['version']]);
        } else {
          $toRun[] = array($pkg, $pkg->newest());
        }
      }

    // listed packages mode
    } else {
    
      foreach($this->args as $pkgName) {
        if (!isset($pkgList[$pkgName]))
          throw new CriticalI_MissingPackageError($pkgName);
        $pkg = $pkgList[$pkgName];
      
        if (isset($this->options['version'])) {
          if (!isset($pkg[$this->options['version']]))
            throw new CriticalI_MissingPackageVersionError($pkgName, $this->options['version']);
          $ver = $pkg[$this->options['version']];
        } else {
          $ver = $pkg->newest();
        }
        
        $toRun[] = array($pkg, $ver);
      }
    }
    
    if (!$toRun) {
      fwrite(STDERR, "No matching packages were found.\n");
      exit(1);
    }
    
    // run it
    $this->document($toRun);
  }
  
  /**
   * Generate documentation for a collection of packages
   *
   * @param array $pkgs An array of package and version pairs
   */
  protected function document($pkgs) {
    $cfg = array();
    
    $searchDirs = array();
    
    // build the list of directories to search
    foreach ($pkgs as $coll) {
      list($pkg, $ver) = $coll;
      $dirs = $ver->property('document.directory', 'lib');
      $dirs .= ',' . $ver->property('document.directory.guides', 'doc');
      
      foreach (explode(',', $dirs) as $dir) {
        $path = $GLOBALS['CRITICALI_ROOT'] . '/' . $ver->installation_directory() . "/$dir";
        if (is_dir($path)) {
          $searchDirs[] = array(
              'name'=>$pkg->name(),
              'dir'=>$path,
              'dirPrefix'=> $GLOBALS['CRITICALI_ROOT'] . '/' . $ver->installation_directory() . "/"
            );
        }
      }
    }

    SimpleDoc_ConfigProvider::register();
    
    $engine = new SimpleDoc_Documentor();
    $engine->set_output_location(isset($this->options['output']) ? $this->options['output'] : 'docs');
    $engine->set_title(isset($this->options['title']) ? $this->options['title'] : 'Generated Documentation');
    
    if (isset($this->options['index']))
      $engine->set_documentation_index($this->options['index']);
    
    foreach ($searchDirs as $dir) {
      $engine->document_directory($dir['dir'], $dir['dirPrefix'], $dir['name']);
    }
    
    $engine->output_documents();
  }
  
}

?>