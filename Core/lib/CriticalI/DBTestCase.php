<?php

/**
 * The DBTestCase class exists as a convenience for writing test cases
 * that require a database.  Creating and/or resetting a database for
 * each test involves significant overhead, so confining that behavior to
 * this class allows that to be skipped for test cases that don't require
 * it (test cases that inherit directly from CriticalI_TestCase).
 *
 * This class includes a number of special behaviors.  By default it
 * connects to or creates a SQLite database named .db in the current
 * directory.  A different database can be used by overriding the
 * connection() method.
 *
 * Once the database connection has been established, the class looks for
 * a directory named schema.  Any files ending in .sql in the schema
 * directory are assumed to contain table creation statements and to be
 * named for the table they created.  Any table with the same is dropped,
 * and the contents of the .sql file are sent to the database.
 *
 * For each test that is run, the class looks for a directory named
 * fixtures.  Any files ending in .ini in the fixtures directory are
 * assumed to also be named for their corresponding tables.  The table
 * for each file is truncated, the data in the fixture file is loaded
 * into the database, and the data becomes available by calling the
 * fixture method with the fixture name and key.
 */
abstract class CriticalI_DBTestCase extends CriticalI_TestCase {
  protected static $allConnections = null;
  
  protected $schemaLoaded = false;
  protected $testDir = null;
  protected $connection = null;
  protected $fixturesCached = false;
  protected $fixtures = null;
  
  /**
   * Return the path to the test directory.
   * @return string
   */
  protected function working_directory() {
    if (!$this->testDir)
      $this->testDir = getcwd();
    return $this->testDir;
  }
   
  /**
   * Return the database connection
   * @return PDO
   */
  protected function connection() {
    $file = $this->working_directory() . "/.db";
    $conn = new PDO("sqlite:$file");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $conn;
  }
   
  /**
   * Override the run method to allow additional prep
   */
  public function runBare() {
    if (!$this->connection) {
      // only initialize the schema once per test directory in a run
      if (!self::$allConnections) self::$allConnections = array();

      $dir = $this->working_directory();
      if (isset(self::$allConnections[$dir])) {
        $this->connection = self::$allConnections[$dir];
      } else {
        $this->connection = $this->connection();
        $this->init_schema($this->connection);
        self::$allConnections[$dir] = $this->connection;
      }
    }
    
    $this->load_fixtures($this->connection);
    
    parent::runBare();
  }
  
  /**
   * Set up the schema, if needed
   *
   * @param PDO $conn  The database connection
   */
  protected function init_schema($conn) {
    $matches = CriticalI_Globber::match($this->working_directory(), "schema/*.sql");
    foreach ($matches as $file) {
      $tableName = basename($file, '.sql');
      $conn->exec("DROP TABLE IF EXISTS $tableName");
      $conn->exec(file_get_contents($file));
    }
  }
  
  /**
   * Set up the fixtures
   *
   * @param PDO $conn  The database connection
   */
  protected function load_fixtures($conn) {
    if (!$this->fixturesCached)
      $this->load_fixture_files();
    
    foreach ($this->fixtures as $tableName=>$tableData) {
      $conn->exec("DELETE FROM $tableName");
      foreach ($tableData as $key=>$values) {
        if (!is_array($values)) {
          trigger_error("Ignoring invalid fixture $key for $tableName", E_USER_WARNING);
          continue;
        }
        
        $sql = $this->build_fixture_sql($conn, $tableName, $values);
        $conn->exec($sql);
        try {
          $id = $conn->lastInsertId("${tableName}_id");
          if ( $id && (!isset($this->fixtures[$tableName][$key]['id'])) )
            $this->fixtures[$tableName][$key]['id'] = $id;
        } catch (Exception $e) {
          // ignore
        }
        
      }
    }
  }
  
  /**
   * Does the work of loading data from the fixture files
   */
  protected function load_fixture_files() {
    $this->fixtures = array();
    
    $matches = CriticalI_Globber::match($this->working_directory(), "fixtures/*.ini");
    foreach ($matches as $file) {
      $tableName = basename($file, '.ini');

      if (!defined('_Q'))
        define('_Q', "\"");
      $data = parse_ini_file($file, true);
      
      if ($data === false)
        trigger_error("Failed to load fixture data from $file.", E_USER_WARNING);
      else
        $this->fixtures[$tableName] = $data;
    }
    
    $this->fixturesCached = true;
  }
  
  /**
   * Builds the INSERT SQL statement for a set of fixture data
   *
   * @param PDO    $conn       The database connection
   * @param string $tableName  The name of the table to insert data into
   * @param array  $data       The fixture data
   * @return string
   */
  protected function build_fixture_sql($conn, $tableName, $data) {
    $cols = array();
    $values = array();
    
    foreach ($data as $colName=>$value) {
      $cols[] = $colName;
      $values[] = $conn->quote($value);
    }
    
    return "INSERT INTO $tableName (".implode(',', $cols).") VALUES (".implode(',', $values).")";
  }
  
  /**
   * Return the data for a named fixture
   *
   * @param string $name  The name of the fixture
   * @param string $key   The fixture key
   * @return array
   */
  protected function fixture($name, $key) {
    if (isset($this->fixtures[$name]) && isset($this->fixtures[$name][$key])) {
      return $this->fixtures[$name][$key];
    } else {
      throw new Exception("No such fixture as $name/$key");
    }
  }
}

?>