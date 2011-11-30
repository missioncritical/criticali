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