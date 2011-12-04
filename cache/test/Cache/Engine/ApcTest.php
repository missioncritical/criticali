<?php

class Cache_Engine_ApcTest extends CriticalI_TestCase {
  
  public function setUp() {
    if (!Cache_Engine_Apc::is_supported())
      $this->markTestSkipped("The apc extension is not available or is an older version");

    $this->tearDown();
  }
  
  public function tearDown() {
    apc_clear_cache();
  }
  
  public function testItemForKey() {
    $engine = new Cache_Engine_Apc();
    
    $item = $engine->item_for_key('alpha', array());
    $this->assertEquals('alpha', $item->key());
  }
  
  public function testHasLocking() {
    $engine = new Cache_Engine_Apc();

    $this->assertFalse($engine->has_locking());
  }

  public function testClear() {
    $engine = new Cache_Engine_Apc();

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
    if (function_exists('apc_exists'))
      $this->assertTrue(Cache_Engine_Apc::is_supported());
    else
      $this->assertFalse(Cache_Engine_Apc::is_supported());
  }
  
  public function testItemExists() {
    $engine = new Cache_Engine_Apc();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertTrue($engine->item_for_key('alpha', array())->exists());
    $this->assertFalse($engine->item_for_key('beta', array())->exists());
  }
  
  public function testItemRead() {
    $engine = new Cache_Engine_Apc();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertEquals('Alpha', $engine->item_for_key('alpha', array())->read());
    $this->assertEquals(null, $engine->item_for_key('beta', array())->read());
  }

  public function testItemWrite() {
    $engine = new Cache_Engine_Apc();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertEquals('Alpha', $engine->item_for_key('alpha', array())->read());

    $item = $engine->item_for_key('alpha', array());
    $item->write('Omega');

    $this->assertEquals('Omega', $engine->item_for_key('alpha', array())->read());
  }

  public function testItemRemove() {
    $engine = new Cache_Engine_Apc();

    $item = $engine->item_for_key('alpha', array());
    $item->write('Alpha');

    $this->assertEquals('Alpha', $engine->item_for_key('alpha', array())->read());

    $item = $engine->item_for_key('alpha', array());
    $item->remove();

    $this->assertEquals(null, $engine->item_for_key('alpha', array())->read());
  }
}
