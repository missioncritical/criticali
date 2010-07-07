<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Help command
 */
class CriticalI_Command_Help extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('help', 'Help for the criticali command', <<<DESC
  criticali help [command]
  criticali help commands
  
The first form displays help for the provided command, or
the system usage statement if no command is given.  The
second form displays a list of available commands.
DESC
);
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    global $criticaliOptionSpec;
    $allCommands = CriticalI_Command_List::get();
    
    if (count($this->args) < 1) {
      // system help message
      print show_usage($criticaliOptionSpec);

    } elseif ($this->args[0] == 'commands') {
      print "Available commands are:\n\n";
      foreach ($allCommands as $cmd) {
        print "  ".str_pad($cmd->name(), 15)." ".$cmd->summary()."\n";
      }
      print "\nFor help on a particular command type 'criticali help command_name'.\n";

    } else {
      if (!isset($allCommands[$this->args[0]])) {
        $msg = "Unknown command \"".$this->args[0]."\".\nType 'criticali help commands' for a list of commands.\n";
        fwrite(STDERR, $msg, strlen($msg));
        exit(1);
      }
      
      print $allCommands[$this->args[0]]->help_string();
    }
  }
}

?>