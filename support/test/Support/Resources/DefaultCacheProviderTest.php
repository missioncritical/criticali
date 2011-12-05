<?php

class Support_Resources_DefaultCacheProviderTest extends CriticalI_TestCase {
  
  public function testGet() {
    $provider = new Support_Resources_DefaultCacheProvider();
    
    $this->assertTrue($provider->get() instanceof Support_Resources_DefaultCacheStore);
  }
  
  public function testStoreGet() {
    $provider = new Support_Resources_DefaultCacheProvider();

    $this->assertEquals(null, $provider->get()->get('alpha'));

    $provider->get()->set('alpha', 'Alpha');
    $this->assertEquals(null, $provider->get()->get('alpha'));
    $this->assertEquals(null, $provider->get()->get('alpha', array()));
    
    $this->assertEquals('Bravo', $provider->get()->get('alpha', null, array($this, 'return_bravo')));
  }
  
  public function testStoreSet() {
    $provider = new Support_Resources_DefaultCacheProvider();

    $provider->get()->set('alpha', 'Alpha');
    $this->assertEquals(null, $provider->get()->get('alpha'));

    $provider->get()->set('alpha', 'Alpha', array());
    $this->assertEquals(null, $provider->get()->get('alpha'));
  }

  public function testStoreExists() {
    $provider = new Support_Resources_DefaultCacheProvider();

    $this->assertFalse($provider->get()->exists('alpha'));

    $provider->get()->set('alpha', 'Alpha');
    $this->assertFalse($provider->get()->exists('alpha'));
    $this->assertFalse($provider->get()->exists('alpha', array()));
  }

  public function testStoreExpire() {
    $provider = new Support_Resources_DefaultCacheProvider();

    $provider->get()->set('alpha', 'Alpha');
    $this->assertFalse($provider->get()->exists('alpha'));

    $provider->get()->expire('alpha');
    $this->assertFalse($provider->get()->exists('alpha'));

    $provider->get()->expire('alpha', array());
    $this->assertFalse($provider->get()->exists('alpha'));
  }
  
  public function return_bravo() {
    return 'Bravo';
  }

}

?>