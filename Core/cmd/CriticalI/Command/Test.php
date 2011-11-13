<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Test command
 */
class CriticalI_Command_Test extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('test', 'Run unit tests for the named packages in the repository', <<<DESC
  criticali test package1 [...packageN]
  
Run the unit tests for the given packages in the repository.
DESC
, array(new CriticalI_OptionSpec('version', CriticalI_OptionSpec::REQUIRED, 'number', 'Run tests for the specified version (by default, only the most recent version is run)'),
        new CriticalI_OptionSpec('filter', CriticalI_OptionSpec::REQUIRED, 'pattern', 'Only run tests matching the provided regular expression.')));
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    $this->init_test_framework();
    
    if (count($this->args) < 1) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }
    
    $pkgList = CriticalI_Package_List::get();
    
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
      
      $this->run_tests_for($pkg, $ver);
    }
  }
  
  /**
   * Initialize the test framework
   */
  protected function init_test_framework() {
    // PHPUnit is required
    $hasPHPUnit = false;
    $dirs = explode($GLOBALS['PATH_SEPARATOR'], $GLOBALS['INCLUDE_PATH']);
    foreach ($dirs as $dir) {
      if (file_exists("$dir/PHPUnit/Framework.php")) {
        $hasPHPUnit = true;
        break;
      }
    }
    if (!$hasPHPUnit) {
      fwrite(STDERR, "PHPUnit is required in order to run unit tests.  If you wish to run unit\n" .
        "tests, please install PHPUnit (this may be done using pear).  If you have\n" .
        "already installed PHPUnit, check that the installation directory is\n" .
        "correctly included in your default PHP include path (it must be possible\n" .
        "to \"require('PHPUnit/Framework.php');\").");
      exit(1);
    }
    
    require_once('PHPUnit/Framework.php');
    require_once('PHPUnit/TextUI/TestRunner.php');
  }
  
  /**
   * Run the tests for a given package
   *
   * @param CriticalI_Package $pkg  The package to run
   * @param CriticalI_Package_Version $ver  The specific version of the package to run
   */
  protected function run_tests_for($pkg, $ver) {
    // see if the package even has tests
    $testDir = $GLOBALS['CRITICALI_ROOT'] . '/' .$ver->installation_directory() . '/' .
      $ver->property('test.directory', 'test');
    if (!is_dir($testDir))
      return; // no tests
    
    $oldDirs = $GLOBALS['CRITICALI_SEARCH_DIRECTORIES'];
    $oldInclude = $GLOBALS['INCLUDE_PATH'];
    
    CriticalI_Package_List::add_package_to_autoloader($pkg->name(), $ver->version_string().'!');
    
    // always run from the test directory
    $oldCwd = getcwd();
    chdir($testDir);
    
    // if there is a file called setup.php, run it
    if (file_exists("$testDir/setup.php"))
      require("$testDir/setup.php");
    
    // now, here we go
    $runner = new PHPUnit_TextUI_TestRunner();
    $suite = $runner->getTest('.', '', true);
    if ( ($suite->testAt(0) instanceof PHPUnit_Framework_Warning) &&
         (strpos($suite->testAt(0)->getMessage(), 'No tests found in class') !== false) ) {
      // nothing to run
    } else {
      $args = array();
      if (isset($this->options['filter'])) $args['filter'] = $this->options['filter'];
      $result = $runner->doRun($suite, $args);
    }
    
    if ($oldCwd !== false) chdir($oldCwd);
    // restore the old include path settings; offers slight protection for the next test
    $GLOBALS['CRITICALI_SEARCH_DIRECTORIES'] = $oldDirs;
    $GLOBALS['INCLUDE_PATH'] = $oldInclude;
    ini_set('include_path', $oldInclude);
  }
    
}

?>