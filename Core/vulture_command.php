<?php

define('VULTURE_VERSION', '0.1');

$GLOBALS['VULTURE_ROOT'] = dirname(__FILE__) . '/..';
$GLOBALS['INCLUDE_PATH'] = ini_get('include_path');
$GLOBALS['PATH_SEPARATOR'] = (strpos($GLOBALS['INCLUDE_PATH'], ';') === FALSE) ? ':' : ';';
$GLOBALS['INCLUDE_PATH'] = implode($GLOBALS['PATH_SEPARATOR'], array(
  $GLOBALS['VULTURE_ROOT'] . "/Core/lib",
  $GLOBALS['INCLUDE_PATH']));

ini_set('include_path', $GLOBALS['INCLUDE_PATH']);
$GLOBALS['VULTURE_SEARCH_DIRECTORIES'] = array($GLOBALS['VULTURE_ROOT'] . "/Core/lib");

require_once("$GLOBALS[VULTURE_ROOT]/Core/lib/command_autoloader.php");

class Vulture_UnknownCommandError extends Vulture_UsageError {
  public function __construct($cmd) {
    parent::__construct("Unrecognized command \"$cmd\"");
  }
}

/**
 * Prints a usage statement
 */
function show_usage($optionSpec) {
  return "Usage:\n\n" .
        "  vulture [vulture_options...] command [command_options...] [args...]\n\n" .
        "Type 'vulture help commands' for a list of commands.\n" .
        "Type 'vulture help command_name' for help on a specific command.\n\n" .
        "Valid vulture options:\n\n" .
        Vulture_OptionSpec::usage_text($optionSpec);
}

$vultureOptionSpec = array(new Vulture_OptionSpec('help', Vulture_OptionSpec::NONE, null, 'Show this help message.'),
  new Vulture_OptionSpec('version', Vulture_OptionSpec::NONE, null, 'Display version number and exit.'),
  new Vulture_OptionSpec('skip-packages', Vulture_OptionSpec::NONE, null, 'Skips loading of installed packages.  Useful for rebuilding package listing.'));

try {
  $options = new Vulture_Options($argv, $vultureOptionSpec);
  
  if (isset($options['help'])) {
    print show_usage($vultureOptionSpec);
    exit(0);
  }
  
  if (isset($options['version'])) {
    print "vulture ".VULTURE_VERSION."\n";
    exit(0);
  }
  
  if (isset($options['skip-packages']))
    Vulture_Package_List::clear();
  
  // at a minimum we need a command
  $commandArgs = $options->arguments();
  if (count($commandArgs) < 1) {
    fwrite(STDERR, show_usage($vultureOptionSpec));
    exit(1);
  }
  
  $allCommands = Vulture_Command_List::get();
  if (!isset($allCommands[$commandArgs[0]]))
    throw new Vulture_UnknownCommandError($commandArgs[0]);

  $allCommands[$commandArgs[0]]->execute($options, $commandArgs);
  
} catch (Vulture_UsageError $e) {
  fwrite(STDERR, $e->getMessage()."\n\n".show_usage($vultureOptionSpec));
  exit(1);
} catch (Exception $e) {
  fwrite(STDERR, "vulture aborted on exception ".get_class($e).": ".$e->getMessage()."\n\nBacktrace:\n".$e->getTraceAsString()."\n");
  exit(1);
}

?>