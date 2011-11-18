<?php

class CriticalI_Package_WrapperTest extends CriticalI_TestCase {
  
  public function setUp() {
    $this->tearDown();
  }

  public function tearDown() {
    $here = dirname(__FILE__);
    foreach (glob("$here/*.cip") as $wrapper) {
      unlink($wrapper);
    }
    
    if (file_exists("$here/.package/package.ini")) unlink("$here/.package/package.ini");
    if (file_exists("$here/.package")) rmdir("$here/.package");
  }
  
  public function testLocation() {
    $wrapper = new CriticalI_Package_Wrapper(dirname(__FILE__) . '/location.cip');
    
    $this->assertEquals(dirname(__FILE__) . '/location.cip', $wrapper->location());
  }
  
  public function testName() {
    $wrapper = new CriticalI_Package_Wrapper(dirname(__FILE__) . '/location.cip');
    $this->assertEquals('location', $wrapper->name());

    $wrapper = new CriticalI_Package_Wrapper(dirname(__FILE__) . '/tmpfile', 'location');
    $this->assertEquals('location', $wrapper->name());
  }
  
  public function testPackageName() {
    $here = dirname(__FILE__);

    $this->wrap_properties("$here/testing.cip", array());
    $wrapper = new CriticalI_Package_Wrapper("$here/testing.cip");
    $this->assertEquals('testing', $wrapper->package_name());

    $this->wrap_properties("$here/testing-1.2.3.cip", array('package.version'=>'1.2.3'));
    $wrapper = new CriticalI_Package_Wrapper("$here/testing-1.2.3.cip");
    $this->assertEquals('testing', $wrapper->package_name());

    $this->wrap_properties("$here/testing-1.2.3.cip", array('package.name'=>'other',
      'package.version'=>'1.2.3'));
    $wrapper = new CriticalI_Package_Wrapper("$here/testing-1.2.3.cip");
    $this->assertEquals('other', $wrapper->package_name());
  }
  
  public function testPackageVersion() {
    $here = dirname(__FILE__);

    $this->wrap_properties("$here/testing.cip", array());
    $wrapper = new CriticalI_Package_Wrapper("$here/testing.cip");
    $this->assertEquals('0.0.0', $wrapper->package_version());

    $this->wrap_properties("$here/testing-1.2.3.cip", array('package.version'=>'1.2.3'));
    $wrapper = new CriticalI_Package_Wrapper("$here/testing-1.2.3.cip");
    $this->assertEquals('1.2.3', $wrapper->package_version());
  }

  /**
   * Wrap a dummy package for testing
   */
  protected function wrap_properties($outfile, $properties) {
    if (file_exists($outfile)) unlink($outfile);
    
    $here = dirname(__FILE__);
    if (!file_exists("$here/.package")) mkdir("$here/.package", 0777);
    
    CriticalI_ConfigFile::write("$here/.package/package.ini", $properties);
    
    $zip = new ZipArchive();
    $zip->open($outfile, ZipArchive::CREATE);
    
    $zip->addFile("$here/.package/package.ini", 'package.ini');
    
    $zip->close();
  }

}

?>