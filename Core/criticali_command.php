<?php

define('CRITICALI_VERSION', '0.1');

$GLOBALS['CRITICALI_ROOT'] = dirname(__FILE__) . '/..';
$GLOBALS['INCLUDE_PATH'] = ini_get('include_path');
$GLOBALS['PATH_SEPARATOR'] = (strpos($GLOBALS['INCLUDE_PATH'], ';') === FALSE) ? ':' : ';';
$GLOBALS['INCLUDE_PATH'] = implode($GLOBALS['PATH_SEPARATOR'], array(
  $GLOBALS['CRITICALI_ROOT'] . "/Core/lib",
  $GLOBALS['INCLUDE_PATH']));

ini_set('include_path', $GLOBALS['INCLUDE_PATH']);
$GLOBALS['CRITICALI_SEARCH_DIRECTORIES'] = array($GLOBALS['CRITICALI_ROOT'] . "/Core/lib");

require_once("$GLOBALS[CRITICALI_ROOT]/Core/lib/command_autoloader.php");

class CriticalI_UnknownCommandError extends CriticalI_UsageError {
  public function __construct($cmd) {
    parent::__construct("Unrecognized command \"$cmd\"");
  }
}

/**
 * Prints a usage statement
 */
function show_usage($optionSpec) {
  return "Usage:\n\n" .
        "  criticali [criticali_options...] command [command_options...] [args...]\n\n" .
        "Type 'criticali help commands' for a list of commands.\n" .
        "Type 'criticali help command_name' for help on a specific command.\n\n" .
        "Valid criticali options:\n\n" .
        CriticalI_OptionSpec::usage_text($optionSpec);
}

$criticaliOptionSpec = array(new CriticalI_OptionSpec('help', CriticalI_OptionSpec::NONE, null, 'Show this help message.'),
  new CriticalI_OptionSpec('version', CriticalI_OptionSpec::NONE, null, 'Display version number and exit.'),
  new CriticalI_OptionSpec('skip-packages', CriticalI_OptionSpec::NONE, null, 'Skips loading of installed packages.  Useful for rebuilding package listing.'));

try {
  $options = new CriticalI_Options($argv, $criticaliOptionSpec);
  
  if (isset($options['help'])) {
    print show_usage($criticaliOptionSpec);
    exit(0);
  }
  
  if (isset($options['version'])) {
    print "criticali ".CRITICALI_VERSION."\n";
    exit(0);
  }
  
  if (isset($options['skip-packages']))
    CriticalI_Package_List::clear();
  
  // at a minimum we need a command
  $commandArgs = $options->arguments();
  if (count($commandArgs) < 1) {
    fwrite(STDERR, show_usage($criticaliOptionSpec));
    exit(1);
  }
  
  $allCommands = CriticalI_Command_List::get();
  if (!isset($allCommands[$commandArgs[0]]))
    throw new CriticalI_UnknownCommandError($commandArgs[0]);

  $allCommands[$commandArgs[0]]->execute($options, $commandArgs);
  
} catch (CriticalI_UsageError $e) {
  fwrite(STDERR, $e->getMessage()."\n\n".show_usage($criticaliOptionSpec));
  exit(1);
} catch (Exception $e) {
  fwrite(STDERR, "criticali aborted on exception ".get_class($e).": ".$e->getMessage()."\n\nBacktrace:\n".$e->getTraceAsString()."\n");
  exit(1);
}

?>