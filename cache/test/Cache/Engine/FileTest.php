<?php

class Cache_Engine_FileTest_Stub extends Cache_Engine_File {
  public function public_safe_file_name($name) { return $this->safe_file_name($name); }
  public function &locks() { return $this->locks; }
}

class Cache_Engine_FileTest extends CriticalI_TestCase {
  
  public function setUp() {
    $this->tearDown();
    $GLOBALS['ROOT_DIR'] = dirname(__FILE__) . '/../..';
  }
  
  public function tearDown() {
    $this->clean_dir(dirname(__FILE__) . '/../../var/cache');
    $this->clean_dir(dirname(__FILE__) . '/../../var/cache2');
  }
  
  public function testItemForKey() {
    $engine = new Cache_Engine_File();
    
    $item = $engine->item_for_key('alpha', array());
    $this->assertEquals('alpha', $item->key());
  }
  
  public function testHasLocking() {
    $engine = new Cache_Engine_File();

    $this->assertTrue($engine->has_locking());
  }

  public function testClear() {
    $var = $GLOBALS['ROOT_DIR'] . '/var';
    $engine = new Cache_Engine_File();

    file_put_contents("$var/cache/alpha", 'Alpha');
    file_put_contents("$var/cache/alpha.lock", '');
    file_put_contents("$var/cache/beta", 'Beta');
    file_put_contents("$var/cache/beta.lock", '');
    file_put_contents("$var/cache/gamma", 'Gamma');

    file_put_contents("$var/cache2/alpha", 'Alpha');
    file_put_contents("$var/cache2/alpha.lock", '');
    
    $ls = $this->list_files("$var/cache");
    sort($ls);
    $this->assertEquals(array('alpha', 'alpha.lock', 'beta', 'beta.lock', 'gamma'), $ls);
    
    $engine->clear();
    $ls = $this->list_files("$var/cache");
    sort($ls);
    $this->assertEquals(array('gamma'), $ls);

    $engine->clear(array('cache_file'=>'gamma'));
    $ls = $this->list_files("$var/cache");
    sort($ls);
    $this->assertEquals(array(), $ls);

    $ls = $this->list_files("$var/cache2");
    sort($ls);
    $this->assertEquals(array('alpha', 'alpha.lock'), $ls);
    
    $engine->clear();
    $ls = $this->list_files("$var/cache");
    sort($ls);
    $this->assertEquals(array(), $ls);
  }

  public function testIsSupported() {
    $this->assertTrue(Cache_Engine_File::is_supported()); 
  }

  public function testCacheDirectory() {
    $engine = new Cache_Engine_File();
    
    $this->assertEquals("$GLOBALS[ROOT_DIR]/var/cache", $engine->cache_directory());
    $this->assertEquals("$GLOBALS[ROOT_DIR]/var/cache2",
      $engine->cache_directory(array('cache_dir'=>"$GLOBALS[ROOT_DIR]/var/cache2")));
  }

  public function testCacheFilePath() {
    $engine = new Cache_Engine_File();
    
    $this->assertEquals("$GLOBALS[ROOT_DIR]/var/cache/alpha", $engine->cache_file_path('alpha'));
    $this->assertEquals("$GLOBALS[ROOT_DIR]/var/cache2/alpha",
      $engine->cache_file_path('alpha', array('cache_dir'=>"$GLOBALS[ROOT_DIR]/var/cache2")));
    $this->assertEquals("$GLOBALS[ROOT_DIR]/var/cache/beta",
      $engine->cache_file_path('alpha', array('cache_file'=>'beta')));
    $this->assertEquals("$GLOBALS[ROOT_DIR]/var/cache2/beta",
      $engine->cache_file_path('alpha', array('cache_dir'=>"$GLOBALS[ROOT_DIR]/var/cache2",
        'cache_file'=>'beta')));
    $this->assertEquals("/gamma/beta",
      $engine->cache_file_path('alpha', array('cache_file'=>'/gamma/beta')));
    $this->assertEquals("/gamma/beta",
      $engine->cache_file_path('alpha', array('cache_dir'=>"$GLOBALS[ROOT_DIR]/var/cache2",
        'cache_file'=>'/gamma/beta')));
    $this->assertEquals("\\gamma\\beta",
      $engine->cache_file_path('alpha', array('cache_file'=>"\\gamma\\beta")));
    $this->assertEquals("C:\\gamma\\beta",
      $engine->cache_file_path('alpha', array('cache_file'=>"C:\\gamma\\beta")));
  }

  public function testRegisterLock() {
    $engine = new Cache_Engine_FileTest_Stub();
    
    $this->assertEquals(0, count($engine->locks()));
    
    $fh1 = fopen('php://stdin', 'rb');
    $fh2 = fopen('php://stdin', 'rb');
    
    $token1 = $engine->register_lock($fh1);
    $token2 = $engine->register_lock($fh2);

    $this->assertEquals(2, count($engine->locks()));
    $this->assertNotEquals($token1, $token2);
    
    $engine->unregister_lock($token1);
    $engine->unregister_lock($token2);

    $this->assertEquals(0, count($engine->locks()));
    
    fclose($fh1);
    fclose($fh2);
  }

  public function testUnregisterLock() {
    $engine = new Cache_Engine_FileTest_Stub();
    
    $this->assertEquals(0, count($engine->locks()));
    
    $fh = fopen('php://stdin', 'rb');
    
    $token = $engine->register_lock($fh);

    $this->assertEquals(1, count($engine->locks()));
    
    $engine->unregister_lock($token);

    $this->assertEquals(0, count($engine->locks()));
    
    fclose($fh);
  }

