<?php

class CriticalI_RepositoryLockInspector extends CriticalI_RepositoryLock {
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

class CriticalI_RepositoryLockTest extends CriticalI_TestCase {
  
  protected $oldRoot;
  protected $newRoot;
  
  public function setUp() {
    $this->oldRoot = $GLOBALS['CRITICALI_ROOT'];
    $this->newRoot = dirname(__FILE__);
    $GLOBALS['CRITICALI_ROOT'] = $this->newRoot;
    CriticalI_RepositoryLockInspector::push_state();
  }
  
  public function tearDown() {
    CriticalI_RepositoryLockInspector::cleanup();
    CriticalI_RepositoryLockInspector::pop_state();
    $GLOBALS['CRITICALI_ROOT'] = $this->oldRoot;
    
    if (file_exists($this->newRoot."/.lock")) unlink($this->newRoot."/.lock");
  }
  
  public function testReadLock() {
    if (file_exists($this->newRoot."/.lock")) unlink($this->newRoot."/.lock");
    CriticalI_RepositoryLock::read_lock();
    $this->assertTrue(file_exists($this->newRoot."/.lock"));
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(), CriticalI_RepositoryLock::READ_LOCKED);

    CriticalI_RepositoryLock::read_lock();
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(), CriticalI_RepositoryLock::READ_LOCKED);

    CriticalI_RepositoryLock::write_lock();
    CriticalI_RepositoryLock::read_lock();
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(),
                        CriticalI_RepositoryLock::WRITE_LOCKED);
  }
  
  public function testWriteLock() {
    if (file_exists($this->newRoot."/.lock")) unlink($this->newRoot."/.lock");
    CriticalI_RepositoryLock::write_lock();
    $this->assertTrue(file_exists($this->newRoot."/.lock"));
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(),
                        CriticalI_RepositoryLock::WRITE_LOCKED);

    CriticalI_RepositoryLock::write_lock();
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(),
                        CriticalI_RepositoryLock::WRITE_LOCKED);
    
    CriticalI_RepositoryLock::release();
    CriticalI_RepositoryLock::read_lock();
    CriticalI_RepositoryLock::write_lock();
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(),
                        CriticalI_RepositoryLock::WRITE_LOCKED);
  }
  
  public function testRelease() {
    CriticalI_RepositoryLock::read_lock();
    CriticalI_RepositoryLock::release();
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(), CriticalI_RepositoryLock::UNLOCKED);
    
    CriticalI_RepositoryLock::release();
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(), CriticalI_RepositoryLock::UNLOCKED);

    CriticalI_RepositoryLock::cleanup();
    CriticalI_RepositoryLock::release();
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(), CriticalI_RepositoryLock::UNLOCKED);
  }

  public function testCleanup() {
    CriticalI_RepositoryLock::read_lock();
    CriticalI_RepositoryLock::cleanup();
    $this->assertEquals(CriticalI_RepositoryLockInspector::file_handle(), false);
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(), CriticalI_RepositoryLock::UNLOCKED);
    
    CriticalI_RepositoryLock::read_lock();
    CriticalI_RepositoryLock::release();
    CriticalI_RepositoryLock::cleanup();
    $this->assertEquals(CriticalI_RepositoryLockInspector::file_handle(), false);
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(), CriticalI_RepositoryLock::UNLOCKED);

    CriticalI_RepositoryLock::cleanup();
    $this->assertEquals(CriticalI_RepositoryLockInspector::file_handle(), false);
    $this->assertEquals(CriticalI_RepositoryLockInspector::lock_state(), CriticalI_RepositoryLock::UNLOCKED);
  }

}

?>