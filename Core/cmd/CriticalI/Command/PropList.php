<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * List command
 */
class CriticalI_Command_PropList extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('prop-list', 'List the properties in the repository', <<<DESC
  criticali prop-list [name]
  
Lists the properties and their values that are stored in the repository.
Repository properties can be used to control things like the default
options for some commands. If a name is given on the command line, only
matching properties are listed. Wildcard characters * and ? can be used in
the name to match multiple properties.
DESC
, array() );
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    if (count($this->args) > 1) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }
    
    // all
    if (count($this->args) == 0) {
      $props = CriticalI_Property::all();

    // wildcard
    } elseif ((strpos($this->args[0], '*') !== false) || (strpos($this->args[0], '?') !== false)) {
      $props = $this->match_wildcard($this->args[0]);

    // single
    } else {
      $props = CriticalI_Property::exists($this->args[0]) ?
        array($this->args[0]=>CriticalI_Property::get($this->args[0])) : array();
    }

    if (count($props) > 0)
      $this->show_listing($props);
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
   * Show a the property listing
   */
  protected function show_listing($properties) {
    // sort the list
    ksort($properties);

    $table = new CriticalI_Command_TableFormatter(array('border-cell'=>' = '));
    
    foreach ($properties as $name=>$value) {
      $table->add_row(array($name, $value));
    }
    
    print $table->to_string();
  }
  
}

?>