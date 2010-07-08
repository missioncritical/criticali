<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Exception thrown for unknown/unsupported options
 */
class CriticalI_UnknownOptionError extends CriticalI_UsageError {
  public function __construct($option) {
    parent::__construct("Unknown option \"$option\".");
  }
}

/**
 * Exception thrown for invalid argument provided
 */
class CriticalI_InvalidOptionArgumentError extends CriticalI_UsageError {
  public function __construct($option, $argument) {
    parent::__construct("The option \"$option\" does not accept an argument (\"$argument\" provided, none expected).");
  }
}

/**
 * Exception thrown for missing argument
 */
class CriticalI_MissingOptionArgumentError extends CriticalI_UsageError {
  public function __construct($option) {
    parent::__construct("The option \"$option\" requires an argument (none provided, one expected).");
  }
}

/**
 * An processed command line option
 */
class CriticalI_Option {
  public $name;
  public $argument;
  
  public function __construct($name = null, $argument = null) {
    $this->name = $name;
    $this->argument = $argument;
  }
}

/**
 * Processes command line options.
 *
 * Once options have been processed, the CriticalI_Options object can be
 * treated as an array.  Treating it as a numerically indexed list allows
 * access to all options passed on the command line when an option is
 * specified more than once.  It can also be treated as an associative
 * array with the option names as keys.
 */
class CriticalI_Options implements IteratorAggregate, ArrayAccess {
  protected $arguments;
  protected $optionSpec;
  protected $scriptName;
  protected $options;
  protected $hash;
  
  /**
   * Constructor
   *
   * The option specification is a list of CriticalI_OptionSpec objects.
   *
   * @param array $args       The argument list (first item is the script name, not an option)
   * @param array $optionSpec Option specification list.
   */
  public function __construct($args, $optionSpec) {
    $this->arguments = $args;
    $this->optionSpec = $optionSpec;
    $this->scriptName = array_shift($this->arguments);
    $this->options = array();
    $this->hash = null;
    $this->process_arguments();
  }
  
  /**
   * Return the script name
   */
  public function script_name() {
    return $this->scriptName;
  }
  
  /**
   * Return the arguments that remain after processing
   */
  public function arguments() {
    return $this->arguments;
  }
  
  /**
   * Return an iterator for the options list
   * @return ArrayIterator
   */
  public function getIterator() {
    return new ArrayIterator($this->options);
  }
  
  /**
   * Tests for existence of an array index
   * @param string $idx  The index to test
   * @return boolean
   */
  public function offsetExists($idx) {
    if (is_numeric($idx)) {
      return isset($this->options[$idx]);
    } else {
      $hash = $this->options_hash();
      return isset($hash[$idx]);
    }
  }
  
  /**
   * Retrieves the value at an array index.  A numeric index returns a
   * CriticalI_Option, a non-numeric index returns only the argument for
   * the option.
   * @param string $idx  The index to get
   * @return mixed The value for the given option or option index
   */
  public function offsetGet($idx) {
    if (is_numeric($idx)) {
      return $this->options[$idx];
    } else {
      $hash = $this->options_hash();
      return $hash[$idx]->argument;
    }
  }
  
  /**
   * Sets the value at an array index
   * @param string $idx   The index to set
   * @param string $value The value to set
   */
  public function offsetSet($idx, $value) {
    if (is_numeric($idx)) {
      // special case for null
      if (is_null($value)) {
        $this->offsetUnset($idx, $value);
        return;
      } elseif (!($value instanceof CriticalI_Option))
        throw new Exception("Invalid parameter supplied.  CriticalI_Option required, but received ".get_class($value));
      $this->options[$idx] = $value;
      $this->hash = null; // invalidate the hash
    } else {
      $hash = $this->options_hash();
      if (isset($hash[$idx])) {
        $this->hash[$idx]->argument = $value;
      } else {
        $newOpt = new CriticalI_Option($idx, $value);
        $this->options[] = $newOpt;
        $this->hash[$idx] = $newOpt;
      }
    }
  }
  
