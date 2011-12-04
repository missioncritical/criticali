<?php

class Cache_Engine_MemcacheTest extends CriticalI_TestCase {
  
  public function setUp() {
    if (!Cache_Engine_Memcache::is_supported())
      $this->markTestSkipped("The memcache extension is not available");

    $memcache = new Memcache();
    if (!$memcache->connect('localhost', 11211))
      $this->markTestSkipped("The test requires an instance of memcached ".
        "to be running on localhost at port 11211");
    
    $this->tearDown();
  }
  
  public function tearDown() {
    $memcache = new Memcache();
    if ($memcache->connect('localhost', 11211))
      $memcache->flush();
  }
  
  public function testItemForKey() {
    $engine = new Cache_Engine_Memcache();
    
    $item = $engine->item_for_key('alpha', array());
    $this->assertEquals('alpha', $item->key());
  }
  
  public function testHasLocking() {
    $engine = new Cache_Engine_Memcache();

    $this->assertFalse($engine->has_locking());
  }

  public function testClear() {
    $engine = new Cache_Engine_Memcache();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');
    $item = $engine->item_for_key('beta', array());
    $item->write('Bravo');
    
    $this->assertEquals('Alpha', $engine->item_for_key('alpha', array())->read());
    $this->assertEquals('Bravo', $engine->item_for_key('beta', array())->read());

    $engine->clear();
    
    $this->assertEquals(null, $engine->item_for_key('alpha', array())->read());
    $this->assertEquals(null, $engine->item_for_key('beta', array())->read());
  }

  public function testIsSupported() {
    if (class_exists('Memcache'))
      $this->assertTrue(Cache_Engine_Memcache::is_supported());
    else
      $this->assertFalse(Cache_Engine_Memcache::is_supported());
  }
  
  public function testGetConnectionOptions() {
    $engine = new Cache_Engine_Memcache();
    
    $this->assertEquals(array('localhost', 11211), $engine->get_connection_options());
    $this->assertEquals(array('testhost', 11211),
      $engine->get_connection_options(array('memcache_host'=>'testhost')));
    $this->assertEquals(array('testhost', 12345),
      $engine->get_connection_options(array('memcache_host'=>'testhost', 'memcache_port'=>12345)));
    $this->assertEquals(array('unix:///var/run/memcached.sock', 0),
      $engine->get_connection_options(array('memcache_host'=>'unix:///var/run/memcached.sock')));
  }
  
  public function testItemExists() {
    $engine = new Cache_Engine_Memcache();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertTrue($engine->item_for_key('alpha', array())->exists());
    $this->assertFalse($engine->item_for_key('beta', array())->exists());
  }
  
  public function testItemRead() {
    $engine = new Cache_Engine_Memcache();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertEquals('Alpha', $engine->item_for_key('alpha', array())->read());
    $this->assertEquals(null, $engine->item_for_key('beta', array())->read());
  }

  public function testItemWrite() {
    $engine = new Cache_Engine_Memcache();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertEquals('Alpha', $engine->item_for_key('alpha', array())->read());

    $item = $engine->item_for_key('alpha', array());
    $item->write('Omega');

    $this->assertEquals('Omega', $engine->item_for_key('alpha', array())->read());
  }

  public function testItemRemove() {
    $engine = new Cache_Engine_Memcache();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertEquals('Alpha', $engine->item_for_key('alpha', array())->read());

    $item = $engine->item_for_key('alpha', array());
    $item->remove();

    $this->assertEquals(null, $engine->item_for_key('alpha', array())->read());
  }
}
