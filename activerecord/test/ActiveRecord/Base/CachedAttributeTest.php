<?php

class CachedAttributeTest_Name extends ActiveRecord_Base {
  protected function init_class() {
    $this->set_table_name('names');
  }
  
  public function is_temporary_cached() {
    return $this->has_cached_attribute('temporary');
  }
  
  public function temporary() {
    return $this->has_cached_attribute('temporary') ?
      $this->read_cached_attribute('temporary') : null;
  }

  public function set_temporary($value) {
    $this->write_cached_attribute('temporary', $value);
  }
}

class ActiveRecord_Base_CachedAttributeTest extends Vulture_DBTestCase {
  
  public function testHasCachedAttribute() {
    $name = new CachedAttributeTest_Name();
    $this->assertFalse($name->is_temporary_cached());
    
    $name->temporary = 5;
    $this->assertTrue($name->is_temporary_cached());
  }
  
  public function testReadWriteCachedAttribute() {
    $name = new CachedAttributeTest_Name();
    $this->assertEquals(null, $name->temporary);
    $name->temporary = 5;
    $this->assertEquals(5, $name->temporary);
  }

  public function testSerialization() {
    $name = new CachedAttributeTest_Name();
    $name->first_name = 'Frank';
    $name->temporary = 5;
    $this->assertEquals(5, $name->temporary);
    
    $str = serialize($name);
    $name = unserialize($str);
    
    $this->assertFalse($name->is_temporary_cached());
    $this->assertEquals('Frank', $name->first_name);
    $this->assertEquals(null, $name->temporary);
  }

}

?>