<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * List command
 */
class CriticalI_Command_PropRemove extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('prop-remove', 'Remove a property from the repository', <<<DESC
  criticali prop-remove [options] name1 [...nameN]
  
Remove the named property or properties from the repository. Wildcard
characters * and ? can be used in the name to match multiple properties.
DESC
, array(
  new CriticalI_OptionSpec('quiet', CriticalI_OptionSpec::NONE, null, 'Limits output to error messages and any required prompts for information.'),
  new CriticalI_OptionSpec('no', CriticalI_OptionSpec::NONE, null, 'Assumes no as the answer to any confirmations.'),
  new CriticalI_OptionSpec('yes', CriticalI_OptionSpec::NONE, null, 'Assumes yes as the answer to any confirmations.')
  ));
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    if (count($this->args) < 1) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }
    
    $propertyNames = array();
    
    foreach ($this->args as $arg) {
      // wildcard
      if ((strpos($arg, '*') !== false) || (strpos($arg, '?') !== false))
        $propertyNames = array_merge($propertyNames, array_keys($this->match_wildcard($this->args[0])));
      // normal
      elseif (CriticalI_Property::exists($arg))
        $propertyNames[] = $arg;
    }

    $propertyNames = array_unique($propertyNames);
    
    if (count($propertyNames) == 0)
      return;

    if ( (!$this->options['quiet']) || ((!$this->options['yes']) && (!$this->options['no'])) )
      $this->display_properties($propertyNames);
    
    $proceed = false;
    
    if ($this->options['yes'] || $this->options['no'])
      $proceed = $this->options['no'] ? false : true;
    else
      $proceed = $this->prompt_confirm("Delete these properties?", true);

    if ($proceed)
      CriticalI_Property::remove_multiple($propertyNames);
  }
  
  /**
   * Search the list of properties for ones matching our pattern
   */
  protected function match_wildcard($pattern) {
    // convert the pattern into a regular expression
    $regex = str_replace(
      array("\\E", '*', '?', '/'),
      array("\\E\\\\E\\Q", "\\E.*\\Q", "\\E.?\\Q", "\\E\\/\\Q"),
      $pattern
      );
    
    $regex = "/\\A\\Q$regex\\E\\z/";
    
    $matches = array();
    
    foreach (CriticalI_Property::all() as $prop=>$value) {
      if (preg_match($regex, $prop))
        $matches[$prop] = $value;
    }
    
    return $matches;
  }
  
  /**
   * List the properties that will be deleted
   */
  protected function display_properties($properties) {
    // sort the list
    sort($properties);
    
    print "The following properties will be deleted:\n\n" .
      implode("\n", $properties) . "\n\n";
  }
  
}

?>