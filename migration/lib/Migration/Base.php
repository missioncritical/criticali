<?php
// Copyright (c) 2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package migration */

/**
 * Parent class for classes representing individual migrations.
 */
abstract class Migration_Base {
  
  protected $connection;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->connection = null;
  }
  
  /**
   * Perform this migration.
   */
  abstract public function up();
  
  /**
   * Undo this migration.
   */
  public function down() {
    throw new Migration_IrreversibleError($this->scope, get_class($this));
  }
  
  /**
   * Return the database connection
   */
  protected function connection() {
    if (!$this->connection)
      $this->connection = Support_Resources::db_connection(true, false, 'activerecord');
    return $this->connection;
  }
  
  /**
   * A convenience method that calls Support_Util::model()
   *
   * This method exists purely for more concise code creation. All three
   * of the examples below perform the same operation:
   * <code>
   *   // write this:
   *   $post = $this->model('BlogPost')->find($id);
   *
   *   // instead of:
   *   $post = Support_Util::model('BlogPost')->find($id);
   *
   *   // or
   *   $BlogPost = new BlogPost();
   *   $post = $BlogPost->find($id);
   * </code>
   *
   * @param string $className The name of the model class to return
   * @return object
   */
  protected function model($className) {
    return Support_Util::model($className);
  }

  /**
   * Map unknown calls made on the migration directly to the database connection.
   */
  public function __call($name, $arguments) {
    $ref = new ReflectionObject($this->connection());
    if ($ref->hasMethod($name)) {
      $meth = $ref->getMethod($name);
      if ($meth->isPublic())
        return $meth->invokeArgs($this->connection(), $arguments);
    }
    
    throw new Exception("Unknown method \"$name\" called on migration ".get_class($this));
  }
}
