<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Specification for a command line option
 */
class CriticalI_OptionSpec {
  const NONE = 0;
  const REQUIRED = 1;
  const OPTIONAL = 2;
  
  public $name;
  public $argument_type; /** NONE, REQUIRED, or OPTIONAL **/
  public $argument_name;
  public $description;
  
  /**
   * Constructor
   *
   * @param string $name          The option name (no leading --)
   * @param int    $argument_type Argument type (NONE, REQUIRED, or OPTIONAL)
   * @param string $argument_name The argument name (when applicable), used for help output
   * @param string $description   Optional description for help output
   */
  public function __construct($name = null, $argument_type = self::NONE, $argument_name = null, $description = null) {
    $this->name = $name;
    $this->argument_type = $argument_type;
    $this->argument_name = $argument_name;
    $this->description = $description;
  }
  
  /**
   * Helper function to produce partial usage text from a list of
   * CriticalI_OptionSpec objects.
   *
   * @param array $specs  The array of CriticalI_OptionSpec objects to produce usage text for
   * @return string
   */
  public static function usage_text($specs) {
    $txt = '';
    foreach ($specs as $spec) {
      $txt .= '  --' . $spec->name;
      if ($spec->argument_type == self::REQUIRED)
        $txt .= '=' . (empty($spec->argument_name) ? 'value' : $spec->argument_name);
      elseif ($spec->argument_type == self::OPTIONAL)
        $txt .= '[=' . (empty($spec->argument_name) ? 'value' : $spec->argument_name) . ']';
      
      $txt .= "\n";
      
      if ($spec->description) {
        $txt .= "               " . wordwrap($spec->description, 60, "\n               ") . "\n";
      }
      
      $txt .= "\n";
    }
    
    return $txt;
  }
}

?>