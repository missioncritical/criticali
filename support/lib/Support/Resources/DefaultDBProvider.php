<?php

/**
 * Default implementation of Support_Resources_DBProvider
 */
class Support_Resources_DefaultDBProvider implements Support_Resources_DBProvider {
  protected $connection;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->connection = null;
  }
  
  /**
   * Return an instance of a database connection.
   *
   * @param boolean $writer If true, the returned connection must be writing to the database (default is true)
   * @param boolean $unique If true, forces the provider to return a unique, unshared connection (default is false)
   * @return PDO
   */
  public function get($writer = true, $unique = false) {
    if ($unique)
      return $this->get_new_connection();
    
    if (!$this->connection)
      $this->connect();
    return $this->connection;
  }
  
  /**
   * Connect to the database
   */
  protected function connect() {
    if (!$this->connection) {
      $this->connection = $this->get_new_connection();
    }
  }
  
  /**
   * Returns a new PDO connection
   */
  protected function get_new_connection() {
    $conn = new PDO(Cfg::get_required('database/dsn'),
                    Cfg::get('database/username'),
                    Cfg::get('database/password'),
                    Cfg::get('database/options'));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $conn;
  }
}

?>