  /**
   * Deletes an entry at an array index
   * @param string $idx  The index to unset
   */
  public function offsetUnset($idx) {
    if (is_numeric($idx)) {
      unset($this->options[$idx]);
      $this->hash = null; // invalidate the hash
    } else {
      // find the actual option and remove it
      for ($optIdx = count($this->options) - 1; $optIdx >= 0; $optIdx--) {
        if ($this->options[$optIdx]->name == $idx) {
          unset($this->options[$optIdx]);
          $this->hash = null;
          return;
        }
      }
    }
  }
  
  /**
   * Provides internal access to the options hash
   */
  protected function options_hash() {
    if (is_null($this->hash)) {
      // hash is lazily populated
      $this->hash = array();
      foreach ($this->options as $opt) {
        $this->hash[$opt->name] = $opt; // last declared wins
      }
    }
    
    return $this->hash;
  }
  
  /**
   * Process the arguments
   */
  protected function process_arguments() {
    while ($this->is_next_option()) {
      $arg = array_shift($this->arguments);
      $this->parse_option($arg);
    }
    
    if ((count($this->arguments) > 0) && ($this->arguments[0] == '--'))
      array_shift($this->arguments);
  }
  
  /**
   * Peeks at the next argument to see if it is an option
   */
  protected function is_next_option() {
    return ( (count($this->arguments) > 0) &&
             (substr($this->arguments[0], 0, 2) == '--') &&
             (strlen($this->arguments[0]) > 2) );
  }
  
  /**
   * Parses an option
   *
   * @param string $arg  The current argument
   */
  protected function parse_option($arg) {
    $name = substr($arg, 2);
    $optArg = null;
    
    // handle argument provided with =
    if (($idx = strpos($name, '=')) !== false) {
      $optArg = substr($name, $idx + 1);
      $name = substr($name, 0, $idx);
    }
    
    // see if this is a known option
    if ($spec = $this->find_option_spec($name)) {
      if ($spec->argument_type == CriticalI_OptionSpec::OPTIONAL) {
        $this->parse_option_optional_arg($name, $optArg);
      } elseif ($spec->argument_type == CriticalI_OptionSpec::REQUIRED) {
        $this->parse_option_required_arg($name, $optArg);
      } else {
        $this->parse_option_prohibited_arg($name, $optArg);
      }
    } else {
      throw new CriticalI_UnknownOptionError($name);
    }
  }
  
  /**
   * Handle an option which requires an argument
   *
   * @param string $name  The option name
   * @param string $optArg The attached argument, if supplied (i.e. if provided as name=arg)
   */
  protected function parse_option_required_arg($name, $optArg) {
    if (is_null($optArg)) {
      if (count($this->arguments) > 0) {
        $optArg = array_shift($this->arguments);
      } else {
        throw new CriticalI_MissingOptionArgumentError($name);
      }
    }
    
    $this->options[] = new CriticalI_Option($name, $optArg);
  }
  
  /**
   * Handle an option which may take an optional argument
   *
   * @param string $name  The option name
   * @param string $optArg The attached argument, if supplied (i.e. if provided as name=arg)
   */
  protected function parse_option_optional_arg($name, $optArg) {
    if (is_null($optArg)) {
      if ( (!$this->is_next_option()) &&
           (count($this->arguments) > 0) &&
           ($this->arguments[0] != '--') ) {
        $optArg = array_shift($this->arguments);
      }
    }
    
    $this->options[] = new CriticalI_Option($name, $optArg);
  }
  
  /**
   * Handle an option which may not have an argument
   *
   * @param string $name  The option name
   * @param string $optArg The attached argument, if supplied (i.e. if provided as name=arg)
   */
  protected function parse_option_prohibited_arg($name, $optArg) {
    if (!is_null($optArg)) {
      throw new CriticalI_InvalidOptionArgumentError($name, $optArg);
    }
    
    $this->options[] = new CriticalI_Option($name, true);
  }
  
  /**
   * Located the option spec for a named option
   *
   * @param string $name  The option spec to find
   */
  protected function find_option_spec($name) {
    foreach ($this->optionSpec as $spec) {
      if ($spec->name == $name) return $spec;
    }
    return false;
  }
}

?>