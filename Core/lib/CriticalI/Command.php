<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A CriticalI_Command implements a command that can be used with the
 * command-line criticali utility.  This is the base class which all
 * implemented commands must extend.
 */
abstract class CriticalI_Command {
  
  protected $name;
  protected $summary;
  protected $description;
  protected $optionSpec;
  
  protected $globalOptions;
  protected $options;
  protected $args;
  
  /**
   * Constructor
   *
   * @param string $name        The command literal
   * @param string $summary     A short description of the command (one sentence)
   * @param string $description A usage statement followed by a command description.
   * @param array  $optionSpec  An array of CriticalI_OptionSpec objects.
   */
  public function __construct($name, $summary = '', $description = '', $optionSpec = null) {
    $this->name = $name;
    $this->summary = $summary;
    $this->description = $description;
    $this->optionSpec = is_array($optionSpec) ? $optionSpec : array();
    $this->globalOptions = null;
    $this->options = null;
    $this->args = null;
  }
  
  /**
   * Returns the name of the command
   * @return string
   */
  public function name() {
    return $this->name;
  }
  
  /**
   * Returns the summary of the command
   * @return string
   */
  public function summary() {
    return $this->summary;
  }
  
  /**
   * Returns a usage/help statement for the command
   * @return string
   */
  public function help_string() {
    $str = "Usage:\n\n" .
          $this->description . "\n";
    if ($this->optionSpec)
      $str .= "\n" .
          "Valid options:\n\n" .
          CriticalI_OptionSpec::usage_text($this->optionSpec);
    return $str;
  }
  
  /**
   * Execute the command
   *
   * @param CriticalI_Options $globalOptions  Any top-level options passed to criticali
   * @param array $args                     The arguments passed to the command
   */
  public function execute($globalOptions, $args) {
    try {
      $this->globalOptions = $globalOptions;
      $this->options = new CriticalI_Options($args, $this->optionSpec);
      $this->args = $this->options->arguments();
      
      $this->run_command();
  
    } catch (CriticalI_UsageError $e) {
      fwrite(STDERR, $e->getMessage()."\n\n".$this->help_string());
      exit(1);
    }
  }
  
  /**
   * Internal implementation of the command.  This must be implemented by
   * all sub classes.  The properties globalOptions, options, and args
   * will have been populated when this method is called.
   */
  abstract protected function run_command();
  
  /**
   * Prompt the user and return the user's response
   *
   * @param string $prompt  The prompt to display for the user
   * @param string $default (optional) The default value to use if the enters no response
   * @return string
   */
  public function prompt($prompt, $default = false) {
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
  public function prompt_confirm($prompt, $default = null) {
    $result = null;
    
    while (is_null($result)) {
      if (is_null($default))
        $textDefault = false;
      else
        $textDefault = $default ? "yes" : "no";
      
      $try = $this->prompt($prompt, $textDefault);
      
      if (preg_match("/y(?:es)?/i", $try))
        $result = true;
      elseif (preg_match("/no?/i", $try))
        $result = false;
      else
        print("Please enter \"yes\" or \"no\".\n");
    }
    
    return $result;
  }
}

?>