  public function testSafeFileName() {
    $engine = new Cache_Engine_FileTest_Stub();
    
    $this->assertEquals('alpha', $engine->public_safe_file_name('alpha'));
    $this->assertEquals('%25alpha%25', $engine->public_safe_file_name('%alpha%'));
    $this->assertEquals('%00alpha', $engine->public_safe_file_name("\x00alpha"));
    $this->assertEquals('a%7Falpha', $engine->public_safe_file_name("a\x7falpha"));
    $this->assertEquals('al%22%2A%2F%3A%3C%3E%3F%5C%7Cpha',
      $engine->public_safe_file_name("al\"*/:<>?\\|pha"));
    $this->assertEquals( 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',$engine->public_safe_file_name('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));
    $this->assertEquals( '%25aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa-%%-63c5c35b20ef47d72e2cef8c41c341bb',$engine->public_safe_file_name('%aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));
  }
  
  public function testItemExists() {
    $engine = new Cache_Engine_File();

    $this->assertFalse($engine->item_for_key('alpha', array())->exists());

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');
    $item = $engine->item_for_key('beta', array());
    $item->write('Bravo');

    $this->assertTrue($engine->item_for_key('alpha', array())->exists());
    $this->assertTrue($engine->item_for_key('beta', array())->exists());
    $this->assertFalse($engine->item_for_key('gamma', array())->exists());
  }
  
  public function testItemLock() {
    $engine = new Cache_Engine_FileTest_Stub();
    
    $item = $engine->item_for_key('alpha', array());
    
    $item->lock(Cache_ItemBase::LOCK_READ, false);
    $this->assertEquals(0, count($engine->locks()));

    $item->lock(Cache_ItemBase::LOCK_READ, true);
    $this->assertEquals(1, count($engine->locks()));

    $item->lock(Cache_ItemBase::LOCK_WRITE, true);
    $this->assertEquals(1, count($engine->locks()));
  }
  
  public function testItemUnlock() {
    $engine = new Cache_Engine_FileTest_Stub();
    
    $item = $engine->item_for_key('alpha', array());
    
    $item->lock(Cache_ItemBase::LOCK_READ, false);
    $this->assertEquals(0, count($engine->locks()));

    $item->lock(Cache_ItemBase::LOCK_READ, true);
    $this->assertEquals(1, count($engine->locks()));

    $item->lock(Cache_ItemBase::LOCK_WRITE, true);
    $this->assertEquals(1, count($engine->locks()));
    
    $item->unlock();
    $this->assertEquals(0, count($engine->locks()));
  }
  
  public function testItemMtime() {
    $engine = new Cache_Engine_File();

    $this->assertFalse($engine->item_for_key('alpha', array())->exists());
    $this->assertEquals(0, $engine->item_for_key('alpha', array())->mtime());

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertTrue($engine->item_for_key('alpha', array())->exists());
    $this->assertTrue($engine->item_for_key('alpha', array())->mtime() > 0);
  }

  public function testItemRead() {
    $engine = new Cache_Engine_File();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertEquals('Alpha', $engine->item_for_key('alpha', array())->read());
    $this->assertEquals(null, $engine->item_for_key('beta', array())->read());

    $item = $engine->item_for_key('numbers', array());
    $item->write(array(1, 2, 3, 4));

    $this->assertEquals(array(1, 2, 3, 4), $engine->item_for_key('numbers', array())->read());
  }

  public function testItemWrite() {
    $engine = new Cache_Engine_File();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertEquals('Alpha', $engine->item_for_key('alpha', array())->read());
    $this->assertEquals('Alpha', file_get_contents("$GLOBALS[ROOT_DIR]/var/cache/alpha"));

    $item = $engine->item_for_key('alpha', array());
    $item->write('First');
    $item = $engine->item_for_key('alpha', array('cache_dir'=>"$GLOBALS[ROOT_DIR]/var/cache2"));
    $item->write('Omega');

    $this->assertEquals('First', $engine->item_for_key('alpha', array())->read());
    $this->assertEquals('First', file_get_contents("$GLOBALS[ROOT_DIR]/var/cache/alpha"));
    $this->assertEquals('Omega', $engine->item_for_key('alpha',
      array('cache_dir'=>"$GLOBALS[ROOT_DIR]/var/cache2"))->read());
    $this->assertEquals('Omega', file_get_contents("$GLOBALS[ROOT_DIR]/var/cache2/alpha"));
  }

  public function testItemRemove() {
    $engine = new Cache_Engine_File();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertEquals('Alpha', $engine->item_for_key('alpha', array())->read());

    $item = $engine->item_for_key('alpha', array());
    $item->remove();

    $this->assertEquals(null, $engine->item_for_key('alpha', array())->read());
    $this->assertFalse(file_exists("$GLOBALS[ROOT_DIR]/var/cache/alpha"));
  }

  protected function list_files($dir) {
    $entries = array();
    
    if (($dh = opendir($dir)) === false)
      throw new Exception("Could not open $dir");
    
    while (($file = readdir($dh)) !== false) {
      if ((!is_dir("$dir/$file")) && ($file !== '.empty'))
        $entries[] = $file;
    }
    
    closedir($dh);
    
    return $entries;
  }
  
  protected function clean_dir($dir) {
    foreach ($this->list_files($dir) as $file) {
      unlink("$dir/$file");
    }
  }
  
}

?>