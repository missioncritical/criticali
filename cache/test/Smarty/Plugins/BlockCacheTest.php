<?php

require_once('Smarty/framework_plugins/block.cache.php');

class Smarty_Plugins_BlockCacheTest_Stub {
  public function get_template_vars($arg = null) { return null; }
}

class Smarty_Plugins_BlockCacheTest extends CriticalI_TestCase {
  
  public function testBlockCache() {
    Support_Resources::register_cache(new Cache_Provider(), 'php', true);
    $smarty = new Smarty_Plugins_BlockCacheTest_Stub();
    
    $repeat = true;
    $this->assertEquals(null, smarty_block_cache(array('name'=>'test'), null, $smarty, $repeat));
    $this->assertTrue($repeat);

    $repeat = false;
    $this->assertEquals('content', smarty_block_cache(array('name'=>'test'), 'content', $smarty, $repeat));
    $this->assertFalse($repeat);

    $repeat = true;
    $this->assertEquals('content', smarty_block_cache(array('name'=>'test'), null, $smarty, $repeat));
    $this->assertFalse($repeat);

    $repeat = true;
    $this->assertEquals('content', smarty_block_cache(array('name'=>'test', 'global'=>true),
      null, $smarty, $repeat));
    $this->assertFalse($repeat);

    $repeat = true;
    $this->assertEquals('content', smarty_block_cache(array('name'=>'test', 'engine'=>'memory'),
      null, $smarty, $repeat));
    $this->assertFalse($repeat);

    $repeat = true;
    $this->assertEquals('content', smarty_block_cache(array('name'=>'test', 'profile'=>'memory_profile'),
      null, $smarty, $repeat));
    $this->assertFalse($repeat);
  }
  
}

?>