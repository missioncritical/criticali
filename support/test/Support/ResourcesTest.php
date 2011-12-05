<?php

class Support_ResourcesTest extends CriticalI_TestCase {
  
  public function testCache() {
    $cache = Support_Resources::cache();
    
    $this->assertTrue(method_exists($cache, 'get'));
    $this->assertTrue(method_exists($cache, 'set'));
    $this->assertTrue(method_exists($cache, 'exists'));
    $this->assertTrue(method_exists($cache, 'expire'));
  }
  
}

?>