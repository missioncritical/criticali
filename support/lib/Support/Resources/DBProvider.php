<?php

/**
 * Interface for providing database connections for Support_Resources
 */
interface Support_Resources_DBProvider {
  /**
   * Return an instance of a database connection.
   *
   * @param boolean $writer If true, the returned connection must be writing to the database (default is true)
   * @param boolean $unique If true, forces the provider to return a unique, unshared connection (default is false)
   * @return PDO
   */
  public function get($writer = true, $unique = false);
}

?>