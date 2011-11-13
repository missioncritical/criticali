<?php

class CriticalI_PropertyTestStub extends CriticalI_Property {
  public static function clear() {
    self::$list = null;
  }
}

class CriticalI_PropertyTest extends CriticalI_TestCase {
  
  protected $oldRoot;
  protected $newRoot;
  
  public function setUp() {
    CriticalI_PropertyTestStub::clear();
    $this->oldRoot = $GLOBALS['CRITICALI_ROOT'];
    $this->newRoot = dirname(__FILE__);
    $GLOBALS['CRITICALI_ROOT'] = $this->newRoot;
    
    if (file_exists($this->newRoot."/.properties")) unlink($this->newRoot."/.properties");
  }
  
  public function tearDown() {
    CriticalI_PropertyTestStub::clear();
    $GLOBALS['CRITICALI_ROOT'] = $this->oldRoot;
    
    if (file_exists($this->newRoot."/.properties")) unlink($this->newRoot."/.properties");
    if (file_exists($this->newRoot."/.lock")) unlink($this->newRoot."/.lock");
  }
  
  public function testNewRepository() {
    $props = CriticalI_Property::all();
    $this->assertEquals(array(), $props);
  }
  
  public function testGet() {
    $this->populateProperties();
    
    $this->assertEquals('Alpha', CriticalI_Property::get('a'));
    $this->assertEquals(null, CriticalI_Property::get('z'));
    $rand = rand();
    $this->assertEquals($rand, CriticalI_Property::get('z', $rand));
  }
  
  public function testExists() {
    $this->populateProperties();
    
    $this->assertTrue(CriticalI_Property::exists('a'));
    $this->assertFalse(CriticalI_Property::exists('z'));
  }
  
  public function testAll() {
    $this->populateProperties();
    
    $this->assertEquals(array('a'=>'Alpha','b'=>'Bravo','c'=>'Charlie',
      'd'=>'Delta','e'=>'Echo'), CriticalI_Property::all());
  }

  public function testSet() {
    $this->populateProperties();
    
    try {
      CriticalI_Property::set('"f"', 'Foxtrot');
      $this->fail("Set invalid key");
    } catch (CriticalI_UsageError $e) {
      // expected
    }
    
    $this->assertFalse(CriticalI_Property::exists('f'));
    CriticalI_Property::set('f', 'Foxtrot');
    $this->assertEquals('Foxtrot', CriticalI_Property::get('f'));
    $this->assertEquals("; This is an automatically generated file.\n".
      "; EDIT AT YOUR OWN RISK!\n".
      "version=1\n".
      "[user]\n".
      "a=\"Alpha\"\n".
      "b=\"Bravo\"\n".
      "c=\"Charlie\"\n".
      "d=\"Delta\"\n".
      "e=\"Echo\"\n".
      "f=\"Foxtrot\"\n", file_get_contents($this->newRoot.'/.properties'));

    file_put_contents($this->newRoot . '/.properties',
      "version=1\n".
      "[user]\n".
      "a=\"Alpha\"\n".
      "b=\"Bravo\"\n".
      "c=\"Charlie\"\n".
      "d=\"Delta\"\n".
      "e=\"Echo\"\n".
      "f=\"Foxtrot\"\n".
      "g=\"Golf\"\n".
      "h=\"How\"\n".
      "i=\"India\"\n");

    $this->assertFalse(CriticalI_Property::exists('g'));
    $this->assertFalse(CriticalI_Property::exists('h'));
    CriticalI_Property::set('h', 'Hotel');
    $this->assertEquals('Hotel', CriticalI_Property::get('h'));
    $this->assertEquals('Golf', CriticalI_Property::get('g'));
    $this->assertEquals("; This is an automatically generated file.\n".
      "; EDIT AT YOUR OWN RISK!\n".
      "version=1\n".
      "[user]\n".
      "a=\"Alpha\"\n".
      "b=\"Bravo\"\n".
      "c=\"Charlie\"\n".
      "d=\"Delta\"\n".
      "e=\"Echo\"\n".
      "f=\"Foxtrot\"\n".
      "g=\"Golf\"\n".
      "h=\"Hotel\"\n".
      "i=\"India\"\n", file_get_contents($this->newRoot.'/.properties'));
  }
  
  public function testSetMultiple() {
    $this->populateProperties();
    
    try {
      CriticalI_Property::set_multiple(array('f'=>'Foxtrot', '"g"'=>'Golf', 'h'=>'Hotel'));
      $this->fail("Set invalid key");
    } catch (CriticalI_UsageError $e) {
      // expected
    }
    
    $this->assertFalse(CriticalI_Property::exists('f'));
    $this->assertFalse(CriticalI_Property::exists('g'));
    $this->assertFalse(CriticalI_Property::exists('h'));
    CriticalI_Property::set_multiple(array('f'=>'Foxtrot', 'g'=>'Golf', 'h'=>'Hotel'));
    $this->assertEquals('Foxtrot', CriticalI_Property::get('f'));
    $this->assertEquals('Golf', CriticalI_Property::get('g'));
    $this->assertEquals('Hotel', CriticalI_Property::get('h'));
    $this->assertEquals("; This is an automatically generated file.\n".
      "; EDIT AT YOUR OWN RISK!\n".
      "version=1\n".
      "[user]\n".
      "a=\"Alpha\"\n".
      "b=\"Bravo\"\n".
      "c=\"Charlie\"\n".
      "d=\"Delta\"\n".
      "e=\"Echo\"\n".
      "f=\"Foxtrot\"\n".
      "g=\"Golf\"\n".
      "h=\"Hotel\"\n", file_get_contents($this->newRoot.'/.properties'));
  }
  
  protected function populateProperties() {
    file_put_contents($this->newRoot . '/.properties', <<<PROPS
version=1
[user]
a=Alpha
b=Bravo
c=Charlie
d=Delta
e=Echo
PROPS
    );
  }
}

?>