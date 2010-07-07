<?php

class Support_Resources_DefaultConfigProviderTest extends CriticalI_TestCase {
  
  public function testGet() {
    $this->assertEquals(Support_Resources::config_data(), $GLOBALS['APP_CONFIG']);
    // second pass skips include
    $this->assertEquals(Support_Resources::config_data(), $GLOBALS['APP_CONFIG']);
    $this->assertEquals($GLOBALS['APP_CONFIG'], array('foo'=>1));
  }
  
  public function testGetAgain() {
    // running again in a separate test actually verifies the underlying
    // test case is correctly protecting our global data
    $this->assertEquals(Support_Resources::config_data(), $GLOBALS['APP_CONFIG']);
    // second pass skips include
    $this->assertEquals(Support_Resources::config_data(), $GLOBALS['APP_CONFIG']);
    $this->assertEquals($GLOBALS['APP_CONFIG'], array('foo'=>1));
  }

}

?>