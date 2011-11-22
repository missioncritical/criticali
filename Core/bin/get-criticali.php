#!/usr/bin/env php
<?php
/*
 * Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

define('REMOTE_REPOSITORIES', "http://criticali-packages1.missioncriticallabs.com\nhttp://criticali-packages2.missioncriticallabs.com");

assert_minimum_criteria_met();
determine_os();
pick_defaults();

print "\nCritical I[nfrastructure]\n\n";

print wordwrap("Welcome to the Critical I installer. The installer will ".
  "attempt to obtain and install the latest version of Critical I on your ".
  "machine. First we need to gather a little bit of information about ".
  "where to install it.\n\n");

$ok = false;

while (!$ok) {
  print wordwrap("Critical I primarily consists of a repository where it ".
    "stores packages that can be used in your development projects. The ".
    "repository should be an empty directory that does not contain any ".
    "other files. You can specifcy an existing directory or a location ".
    "where you would like one created.\n\n");

  $criticali_root = prompt_writable_directory("Where would you like to install the repository?",
    $criticali_root);

  if (WINDOZE) {
    print wordwrap("\nCritical I comes with a command line utility (called ".
      "\"criticali\"). On Windows systems this must be installed in a ".
      "location you choose in order to run. Ideally, that would be a ".
      "directorty listed in your PATH for easy use.\n\n");

  } else {
    print wordwrap("\nCritical I comes with a command line utility (called ".
      "\"criticali\"). A copy of it resides in the repository. It can also ".
      "be installed wherever you normally keep such scripts. Ideally, that ".
      "would be someplace inside your PATH (for easy use). Alternatively, ".
      "you can update your PATH to include the correct directory inside ".
      "the repository.\n\n");
  
    $install_script = prompt_confirm("Would you like to install the command ".
      "line utility in a separate\ndirectory?", $install_script);
  }

  if ($install_script) {
    $script_dir = prompt_writable_directory("Where would you like to install the utility?", $script_dir);
  }

  print wordwrap("\nOne last piece of information: we need to confirm that ".
    "we're using the right location for the version of PHP you'll be running ".
    "Critical I with. The default value is a best guess for where it's ".
    "located. Depending on your system, you may need to adjust this.\n\n");

  $php_binary = prompt("Where is the php command (command line version) located?",
    $php_binary);
  
  print "\nHere's how we'll install Critical I:\n\n";
  print "Repository location:   $criticali_root\n";
  print "Install command line:  " . ($install_script ? 'yes' : 'no') . "\n";
  if ($install_script)
    print "Command line location: $script_dir\n";
  print "PHP CLI:               $php_binary\n\n";
  
  $ok = prompt_confirm("Continue the installation with these settings?", false);
}



// now the actual installation
try {

  // get the package
  $availables = find_latest_version();
  $localPackage = download_package($availables);
  
  // unwrap it
  conditional_mkdir($criticali_root);
  unwrap_package($localPackage, $criticali_root);
  
  // install the command line
  if ($install_script) {
    conditional_mkdir($script_dir);
    install_script($script_dir, $criticali_root, $php_binary);
  }
  
  // initialize the repository
  repository_init($php_binary, $criticali_root);

} catch (Exception $e) {
  
  die("Installation failed with the following error: " .
    $e->getMessage() . "\n");
  
}

print "Installation complete\n";


/**
 * Ensures the minimum criteria to run this installer and support
 * CriticalI are met
 */
