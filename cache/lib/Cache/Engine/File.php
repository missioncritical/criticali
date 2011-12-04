<?php
// Copyright (c) 2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package cache */

/**
 * The file system cache engine. Items stored in the file system cache are
 * each stored in separate files and persist between requests. They are
 * dependent on the default expiration mechanism of Cache_Store to clean
 * up unused files.
 *
 * The file system cache accepts two cache-specific options:
 *  - <b>cache_dir:</b> The directory to store cache files in
 *  - <b>cache_file:</b> The exact name (or path) of the cache file to use (default is the key name)
 */
class Cache_Engine_File implements Cache_Engine {
  
  const MAX_FILENAME = 150;
  const FILE_SAMPLE_SIZE = 50;
  
  protected $lockCount;
  protected $locks;
  protected $logger;
  
  /**
   * Constructor
   */
  public function __constructor() {
    $this->lockCount = 0;
    $this->locks = array();
    $this->logger = false;
    
    register_shutdown_function(array($this, 'cleanup'));
  }
  
  /**
   * Clean up locks at shutdown
   */
  public function cleanup() {
    foreach ($this->locks as $id=>$lock) {
      flock($lock, LOCK_UN);
      fclose($lock);
    }
  }

  /**
   * Return the cache item object for the provided key.
   *
   * @param string $key The key to retrieve the item for
   * @param array $options Any cache options provided by the requestor
   * @return Cache_ItemBase
   */
  public function item_for_key($key, $options) {
    return new Cache_Engine_File_Item($this, $key, $options);
  }
  
  /**
   * Returns true to indicate this engine's items provide locking
   *
   * @return boolean
   */
  public function has_locking() {
    return true;
  }
  
  /**
   * Clear all items from the cache
   *
   * @param array $options Any cache options provided by the requestor
   */
  public function clear($options = null) {
    // single file scenario
    if (is_array($options) && isset($options['cache_file'])) {
      $cache_file = $this->cache_file_path(null, $options);
      if (is_file($cache_file))
        unlink($cache_file);
      if (is_file($cache_file.'.lock'))
        unlink($cache_file.'.lock');
    
    // directory scenario
    } else {
      $dir = $this->cache_directory($option);
      
      // get all the regular files in the directory, with lock files separated
      if (($dh = opendir($dir)) === false)
        return;
      
      $files = array();
      $locks = array();
      while (($fname = readdir($dh)) !== false) {
        if (is_file("$dir/$fname")) {
          if (substr($fname, -5) == '.lock')
            $locks[$fname] = 1;
          else
            $files[] = $fname;
        }
      }
      
      closedir($dh);

      // clean all the file / lock file pairs
      foreach ($files as $fname) {
        if (isset($locks["$fname.lock"])) {
          unlink("$dir/$fname");
          unlink("$dir/$fname.lock");
          unset($locks["$fname.lock"]);
        }
      }
      // and lock file orphans
      foreach ($locks as $fname) {
        unlink("$dir/$fname");
      }
    }
  }
  
  /**
   * Called statically to ensure this engine is supported before
   * constructing it. The file engine is always supported.
   *
   * @return boolean
   */
  public static function is_supported() {
    return true;
  }
  
  /**
   * Returns the name of the cache directory to use for a given set of
   * options
   *
   * @param array $options The options to use for determining the directory
   */
  public function cache_directory($options = null) {
    if (is_array($options) && isset($options['cache_dir']))
      return $options['cache_dir'];
    else
      return Cfg::get('cache/cache_dir', "$GLOBALS[ROOT_DIR]/var/cache");
  }
  
  /**
   * Register a lock for cleanup at shutdown
   *
   * @param resource $lock The file handle
   * @return string The lock identifier token
   */
  public function register_lock($lock) {
    $token = 'l' . $this->lockCount++;
    $this->locks[$token] = $lock;
    return $token;
  }
  
  /**
   * Unregister a released lock passed to register_lock()
   *
   * @param string $token The lock identifier token received from register_lock()
   */
  public function unregister_lock($token) {
    unset($this->locks[$token]);
  }
  
  /**
   * Returns the full cache file path for a given set of options and keys
   *
   * @param string $key    The key to use
   * @param array $options The options to use for determining the path
   */
  public function cache_file_path($key, $options = null) {
    if (is_array($options) && isset($options['cache_file']))
      $filename = $options['cache_file'];
    else
      $filename = $this->safe_file_name($key);
    
    // test for an absolute path
    if (preg_match("/\\A(?:\\\\|\\/|[a-zA-Z]:[\\\\\\/])/", $filename))
      return $filename;
    
    return $this->cache_directory($options) . '/' . $filename;
  }
  
