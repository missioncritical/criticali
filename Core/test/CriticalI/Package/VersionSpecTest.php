<?php

class CriticalI_Package_VersionSpecTest extends CriticalI_TestCase {
  
  public function testDefault() {
    $result = CriticalI_Package_Version::canonify_version_specification('1.2.3');
    $this->assertEquals(array('1','2','3'), $result->start);
    $this->assertEquals(null, $result->end);
    $this->assertFalse($result->exact);
    $this->assertFalse($result->plus);
    $this->assertFalse($result->minus);
    $this->assertFalse($result->any);
  }
  
  public function testGreaterThan() {
    $result = CriticalI_Package_Version::canonify_version_specification('1.2.3+');
    $this->assertEquals(array('1','2','3'), $result->start);
    $this->assertEquals(null, $result->end);
    $this->assertFalse($result->exact);
    $this->assertTrue($result->plus);
    $this->assertFalse($result->minus);
    $this->assertFalse($result->any);
  }
  
  public function testLessThan() {
    $result = CriticalI_Package_Version::canonify_version_specification('1.2.3-');
    $this->assertEquals(array('1','2','3'), $result->start);
    $this->assertEquals(null, $result->end);
    $this->assertFalse($result->exact);
    $this->assertFalse($result->plus);
    $this->assertTrue($result->minus);
    $this->assertFalse($result->any);
  }
  
  public function testExact() {
    $result = CriticalI_Package_Version::canonify_version_specification('1.2.3!');
    $this->assertEquals(array('1','2','3'), $result->start);
    $this->assertEquals(null, $result->end);
    $this->assertTrue($result->exact);
    $this->assertFalse($result->plus);
    $this->assertFalse($result->minus);
    $this->assertFalse($result->any);
  }
  
  public function testRange() {
    $result = CriticalI_Package_Version::canonify_version_specification('1.2.3-4.5.6');
    $this->assertEquals(array('1','2','3'), $result->start);
    $this->assertEquals(array('4','5','6'), $result->end);
    $this->assertFalse($result->exact);
    $this->assertFalse($result->plus);
    $this->assertFalse($result->minus);
    $this->assertFalse($result->any);

    $result = CriticalI_Package_Version::canonify_version_specification('4.5.6-1.2.3');
    $this->assertEquals(array('1','2','3'), $result->start);
    $this->assertEquals(array('4','5','6'), $result->end);
    $this->assertFalse($result->exact);
    $this->assertFalse($result->plus);
    $this->assertFalse($result->minus);
    $this->assertFalse($result->any);
  }
  
  public function testAny() {
    $result = CriticalI_Package_Version::canonify_version_specification('*');
    $this->assertEquals(null, $result->start);
    $this->assertEquals(null, $result->end);
    $this->assertFalse($result->exact);
    $this->assertFalse($result->plus);
    $this->assertFalse($result->minus);
    $this->assertTrue($result->any);
  }

}

?>