function assert_minimum_criteria_met() {
  $errors = array();
  
  // must be run from the command line
  if (php_sapi_name() != 'cli')
    $errors[] = "get-criticali must be run from the command line";
    
  // PHP 5 is required, 5.2 recommended
  if (version_compare(PHP_VERSION, '5.0.0', '<'))
    $errors[] = "Critical I requires PHP version 5.0 or above";
  elseif (version_compare(PHP_VERSION, '5.2.0', '<'))
    print("warning: PHP 5.2.0 or above is recommended for Critical I\n\n");
  
  // ZipArchive must be available
  if (!class_exists('ZipArchive'))
    $errors[] = "Critical I requires PHP to be built with zip support " .
      "(the class ZipArchive must be provided)";
  
  // http stream must be supported
  if (function_exists('stream_get_wrappers')) {
    if (array_search('http', stream_get_wrappers()) === false)
      $errors[] = "Critical I requires support for the http stream wrapper to be enabled";
  }
  
  if ($errors) {
    die("Critical I could not be installed due to the following errors:\n\n" .
      implode("\n", $errors) . "\n");
  }
}

/**
 * Determines minimal information about which OS you are running
 */
function determine_os() {
  if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
    define('WINDOZE', true);
  else
    define('WINDOZE', false);
}

/**
 * Pick default values for installation directories
 */
function pick_defaults() {
  global $criticali_root, $install_script, $script_dir, $php_binary;
  
  // pick a repository location
  $criticali_root = PHP_LIBDIR . DIRECTORY_SEPARATOR . 'criticali';
  if (!can_be_writable_directory($criticali_root)) {
    // try inside home
    if ($home = getenv('HOME'))
      $criticali_root = $home . DIRECTORY_SEPARATOR . 'criticali';
    
    // for windows, try inside documents
    if (WINDOZE && (!can_be_writable_directory($criticali_root)) &&
      ($homedrive = getenv('HOMEDRIVE')) && ($homepath = getenv('HOMEPATH')))
      $criticali_root = $homedrive . $homepath . DIRECTORY_SEPARATOR . 'criticali';
    
    // lastly, just use the current directory
    if (!can_be_writable_directory($criticali_root))
      $criticali_root = getcwd();
  }
  
  // default for script is always to install
  $install_script = true;
  
  // get the path for testing
  $path = getenv('PATH');
  if ($path)
    $path = explode(PATH_SEPARATOR, $path);
  else
    $path = array();
  
  // pick a script location
  $try = array_merge(array(PHP_BINDIR), $path);
  $try[] = getcwd();
  
  $script_dir = array_shift($try);
  while((!can_be_writable_directory($script_dir)) && $try) {
    $script_dir = array_shift($try);
  }
  
  // PHP binary location
  if (WINDOZE) {
    // this OS is, as always, the most broken
    $homedrive = getenv('HOMEDRIVE');
    if (!$homedrive) $homedrive = "C:";
    
    $try = $path;
    $try[] = $homedrive . DIRECTORY_SEPARATOR . 'php5';
    $try[] = $homedrive . DIRECTORY_SEPARATOR . 'php';
    $try[] = PHP_BINDIR;
    
    $binname = 'php.exe';
  
  } else {
    
    $try = array_merge(array(PHP_BINDIR), $path);
    $try[] = PHP_BINDIR; // first and last resort
    
    $binname = 'php';
    
  }
    
  $php_binary = array_shift($try) . DIRECTORY_SEPARATOR . $binname;
  while (!is_file($php_binary) && $try) {
    $php_binary = array_shift($try) . DIRECTORY_SEPARATOR . $binname;
  }

}

/**
 * Prompt the user and return the user's response
 *
 * @param string $prompt  The prompt to display for the user
 * @param string $default (optional) The default value to use if the enters no response
 * @return string
 */
function prompt($prompt, $default = false) {
  $result = false;
  
  while (!$result) {
    if ($default)
      print("$prompt [$default]: ");
    else
      print("$prompt: ");
    
    $result = trim(fgets(STDIN));
    
    if (!$result) $result = $default;
  }
  
  return $result;
}

/**
 * Prompt the user with a yes/no question and return the user's
 * response as a boolean
 *
 * @param string $prompt  The prompt to display for the user
 * @param boolean $default (optional) The default value to use if the enters no response
 * @return boolean
 */
