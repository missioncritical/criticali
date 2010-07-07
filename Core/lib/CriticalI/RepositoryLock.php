<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Interface for acquiring locks for accessing or modifying the repository.
 *
 * The locking mechanism makes use of flock().  This is simple, very
 * widely available, and unlikely to leave the system in an inconsistent
 * state.  However, it availability and reliability are not universal.
 * It will not work on NFS, some other networked file systems, and old
 * FAT filesystems (which are really outdated at this point), so install
 * your repository accordingly.
 *
 * A read lock of the repository can be upgraded by obtaining a write
 * lock, but calling read lock once a write lock has been obtained will
 * not downgrade the lock to a read lock.  This is different than the
 * behavior of flock itself.  While this may not seem intuitive at first,
 * it allows for greater modularity and reuse of code.  In this way
 * locking can actually be declarative.  Underlying routines which need
 * to access files in the repository can declare the type of lock they
 * need (shared or exclusive) and know that a lock of that level or
 * higher has been obtained.  That way, if a read operation occurs inside
 * of a large exclusive operation, the read operation can call for a read
 * lock without affecting the locking needs of the larger transaction.
 * Similarly, because locks are freed automatically at the end of the
 * script, code should generally rely on this behavior to release the
 * lock instead of releasing it explicitly which could adversely affect
 * locking if used within a larger transaction.
 */
class CriticalI_RepositoryLock {
  const UNLOCKED = 0;
  const READ_LOCKED = 1;
  const WRITE_LOCKED = 2;
  
  protected static $lockState = self::UNLOCKED;
  protected static $fileHandle = false;
  
  /**
   * Constructor -- instantiation not allowed
   */
  private function __construct() {
    throw new Exception("Instantiation of class CriticalI_RepositoryLog not allowed.");
  }
  
  /**
   * Called internally to create the lock file
   */
  protected static function create_lock_file() {
    if (self::$fileHandle)
      return;
      
    $filename = "$GLOBALS[CRITICALI_ROOT]/.lock";
    $flags = ((!file_exists($filename)) || is_writable($filename)) ? 'a+' : 'r';
    self::$fileHandle = fopen($filename, $flags);
    if (!self::$fileHandle)
      throw new Exception("Could not create lock file in repository.");

    self::$lockState = self::UNLOCKED;
    
    register_shutdown_function(array('CriticalI_RepositoryLock', 'cleanup'));
  }
  
  /**
   * Acquire a read lock of the repository
   */
  public static function read_lock() {
    if (!self::$fileHandle)
      self::create_lock_file();
      
    if (self::$lockState != self::UNLOCKED) return; // already have a sufficient lock
      
    if (!flock(self::$fileHandle, LOCK_SH))
      throw new Exception("Could not lock repository for reading.");
    self::$lockState = self::READ_LOCKED;
  }

  /**
   * Acquire a write lock of the repository
   */
  public static function write_lock() {
    if (!self::$fileHandle)
      self::create_lock_file();
      
    if (self::$lockState == self::WRITE_LOCKED) return; // already have a sufficient lock
      
    if (!flock(self::$fileHandle, LOCK_EX))
      throw new Exception("Could not lock repository for writing.");
    self::$lockState = self::WRITE_LOCKED;
  }
  
  /**
   * Release any acquired lock
   */
  public static function release() {
    if (!self::$fileHandle) return;
    if (self::$lockState == self::UNLOCKED) return;
    
    if (!flock(self::$fileHandle, LOCK_UN))
      throw new Exception("Could not unlock repository.");
      
    self::$lockState = self::UNLOCKED;
  }
  
  /**
   * Called automatically on shutdown to clean up after the lock file.
   * Generally this shouldn't be necessary, but some Windows systems have
   * much stricter requirements when it comes to locks.  This should
   * provide the cleanest shutdown in all cases.
   */
  public static function cleanup() {
    if (!self::$fileHandle) return;
    if (self::$lockState != self::UNLOCKED) flock(self::$fileHandle, LOCK_UN);
    fclose(self::$fileHandle);
    
    self::$lockState = self::UNLOCKED;
    self::$fileHandle = false;
  }
}

?>