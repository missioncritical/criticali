<?php
// Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package cache */

/**
 * Cache_ItemBase is an abstract class for implementing the key object
 * that must be returned by implementors of the {@link Cache_Engine}
 * interface. It provides methods for working with the value associated
 * with its key.
 *
 * It's worth mentioning the lock and unlock methods of this class. Both
 * methods must be implemented, but they do not need to do anything if
 * the associated engine does not support key-level locking. That is to
 * say, derived classes do not need to override these methods if they do
 * not implement locking. Similarly mtime() does not need to be
 * overridden if it is not appropriate. See that method's documentation
 * for more details.
 */
abstract class Cache_ItemBase {
  protected $key;
  
  /** A read, or shared lock type */
  const LOCK_READ = 1;   // LOCK_SH
  /** A write, or exclusive lock type */
  const LOCK_WRITE = 2;  // LOCK_EX

  /**
   * Constructor
   *
   * @param string $key The item's key
   */
  public function __construct($key) {
    $this->key = $key;
  }
  
  /**
   * Returns this item's key
   *
   * @return string
   */
  public function key() {
    return $this->key;
  }
  
  /**
   * Acquire a lock on the key. The method is expected to block until the
   * lock is acquired.
   *
   * As mentioned in the documentation for the class, this method does not
   * need to acquire a lock if the engine does not support it. In that case
   * it should simply return (this is the default implementation).
   *
   * For engines that implement locking, the <var>create</var> flag may
   * affect the behavior of this method. When the create flag is false,
   * it indicates that the key will not be created if it does not already
   * exist (as in the case of testing for the existence of a key). In
   * this case, it is permissible to not actually lock the key if it does
   * not exist (for example, if in order to lock the key it must first be
   * created). If the create flag is true, engines supporting locking
   * must lock the key and should create the key at this time if needed.
   *
   * @param int $type The lock type, either LOCK_READ or LOCK_WRITE
   * @param boolean $create The flag indicating whether to create the key or not (see above)
   */
  public function lock($type, $create = false) {
  }
  
  /**
   * Release any acquired lock for the key. As with lock(), this function
   * should merely return (the default) if locking is not supported.
   */
  public function unlock() {
  }
  
  /**
   * Test for the existence of the key in the cache. Return true if it
   * exists, false otherwise.
   *
   * @return boolean
   */
  abstract public function exists();
  
  /**
   * Return the last modified time (as a Unix timestamp) of the value
   * associated with the key. This is used for testing TTL expiration
   * when the underlying engine does not handle it automatically. If the
   * underlying engine handles TTL itself, simply return false. The
   * default implementation always returns false.
   *
   * @return int
   */
  public function mtime() {
    return false;
  }
  
  /**
   * Return the value associated with the key from the cache
   *
   * If the key does not have a value, this function must return null and
   * it must not produce an exception.
   *
   * @return mixed
   */
  abstract public function read();
  
  /**
   * Set the value associated with the key
   *
   * @param mixed $data The value to associate with the key
   */
  abstract public function write($data);

  /**
   * Remove the key from the cache
   */
  abstract public function remove();

  /**
   * Given any value, returns a string representation of that value, serializing the
   * value if necessary. This is convenient for preparing a value for storage for
   * some engines.
   *
   * @param mixed $value The data to marshall
   * @return string
   */
  protected function marshall($value) {
    if (is_string($value) || is_int($value))
      return strval($value);
    else
      // add a prefix we can scan for
      return serialize(array('PHP', $value));
  }
  
  /**
   * This is the inverse of marshall(). Given a string value returned by
   * marshall, returns the original value.
   *
   * @param mixed $value The data to marshall
   * @return string
   */
  protected function umarshall($value) {
    // look for our special prefix
    if (substr($value, 0, 19) == 'a:2:{i:0;s:3:"PHP";') {

      $array = unserialize($value);
      if ($array !== false)
        return $array[1];
    }
    
    return $value;
  }
  
}

?>