function prompt_confirm($prompt, $default = null) {
  $result = null;
  
  while (is_null($result)) {
    if (is_null($default))
      $textDefault = false;
    else
      $textDefault = $default ? "yes" : "no";
    
    $try = prompt($prompt, $textDefault);
    
    if (preg_match("/y(?:es)?/i", $try))
      $result = true;
    elseif (preg_match("/no?/i", $try))
      $result = false;
    else
      print("Please enter \"yes\" or \"no\".\n");
  }
  
  return $result;
}

/**
 * Prompt the user to enter a path to a writable directory (or a path
 * that can be created as writable)
 *
 * @param string $prompt  The prompt to display for the user
 * @param string $default (optional) The default value to use if the enters no response
 * @return string
 */
function prompt_writable_directory($prompt, $default = null) {
  $result = null;
  
  while (is_null($result)) {
    
    $try = prompt($prompt, $default);

    if (can_be_writable_directory($try))
      $result = $try;
    else
      print("$try is not writable. Please enter a writable directory name.\n");
  }
  
  return $result;
}

/**
 * Tests to see if a path is a writable directory or can be made as a
 * writable directory
 *
 * @param string $path The path to test
 * @return boolean
 */
function can_be_writable_directory($path) {
  if (is_dir($path))
    return is_writable($path);

  if (file_exists($path))
    return false;
  
  $parts = explode(DIRECTORY_SEPARATOR, $path);
  array_pop($parts);
  
  if ( (count($parts) > 1) || (count($parts) > 0 && $parts[0] != '') )
    return can_be_writable_directory(implode(DIRECTORY_SEPARATOR, $parts));
  elseif (count($parts) == 1 && $parts[0] == '')
    return is_writable(DIRECTORY_SEPARATOR);
  elseif (count($parts) == 0)
    return is_writable('.');
  
  return false;
}

/**
 * Determine where the latest version of the criticali package can be
 * obtained
 * @return array
 */
function find_latest_version() {
  print "Searching for latest version of Critical I...";
  
  $versions = array();
  
  $repos = explode("\n", REMOTE_REPOSITORIES);
  
  foreach ($repos as $repo) {
    $repo = trim($repo);
    
    try {
      
      $index = unserialize(get_remote_file_contents("$repo/criticali-index"));
      
      if ($index) {
        foreach ($index as $entry) {
          if ($entry['name'] != 'criticali')
            continue;
          
          if (!isset($versions[$entry['version']]))
            $versions[$entry['version']] = array();
          
          $versions[$entry['version']][] = "$repo/$entry[path]";
        }
      }
      
    } catch (Exception $e) {
      // try the next repository...
    }
    
  }
  
  // must have at least one
  if (!$versions)
    throw new Exception("Could not find a Critical I package available for download.");

  // sort by version
  uksort($versions, 'version_compare');
  
  print "done\n";
  
  // return the newest
  return reset($versions);
}

/**
 * Sort of like file_get_contents, except it supports wrappers and throws
 * exceptions on error
 * @param string $url The location to fetch data from
 * @return string
 */
function get_remote_file_contents($url) {
  $fh = fopen($url, 'rb');
  if ($fh === false)
    throw new Exception("Could not access $url");
  
  $data = '';
  while ( (($chunk = fread($fh, 4096)) !== '') && ($chunk !== false) ) {
    $data .= $chunk;
  }
  
  if ($chunk === false)
    throw new Exception("Error reading from $url");
  
  fclose($fh);
  
  return $data;
}

/**
 * Create a temporary file and return its name. The file is
 * automatically cleaned up at the end of the script.
 *
 * @param string $prefix Optional prefix to use for the file name
 * @return string
 */
