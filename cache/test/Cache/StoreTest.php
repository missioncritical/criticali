<?php

class Cache_StoreTest extends Criticali_TestCase {
  
  public function testGet() {
    $store = new Cache_Store();
    
    $this->assertEquals(null, $store->get('alpha'));
    
    $store->set('alpha', 'Alpha');
    $this->assertEquals('Alpha', $store->get('alpha'));
    
    try {
      $store->get('alpha', array('engine'=>'bogus'));
      $this->fail("Used unsupported cache engine \"bogus\"");
    } catch (Cache_UnsupportedEngineError $e) {
      // expected
    }
    
    $this->assertEquals('Alpha', $store->get('alpha', 'memory_profile'));

    $this->assertEquals(null, $store->get('beta'));
    $this->assertEquals('Bravo', $store->get('beta', null, array($this, 'return_bravo')));
    $this->assertEquals('Bravo', $store->get('beta'));

    $store->set('gamma', 'Golf');
    $this->currentKey = 'gamma';
    $this->expectedData = 'Golf';
    $this->assertEquals('Golf',
      $store->get('gamma', array('expiration_check'=>array($this, 'return_is_valid'))));
    $this->assertEquals(null,
      $store->get('gamma', array('expiration_check'=>array($this, 'return_isnt_valid'))));
    $this->assertEquals(null, $store->get('gamma'));
  }
  
  public function testSet() {
    $store = new Cache_Store();
    
    $this->assertEquals(null, $store->get('alpha'));
    
    $store->set('alpha', 'Alpha');
    $this->assertEquals('Alpha', $store->get('alpha'));
    
    try {
      $store->set('alpha', 'Alpha', array('engine'=>'bogus'));
      $this->fail("Used unsupported cache engine \"bogus\"");
    } catch (Cache_UnsupportedEngineError $e) {
      // expected
    }
    
    $store->set('beta', 'Bravo', 'memory_profile');
    $this->assertEquals('Bravo', $store->get('beta'));

    $store->set(array('multi', 'key'), 'Charlie');
    $this->assertEquals('Charlie', $store->get(array('multi', 'key')));
    $this->assertEquals(null, $store->get(array('multi', 'otherkey')));
  }
  
  public function testExists() {
    $store = new Cache_Store();
    
    $store->set('alpha', 'Alpha');
    
    $this->assertTrue($store->exists('alpha'));
    $this->assertFalse($store->exists('beta'));
    $this->assertTrue($store->exists('alpha'), array('engine'=>'memory'));
    $this->assertTrue($store->exists('alpha'), 'memory_profile');

    try {
      $store->exists('alpha', array('engine'=>'bogus'));
      $this->fail("Used unsupported cache engine \"bogus\"");
    } catch (Cache_UnsupportedEngineError $e) {
      // expected
    }
  }
  
  public function testExpire() {
    $store = new Cache_Store();
    
    $store->set('alpha', 'Alpha');
    $this->assertTrue($store->exists('alpha'));
    $store->expire('alpha');
    $this->assertFalse($store->exists('alpha'));

    $store->set('alpha', 'Alpha');
    $this->assertTrue($store->exists('alpha'));
    $store->expire('alpha', array('engine'=>'memory'));
    $this->assertFalse($store->exists('alpha'));

    $store->set('alpha', 'Alpha');
    $this->assertTrue($store->exists('alpha'));
    $store->expire('alpha', 'memory_profile');
    $this->assertFalse($store->exists('alpha'));

    $store->set('alpha', 'Alpha');
    try {
      $store->expire('alpha', array('engine'=>'bogus'));
      $this->fail("Used unsupported cache engine \"bogus\"");
    } catch (Cache_UnsupportedEngineError $e) {
      // expected
    }
  }
  
  public function return_bravo() {
    return 'Bravo';
  }
  
  public function return_is_valid($key, $data) {
    $this->assertEquals($this->currentKey, $key);
    $this->assertEquals($this->expectedData, $data);
    return true;
  }

  public function return_isnt_valid(&$key, &$data) {
    $this->assertEquals($this->currentKey, $key);
    $this->assertEquals($this->expectedData, $data);
    return false;
  }

}

?>