<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

// set the include path
$ROOT_DIR = dirname(__FILE__);
$INCLUDE_PATH = ini_get('include_path');
$PATH_SEPARATOR = (strpos($INCLUDE_PATH, ';') === FALSE) ? ':' : ';';
$INCLUDE_PATH = implode($PATH_SEPARATOR, array(
    "$ROOT_DIR/models",
    "$ROOT_DIR/controllers",
    "$ROOT_DIR/config",
    "$ROOT_DIR/lib",
    "$ROOT_DIR/vendor",
    $INCLUDE_PATH));
$CRITICALI_RUNTIME_SEARCH_DIRECTORIES = explode($PATH_SEPARATOR, $INCLUDE_PATH);

ini_set('include_path', $INCLUDE_PATH);

// allow relative URLs to be used in log4php.xml.
chdir($ROOT_DIR);
if (Cfg::exists('logging/config'))
  define('LOG4PHP_CONFIG_FILENAME', Cfg::get('logging/config'));

// define an autoload function
function __autoload($classname) {
  global $CRITICALI_RUNTIME_SEARCH_DIRECTORIES;
  
  $parts = explode('_', $classname);
  $filename = implode('/', $parts) . ".php";
  
  foreach ($CRITICALI_RUNTIME_SEARCH_DIRECTORIES as $dir) {
    if (file_exists("$dir/$filename")) {
      require_once($filename);
      return;
    }
  }
}

// define a global exception handler
function unhandled_exception($exception) {
    $msg = "Unhandled exception (".get_class($exception)."): ".
      $exception->getMessage()." / $exception";
    error_log($msg);
    Support_Resources::logger('Exception')->error($msg);

    $tpl = Support_Resources::template_engine();
    $tpl->assign('error', $exception->getMessage());
    $tpl->display('layouts/exception.tpl');

    if ($to = Cfg::get('mail/exception_to')) {
      $msg = "Server: $_SERVER[SERVER_NAME]\n\n" .
        "Unhandled Exception (" . get_class($exception) . "): " .
        $exception->getMessage() . "\n\n" .
        "Backtrace:\n" . $exception->getTraceAsString() . "\n\n" .
        "Server Info: " . print_r($_SERVER, true) . "\n" .
        "Request Info: " . print_r($_REQUEST, true);
      mail($to, 'Unhandled Exception', $msg);
    }
}

set_exception_handler('unhandled_exception');

// start the session
session_start();

// load any library initialization
if (file_exists("$ROOT_DIR/vendor/.packages")) {
  if (!defined('_Q'))
    define('_Q', "\"");

  $data = parse_ini_file("$ROOT_DIR/vendor/.packages", true);
  if (($data !== false) && isset($data['init_files'])) {
    $scripts = explode(',', $data['init_files']);
    foreach ($scripts as $script) { include_once($script); }
  }
}

// do local initialization
if (file_exists("$ROOT_DIR/config/environment.php"))
  include_once("environment.php");

?>
