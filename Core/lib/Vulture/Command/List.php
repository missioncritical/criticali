<?php

/**
 * Vulture_Command_List is the collection of installed packages.  It is a
 * singleton whose instance can be obtained by calling the static
 * function list().  The actual instance behaves like many other objects
 * in the system in that it is a first class object that behaves like an
 * array.  Commands are keyed in the list by their name.
 */
class Vulture_Command_List implements IteratorAggregate, ArrayAccess {
  protected static $list = null;
  
  protected $commands;
  
  /**
   * Constructor.
   *
   * This class may not be directly instantiated.
   */
  protected function __construct() {
    $this->commands = array();
    
    // get the list of packages that contain commands
    $allPackages = Vulture_Package_List::get();
    $cmdPackages = $allPackages->commandVersions();
    
    // load the commands from each package version
    foreach ($cmdPackages as $pkg) {
      $this->add_commands_by_glob($pkg->installation_directory(),
        $pkg->property('command.glob', Vulture_Defaults::COMMAND_GLOB));
    }
    
    // load the core commands
    $this->add_commands_by_glob('Core', Vulture_Defaults::CORE_COMMAND_GLOB);
  }
  
  /**
   * Returns the shared list instance
   * @return Vulture_Command_List
   */
  public static function get() {
    if (!self::$list)
      self::$list = new Vulture_Command_List();
    return self::$list;
  }

  /**
   * Return an iterator for the command list
   * @return ArrayIterator
   */
  public function getIterator() {
    return new ArrayIterator($this->commands);
  }
  
  /**
   * Tests for existence of an array index
   * @param string $idx  The index to test
   * @return boolean
   */
  public function offsetExists($idx) {
    return isset($this->commands[$idx]);
  }
  
  /**
   * Retrieves the command at an array index.
   * @param string $idx  The index to get
   * @return Vulture_Command
   */
  public function offsetGet($idx) {
    return $this->commands[$idx];
  }
  
  /**
   * Sets the value at an array index
   * @param string $idx   The index to set
   * @param Vulture_Command $value The value to set
   */
  public function offsetSet($idx, $value) {
    $this->commands[$idx] = $value;
  }
  
  /**
   * Deletes an entry at an array index
   * @param string $idx  The index to unset
   */
  public function offsetUnset($idx) {
    unset($this->commands[$idx]);
  }
  
  /**
   * Adds command classes given a base directory relative to VULTURE_ROOT
   * and a file globbing pattern.
   *
   * @param string $base    The base directory
   * @param string $pattern The globbing pattern
   */
  public function add_commands_by_glob($base, $pattern) {
    // get the matching file names
    $files = Vulture_Globber::match("$GLOBALS[VULTURE_ROOT]/$base", $pattern);
    if (!$files) return;
    foreach ($files as $file) {
      $classname = Vulture_ClassUtils::class_name($file, "$GLOBALS[VULTURE_ROOT]/$base");
      
      // load the file
      include_once($file);
      if (!class_exists($classname, false))
        trigger_error("Unable to instantiate class \"$classname\" after loading command file \"$filename.\"  Command will be ignored.", E_USER_WARNING);
      
      // instantiate the class
      $inst = new $classname();
      
      // must be an instance of Vulture_Command
      if ($inst instanceof Vulture_Command)
        $this->commands[$inst->name()] = $inst;
      else
        trigger_error("Class \"$classname\" is not an instance of Vulture_Command.  Command will be ignored.", E_USER_WARNING);
    }
  }
  
}

?>