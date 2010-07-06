<?php

class Vulture_RepositoryLockInspector extends Vulture_RepositoryLock {
  protected static $states;
  
  public static function lock_state() { return self::$lockState; }
  public static function file_handle() { return self::$fileHandle; }
  
  public static function push_state() {
    if (!self::$states) self::$states = array();
    self::$states[] = array(self::$lockState, self::$fileHandle);
    self::$lockState = self::UNLOCKED;
    self::$fileHandle = false;
  }
  
  public static function pop_state() {
    list(self::$lockState, self::$fileHandle) = array_pop(self::$states);
  }
}

class Vulture_RepositoryLockTest extends Vulture_TestCase {
  
  protected $oldRoot;
  protected $newRoot;
  
  public function setUp() {
    $this->oldRoot = $GLOBALS['VULTURE_ROOT'];
    $this->newRoot = dirname(__FILE__);
    $GLOBALS['VULTURE_ROOT'] = $this->newRoot;
    Vulture_RepositoryLockInspector::push_state();
  }
  
  public function tearDown() {
    Vulture_RepositoryLockInspector::cleanup();
    Vulture_RepositoryLockInspector::pop_state();
    $GLOBALS['VULTURE_ROOT'] = $this->oldRoot;
    
    if (file_exists($this->newRoot."/.lock")) unlink($this->newRoot."/.lock");
  }
  
  public function testReadLock() {
    if (file_exists($this->newRoot."/.lock")) unlink($this->newRoot."/.lock");
    Vulture_RepositoryLock::read_lock();
    $this->assertTrue(file_exists($this->newRoot."/.lock"));
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(), Vulture_RepositoryLock::READ_LOCKED);

    Vulture_RepositoryLock::read_lock();
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(), Vulture_RepositoryLock::READ_LOCKED);

    Vulture_RepositoryLock::write_lock();
    Vulture_RepositoryLock::read_lock();
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(),
                        Vulture_RepositoryLock::WRITE_LOCKED);
  }
  
  public function testWriteLock() {
    if (file_exists($this->newRoot."/.lock")) unlink($this->newRoot."/.lock");
    Vulture_RepositoryLock::write_lock();
    $this->assertTrue(file_exists($this->newRoot."/.lock"));
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(),
                        Vulture_RepositoryLock::WRITE_LOCKED);

    Vulture_RepositoryLock::write_lock();
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(),
                        Vulture_RepositoryLock::WRITE_LOCKED);
    
    Vulture_RepositoryLock::release();
    Vulture_RepositoryLock::read_lock();
    Vulture_RepositoryLock::write_lock();
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(),
                        Vulture_RepositoryLock::WRITE_LOCKED);
  }
  
  public function testRelease() {
    Vulture_RepositoryLock::read_lock();
    Vulture_RepositoryLock::release();
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(), Vulture_RepositoryLock::UNLOCKED);
    
    Vulture_RepositoryLock::release();
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(), Vulture_RepositoryLock::UNLOCKED);

    Vulture_RepositoryLock::cleanup();
    Vulture_RepositoryLock::release();
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(), Vulture_RepositoryLock::UNLOCKED);
  }

  public function testCleanup() {
    Vulture_RepositoryLock::read_lock();
    Vulture_RepositoryLock::cleanup();
    $this->assertEquals(Vulture_RepositoryLockInspector::file_handle(), false);
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(), Vulture_RepositoryLock::UNLOCKED);
    
    Vulture_RepositoryLock::read_lock();
    Vulture_RepositoryLock::release();
    Vulture_RepositoryLock::cleanup();
    $this->assertEquals(Vulture_RepositoryLockInspector::file_handle(), false);
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(), Vulture_RepositoryLock::UNLOCKED);

    Vulture_RepositoryLock::cleanup();
    $this->assertEquals(Vulture_RepositoryLockInspector::file_handle(), false);
    $this->assertEquals(Vulture_RepositoryLockInspector::lock_state(), Vulture_RepositoryLock::UNLOCKED);
  }

}

?>