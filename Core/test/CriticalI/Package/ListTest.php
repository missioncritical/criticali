<?php

class CriticalI_Package_ListTestStub extends CriticalI_Package_List {
  public static function public_clean_root_directory() { return self::clean_root_directory(); }
}

class CriticalI_Package_ListTest extends CriticalI_TestCase {

  protected $root;
  
  public function setUp() {
    $this->root = $GLOBALS['CRITICALI_ROOT'];
  }
  
  public function tearDown() {
    $GLOBALS['CRITICALI_ROOT'] = $this->root;
  }
  
  public function testCleanRootDirectory() {
    $GLOBALS['CRITICALI_ROOT'] = '/path/to/file/..';
    $this->assertEquals('/path/to', CriticalI_Package_ListTestStub::public_clean_root_directory());

    $GLOBALS['CRITICALI_ROOT'] = '/path/to/file/../';
    $this->assertEquals('/path/to', CriticalI_Package_ListTestStub::public_clean_root_directory());

    $GLOBALS['CRITICALI_ROOT'] = "/path/to/file/..\\";
    $this->assertEquals('/path/to', CriticalI_Package_ListTestStub::public_clean_root_directory());

    $GLOBALS['CRITICALI_ROOT'] = "\\path\\to\\file/..";
    $this->assertEquals("\\path\\to", CriticalI_Package_ListTestStub::public_clean_root_directory());

    $GLOBALS['CRITICALI_ROOT'] = "\\path\\to\\\\file/..";
    $this->assertEquals("\\path\\to", CriticalI_Package_ListTestStub::public_clean_root_directory());

    $GLOBALS['CRITICALI_ROOT'] = "\\path\\to\\\\file\\..";
    $this->assertEquals("\\path\\to", CriticalI_Package_ListTestStub::public_clean_root_directory());
  }

}
