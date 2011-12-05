<?php

class Support_ResourcesTest_Cache extends Support_Resources_DefaultCacheProvider {
  public function __construct() {
    parent::__construct();
    $this->cacheStore = new Support_ResourcesTest_CacheStore();
  }
}

class Support_ResourcesTest_CacheStore extends Support_Resources_DefaultCacheStore {
}

class Support_ResourcesTest_Support extends Support_Resources {
  public static function reset() { self::$instance = null; }
  public static function inst() { return self::instance(); }
}

class Support_ResourcesTest extends CriticalI_TestCase {
  
  public function tearDown() {
    Support_ResourcesTest_Support::reset();
  }

  public function testCache() {
    $cache = Support_Resources::cache();
    
    $this->assertTrue(method_exists($cache, 'get'));
    $this->assertTrue(method_exists($cache, 'set'));
    $this->assertTrue(method_exists($cache, 'exists'));
    $this->assertTrue(method_exists($cache, 'expire'));
    $this->assertTrue(method_exists($cache, 'clear'));
  }
  
  public function testRegisterCache() {
    $provider = new Support_ResourcesTest_Cache();
    
    Support_Resources::register_cache($provider, 'test');
    $this->assertTrue(Support_Resources::cache('test') instanceof Support_ResourcesTest_CacheStore);
    $this->assertFalse(Support_Resources::cache() instanceof Support_ResourcesTest_CacheStore);

    Support_Resources::register_cache($provider, 'test2', true);
    $this->assertTrue(Support_Resources::cache('test2') instanceof Support_ResourcesTest_CacheStore);
    $this->assertTrue(Support_Resources::cache() instanceof Support_ResourcesTest_CacheStore);
  }
  
  public function testSetDefaultCache() {
    $provider = new Support_ResourcesTest_Cache();
    
    Support_Resources::register_cache($provider, 'test');
    $this->assertTrue(Support_Resources::cache('test') instanceof Support_ResourcesTest_CacheStore);
    $this->assertFalse(Support_Resources::cache() instanceof Support_ResourcesTest_CacheStore);

    Support_Resources::set_default_cache('test');
    $this->assertTrue(Support_Resources::cache('test') instanceof Support_ResourcesTest_CacheStore);
    $this->assertTrue(Support_Resources::cache() instanceof Support_ResourcesTest_CacheStore);
  }

}

?>