function tempfile($prefix = null) {
  // pick a temporary directory
  if (function_exists('sys_get_temp_dir'))
    $dir = sys_get_temp_dir();
  elseif ($dir = getenv('TMP'))
    ;
  elseif ($dir = getenv('TEMP'))
    ;
  elseif ($dir = getenv('TMPDIR'))
    ;
  else
    $dir = '/tmp';
  
  // must have a prefix
  if (!$prefix) $prefix = 'criticali';
  
  $filename = @tempnam($dir, $prefix);
  
  if ($filename == false)
    throw new Exception("Failed to create temporary file");
  
  register_shutdown_function(create_function('',
    "if (file_exists('$filename')) unlink('$filename');"));
  
  return $filename;
}

/**
 * Download a package from one of the available sources to a temporary
 * file and return the name of the temporary file.
 * @param array $sources The list of URLs where the package may be obtained
 * @return string
 */
function download_package($sources) {
  print "Dowloading package...";
  
  $localfile = tempfile();
  
  foreach ($sources as $url) {
    if (copy($url, $localfile))
      break;
  }
  
  print "done\n";
  
  return $localfile;
}

/**
 * Makes a directory (and any needed parent directories) if it does not
 * exist. Throws an exception on error
 * @param string $dir The directory to make
 */
function conditional_mkdir($dir) {
  if (is_dir($dir))
    return;

  print "Creating directory $dir...";
  
  if (!mkdir($dir, 0777, true))
    throw new Exception("Could not create directory $dir");
  
  print "done\n";
}

/**
 * Unwrap the package
 * @param string $package The local package file
 * @param string $criticali_root The desination directory
 */
function unwrap_package($package, $criticali_root) {
  $destination = $criticali_root . DIRECTORY_SEPARATOR . 'Core';
  conditional_mkdir($destination);
  
  print "Unpacking package...";
  
  $zip = new ZipArchive();
  if ($zip->open($package) !== true)
    throw new Exception("Could open downloaded package file");
  
  if (!$zip->extractTo($destination))
    throw new Exception("Could not expand downloaded archive");
  
  $zip->close();
  
  // last step: on normal OSes, mark the script executable
  if (!WINDOZE) {
    $script = $destination . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'criticali';
    if (!chmod($script, 0777 &~ umask()))
      trigger_error("Could not make $script executable", E_USER_WARNING);
  }

  print "done\n";
}

/**
 * Install the command line utility
 * @param string $script_dir The location to install the utility
 * @param string $criticali_root The installed repository location
 * @param string $php_binary The location of the php command
 */
function install_script($script_dir, $criticali_root, $php_binary) {
  print "Installing command line utility...";
  
  $installed = 1;
  
  $script_name = 'criticali';
  if (WINDOZE) $script_name .= '.bat';
  
  $full_script_path = $script_dir . DIRECTORY_SEPARATOR . $script_name;
  
  // get the script contents and expand any variables
  ob_start();
  require($criticali_root . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'bin' .
    DIRECTORY_SEPARATOR . $script_name);
  $script = ob_get_clean();
  
  // output it
  if (file_put_contents($full_script_path, $script) === false)
    throw new Exception("Could not create the script file $full_script_path");
  
  // on normal OSes, mark the script executable
  if (!WINDOZE) {
    if (!chmod($full_script_path, 0777 &~ umask()))
      trigger_error("Could not make $full_script_path executable", E_USER_WARNING);
  }
  
  print "done\n";
}

/**
 * Initialize the repository
 * @param string $php_binary The php binary
 * @param string $criticali_root The repository to initialize
 */
function repository_init($php_binary, $criticali_root) {
  print "Initializing respository...";
  
  $command = $criticali_root . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR .
    'criticali_command.php';
  
  $full_command = escapeshellarg($php_binary) . ' ' .
    escapeshellarg($command) . ' --skip-packages rebuild';
  
  if (WINDOZE && version_compare(PHP_VERSION, '5.3.0', '<'))
    $full_command = "\" $full_command \"";
  
  system($full_command, $exited);
  
  if ($exited !== 0)
    throw new Exception("$full_command returned $exited");
  
  print "done\n";
}

?>