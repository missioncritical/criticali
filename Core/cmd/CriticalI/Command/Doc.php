<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Doc command
 */
class CriticalI_Command_Doc extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('doc', 'Generate documentation for the named packages in the repository', <<<DESC
  criticali doc [options] package1 [...packageN]
  criticali doc [options] --all-packages
  
Generate documentation for the given packages in the
repository.
DESC
, array(new CriticalI_OptionSpec('version', CriticalI_OptionSpec::REQUIRED, 'number', 'Generate documentation for the specified version (by default, only the most recent version is documented)'),
        new CriticalI_OptionSpec('output', CriticalI_OptionSpec::REQUIRED, 'directory', 'Output documentation to the named directory.  Default is "docs".'),
        new CriticalI_OptionSpec('all-packages', CriticalI_OptionSpec::NONE, null, 'Generate documentation for all installed packages.'),
        new CriticalI_OptionSpec('title', CriticalI_OptionSpec::REQUIRED, 'title', 'Global title for the generated documentation.')));
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    $this->init_doc_framework();
    
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
   * Initialize the documentation framework
   */
  protected function init_doc_framework() {
    // PhpDocumentor is required
    $phpDocumentorLoc = false;
    $dirs = explode($GLOBALS['PATH_SEPARATOR'], $GLOBALS['INCLUDE_PATH']);
    foreach ($dirs as $dir) {
      if (file_exists("$dir/PhpDocumentor/phpDocumentor/Setup.inc.php")) {
        $phpDocumentorLoc = "$dir/PhpDocumentor";
        break;
      }
    }
    if ($phpDocumentorLoc === false) {
      fwrite(STDERR, "PhpDocumentor is required in order to generate documentation.  If you wish\n" .
        "to generate documentation, please install PhpDocumentor (this may be done\n" .
        "using pear).  If you have already installed PhpDocumentor, check that the\n" .
        "installation directory is correctly included in your default PHP include\n" .
        "path (it must be possible to\n" .
        "\"require('PhpDocumentor/phpDocumentor/Setup.inc.php');\").\n");
      exit(1);
    }
    
    // global var for PhpDocumentor
    $GLOBALS['_phpDocumentor_install_dir'] = $phpDocumentorLoc;
    
    // alter the include path
    $GLOBALS['INCLUDE_PATH'] .= $GLOBALS['PATH_SEPARATOR'] . $phpDocumentorLoc;
    ini_set('include_path', $GLOBALS['INCLUDE_PATH']);
    
    require_once('phpDocumentor/Setup.inc.php');
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
      
      foreach (explode(',', $dirs) as $dir) {
        $path = $GLOBALS['CRITICALI_ROOT'] . '/' . $ver->installation_directory() . "/$dir";
        if (is_dir($path)) $searchDirs[] = $path;
      }
    }
    
    // the calls to class_exists by the documentor will wreak havoc when
    // it tries to load everything we're documenting, so turn that off
    // temporarily
    $savedAutoloadDirs = $GLOBALS['CRITICALI_SEARCH_DIRECTORIES'];
    $GLOBALS['CRITICALI_SEARCH_DIRECTORIES'] = array();
    
    // assemble the set of options
    $cfg['hidden'] = 'off';
    $cfg['ignoresymlinks'] = 'off';
    $cfg['template'] = 'templates/default/';
    $cfg['output'] = 'HTML:frames:earthli';
    $cfg['target'] = 'docs';
    
    $cfg['directory'] = implode(',', $searchDirs);
    if (isset($this->options['output'])) $cfg['target'] = $this->options['output'];
    if (isset($this->options['title'])) $cfg['title'] = $this->options['title'];
    
    // now, kick it off
    $GLOBALS['_phpDocumentor_setting'] = $cfg;
    
    $phpDoc = new phpDocumentor_setup();
    $phpDoc->readCommandLineSettings();
    $phpDoc->setupConverters();
    $phpDoc->createDocs();
    
    // restore the autoload path
    $GLOBALS['CRITICALI_SEARCH_DIRECTORIES'] = $savedAutoloadDirs;
  }
  
}

?>