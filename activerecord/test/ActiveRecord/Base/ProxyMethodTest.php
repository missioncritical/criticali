<?php

class CustomProxy {
  public function foo($obj, $proxy, $a, $b) {
    return $a . $b;
  }
  
  public function full_name($obj, $proxy) {
    return implode(' ', array($proxy->read_attribute('first_name'),
      $proxy->read_attribute('last_name')));
  }

  public function set_full_name($obj, $proxy, $name) {
    list($first, $last) = preg_split('/\s+/', $name, 2);
    $proxy->write_attribute('first_name', $first);
    $proxy->write_attribute('last_name', $last);
  }
  
  public function is_temporary_cached($obj, $proxy) {
    return $proxy->has_cached_attribute('temporary');
  }
  
  public function temporary($obj, $proxy) {
    return $proxy->has_cached_attribute('temporary') ?
      $proxy->read_cached_attribute('temporary') : null;
  }

  public function set_temporary($obj, $proxy, $value) {
    $proxy->write_cached_attribute('temporary', $value);
  }
}

class ProxyMethodTest_Name extends ActiveRecord_Base {
  protected function init_class() {
    $this->set_table_name('names');
    $obj = new CustomProxy();
    $this->add_method_proxy('foo', array($obj, 'foo'));
    $this->add_method_proxy('full_name', array($obj, 'full_name'));
    $this->add_method_proxy('set_full_name', array($obj, 'set_full_name'));
    $this->add_method_proxy('is_temporary_cached', array($obj, 'is_temporary_cached'));
    $this->add_method_proxy('temporary', array($obj, 'temporary'));
    $this->add_method_proxy('set_temporary', array($obj, 'set_temporary'));
  }
  
}

class ActiveRecord_Base_ProxyMethodTest extends CriticalI_DBTestCase {
  
  public function testReadProxyCall() {
    $name = new ProxyMethodTest_Name();
    $name->first_name = 'John';
    $name->last_name = 'Smith';
    $this->assertEquals('John Smith', $name->full_name());
  }
  
  public function testWriteProxyCall() {
    $name = new ProxyMethodTest_Name();
    $name->set_full_name('John Smith');
    $this->assertEquals('John', $name->first_name);
    $this->assertEquals('Smith', $name->last_name);
  }
  
  public function testHasCachedProxyCall() {
    $name = new ProxyMethodTest_Name();
    $this->assertFalse($name->is_temporary_cached());
  }
  
  public function testReadWriteCachedProxyCall() {
    $name = new ProxyMethodTest_Name();
    $this->assertEquals(null, $name->temporary());
    $name->set_temporary(95);
    $this->assertEquals(95, $name->temporary());
  }
  
  public function testProxiedProperties() {
    $name = new ProxyMethodTest_Name();
    $this->assertEquals(null, $name->temporary);
    $name->temporary = 85;
    $this->assertEquals(85, $name->temporary);
  }
  
  public function testMultiParamProxyCall() {
    $name = new ProxyMethodTest_Name();
    $this->assertEquals('hi there', $name->foo('hi ', 'there'));
  }
  
}

?>