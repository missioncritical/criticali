<?php

/**
 * Migration_List keeps track of the migrations for a single directory
 */
class Migration_List implements IteratorAggregate, ArrayAccess {
  protected $directory;
  protected $scope;
  protected $migrations;
  
  /**
   * Constructor
   *
   * @param string $dir  The directory containing the migration files
   * @param string $scope The scope, if any, to use in the migrations table
   */
  public function __construct($dir, $scope = null) {
    $this->directory = $dir;
    $this->scope = is_null($scope) ? '' : $scope;
    
    $this->load_migrations();
  }
  
  /**
   * Return an iterator for the command list
   * @return ArrayIterator
   */
  public function getIterator() {
    return new ArrayIterator($this->migrations);
  }
  
  /**
   * Tests for existence of an array index
   * @param string $index  The index to test
   * @return boolean
   */
  public function offsetExists($index) {
    return array_key_exists($index, $this->migrations);
  }
  
  /**
   * Retrieves the migration at an array index.
   * @param string $index  The index to get
   * @return Migration_Record
   */
  public function offsetGet($index) {
    return $this->migrations[$index];
  }
  
  /**
   * Sets the value at an array index
   * @param string $index   The index to set
   * @param Migration_Record $value The value to set
   */
  public function offsetSet($index, $value) {
    $this->migrations[$index] = $value;
  }
  
  /**
   * Deletes an entry at an array index
   * @param string $index  The index to unset
   */
  public function offsetUnset($index) {
    unset($this->migrations[$index]);
  }
  
  /**
   * Return the list of keys in this list
   */
  public function keys() {
    return array_keys($this->migrations);
  }
  
  /**
   * Return the highest performed version number
   */
  public function performed_version() {
    $count = count($this->migrations);
    $val = end($this->migrations);
    
    while ($count) {
      if ($val->performed) {
        reset($this->migrations);
        return $val->version;
      }
      
      $count--;
      $val = prev($this->migrations);
    }
    
    return '0';
  }
  
  /**
   * Return the highest version number (regardless of whether it's been performed)
   */
  public function last_version() {
    $count = count($this->migrations);
    $val = end($this->migrations);
    
    if ($val) {
      reset($this->migrations);
      return $val->version;
    }
    
    return '0';
  }

  /**
   * Load migrations from the directory and tables
   */
  protected function load_migrations() {
    $files = $this->load_migration_files();
    $recs = $this->load_migration_records();
    
    $this->migrations = array();
    $count1 = count($files);
    $count2 = count($recs);
    $val1 = reset($files);
    $val2 = reset($recs);

    // merge
    while (($count1 > 0) || ($count2 > 0)) {
      $cmp = ($count1 > 0 && $count2 > 0) ? strnatcmp($val1, $val2->name) : 0;

      if (($count2 == 0) || ($cmp < 0)) {
        $this->migrations[$val1] = new Migration_Record(array('scope'=>$this->scope, 'name'=>$val1));
        $count1--;
        $val1 = next($files);
      } elseif (($count1 == 0) || ($cmp > 0)) {
        $val2->performed = true;
        $val2->missing = true;
        $this->migrations[$val2->name] = $val2;
        $count2--;
        $val2 = next($recs);
      } else {
        $val2->performed = true;
        $this->migrations[$val2->name] = $val2;
        $count1--;
        $count2--;
        $val1 = next($files);
        $val2 = next($recs);
      }
    }
  }
  
  /**
   * Load the migration files from the directory
   */
  protected function load_migration_files() {
    $files = array();
    foreach (CriticalI_Globber::match($this->directory, '*_*.php') as $file) {
      $files[] = basename($file, '.php');
    }
    
    natsort($files);
    return $files;
  }
  
  /**
   * Load the migration entries from the database
   */
  protected function load_migration_records() {
    // make sure the migrations table exists
    if (!$this->has_migration_table())
      $this->create_migration_table();
    
    $migrations = new Migration_Record();
    $migrations = $migrations->find_all_by_scope($this->scope);
    
    usort($migrations, array($this, 'sort_by_name'));
    return $migrations;
  }
  
  /**
   * Test for the existence of the migration table
   */
  protected function has_migration_table() {
    foreach ($this->connection()->tables() as $table) {
      if ($table == 'migrations') return true;
    }
    return false;
  }
  
  /**
   * Create the migration table
   */
  protected function create_migration_table() {
    $conn = $this->connection();
    
    if ($conn instanceof ActiveRecord_Connection_Sqlite)
      $primary_key = "id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT";
    else
      $primary_key = "id int NOT NULL PRIMARY KEY AUTO_INCREMENT";
    
    $conn->exec(<<<SQL
     CREATE TABLE migrations (
       $primary_key,
       scope varchar(255) NOT NULL,
       name varchar(255) NOT NULL,
       UNIQUE(scope,name)
     );
SQL
    );
  }
  
  /**
   * Return the database connection
   */
  protected function connection() {
    return Support_Resources::db_connection(true, false, 'activerecord');
  }
  
  /**
   * Perform a natural sort by object name property
   */
  public function sort_by_name($a, $b) {
    return strnatcmp($a->name, $b->name);
  }
}