  /**
   * Sanitize a string value for use as a filename
   */
  protected function safe_file_name($str) {
    $escaped = preg_replace_callback(
      "/[\\x00-\\x1f\"%*\\/:<>\\?\\\\\\|\\x7f]/",
      array($this, 'escape_file_character'),
      $str);
    
    if (strlen($escaped) > self::MAX_FILENAME)
      $escaped = substr($escaped, 0, self::FILE_SAMPLE_SIZE) . '-%%-' . md5($escaped);
    
    return $escaped;
  }
  
  /**
   * Escape a value for use in a filename
   */
  protected function escape_file_character($matches) {
    return rawurlencode($matches[0]);
  }

  /**
   * Return a logger instance for the class
   */
  public function logger() {
    if (!$this->logger) {
      $this->logger = Support_Resources::logger('File Cache');
    }
    
    return $this->logger;
  }
}

/**
 * The key object for the file cache engine.
 */
class Cache_Engine_File_Item extends Cache_ItemBase {
  protected $engine;
  protected $options;
  protected $locked;
  protected $lock;
  protected $token;

  /**
   * Constructor
   */
  public function __construct($engine, $key, $options) {
    parent::__construct($key);
    
    $this->engine = $engine;
    $this->options = $options;
    $this->locked = false;
    $this->lock = null;
    $this->token = null;
  }
  
  /**
   * Acquire a lock on the key. The method blocks until the lock is
   * acquired.
   *
   * @param int $type The lock type, either LOCK_READ or LOCK_WRITE
   * @param boolean $create The flag indicating whether to create the key or not
   */
  public function lock($type, $create = false) {
    if ($this->locked && (($type == $this->locked) || ($type == self::LOCK_READ)))
      return; // already locked
    
    // determine the name of the lock file to use
    $lockFile = $this->engine->cache_file_path($this->key(), $this->options) . '.lock';
    
    if ((!$create) && (!file_exists($lockFile)))
      return;
    
    $this->make_directory(dirname($lockFile));
    
    // open the lock file
    if (is_null($this->lock) || $this->lock === false) {
      $this->lock = fopen($lockFile, 'w');
      if ($this->lock === false) {
        $this->engine->logger()->error("Could not open file \"$lockFile\"");
        return;
      }
    }
    
    // lock it
    if (!flock($this->lock, $type)) {
      $this->engine->logger()->error("Failed to lock file \"$lockFile\"");
      fclose($this->lock);
      $this->lock = null;
      return;
    }
    
    // register the lock and set our state
    if (is_null($this->token))
      $this->token = $this->engine->register_lock($this->lock);
    $this->locked = $type;
  }

  /**
   * Release any acquired lock for the key.
   */
  public function unlock() {
    if (!$this->locked)
      return; // not locked
    
    // release the lock
    @flock($this->lock, LOCK_UN);
    @fclose($this->lock);
    
    // unregister the lock and reset our state
    $this->engine->unregister_lock($this->token);
    $this->locked = false;
    $this->lock = null;
    $this->token = null;
  }

  /**
   * Test for the existence of the key in the cache. Return true if it
   * exists, false otherwise.
   *
   * @return boolean
   */
  public function exists() {
    // determine the name of the file
    $filename = $this->engine->cache_file_path($this->key(), $this->options);
    
    // test for existence
    return file_exists($filename);
  }
  
  /**
   * Return the last modified time (as a Unix timestamp) of the value
   * associated with the key.
   *
   * @return int
   */
  public function mtime() {
    $filename = $this->engine->cache_file_path($this->key(), $this->options);
    $mtime = @filemtime($filename);
    return ($mtime === false ? 0 : $mtime);
  }

  /**
   * Return the value associated with the key from the cache
   *
   * If the key does not have a value, this function returns null.
   *
   * @return mixed
   */
  public function read() {
    // determine the name of the file
    $filename = $this->engine->cache_file_path($this->key(), $this->options);
    
    // read it
    $data = @file_get_contents($filename);
    
    return (($data === false) ? null : $this->unmarshall($data));
  }
  
  /**
   * Set the value associated with the key
   *
   * @param mixed $data The value to associate with the key
   */
  public function write($data) {
    // determine the name of the file
    $filename = $this->engine->cache_file_path($this->key(), $this->options);
    
    // make sure the containing directory exists
    $this->make_directory(dirname($filename));
    
    // write it
    $written = file_put_contents($filename, $this->marshall($data));
    
    if ($written === false)
      $this->engine->logger()->error("Error writing to cache file \"$filename\"");
  }

  /**
   * Remove the key from the cache
   */
  public function remove() {
    // determine the name of the file
    $filename = $this->engine->cache_file_path($this->key(), $this->options);
    @unlink($filename);
    @unlink($filename . '.lock');
  }
  
  /**
   * Creates a directory (and any parent directories) as needed
   *
   * @param string $dir  The directory to created
   */
  protected function make_directory($dir) {
    if (file_exists($dir))
      return;
    
    $success = mkdir($dir, 0777, true);
    
    if (!$success)
      $this->engine->logger()->error("Could not create directory \"$dir\"");
  }
  
}

?>