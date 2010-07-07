<?php

class CriticalI_Package_VersionTest extends CriticalI_TestCase {
  
  public function testCanonifyVersionSpecification() {
    $result = CriticalI_Package_Version::canonify_version_specification('1.2.3');
    $this->assertEquals(new CriticalI_Package_VersionSpec('1.2.3'), $result);
  }
  
  public function testCompareVersionSpecification() {
    $ver = new CriticalI_Package_Version(null, '1', '2', '3', '.');
    
    // default
    $less = CriticalI_Package_Version::canonify_version_specification('2.2.3');
    $equal1 = CriticalI_Package_Version::canonify_version_specification('1.2.3');
    $equal2 = CriticalI_Package_Version::canonify_version_specification('1.2.2');
    $greater = CriticalI_Package_Version::canonify_version_specification('0.2.3');
    $this->assertLessThan(0, $ver->compare_version_specification($less));
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
    $this->assertEquals(0, $ver->compare_version_specification($equal2));
    $this->assertGreaterThan(0, $ver->compare_version_specification($greater));

    // greater than
    $less = CriticalI_Package_Version::canonify_version_specification('1.2.4+');
    $equal1 = CriticalI_Package_Version::canonify_version_specification('1.2.3+');
    $equal2 = CriticalI_Package_Version::canonify_version_specification('0.2.3+');
    $this->assertLessThan(0, $ver->compare_version_specification($less));
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
    $this->assertEquals(0, $ver->compare_version_specification($equal2));

    // less than
    $equal1 = CriticalI_Package_Version::canonify_version_specification('1.2.3-');
    $equal2 = CriticalI_Package_Version::canonify_version_specification('2.2.3-');
    $greater = CriticalI_Package_Version::canonify_version_specification('1.2.2-');
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
    $this->assertEquals(0, $ver->compare_version_specification($equal2));
    $this->assertGreaterThan(0, $ver->compare_version_specification($greater));

    // exact
    $less = CriticalI_Package_Version::canonify_version_specification('1.2.4!');
    $equal1 = CriticalI_Package_Version::canonify_version_specification('1.2.3!');
    $greater = CriticalI_Package_Version::canonify_version_specification('1.2.2!');
    $this->assertLessThan(0, $ver->compare_version_specification($less));
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
    $this->assertGreaterThan(0, $ver->compare_version_specification($greater));

    // range
    $less = CriticalI_Package_Version::canonify_version_specification('1.2.4-2.2.3');
    $equal1 = CriticalI_Package_Version::canonify_version_specification('1.2.3-1.2.4');
    $equal2 = CriticalI_Package_Version::canonify_version_specification('1.2.2-1.2.4');
    $equal3 = CriticalI_Package_Version::canonify_version_specification('1.2.2-1.2.3');
    $greater = CriticalI_Package_Version::canonify_version_specification('0.2.3-1.2.2');
    $this->assertLessThan(0, $ver->compare_version_specification($less));
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
    $this->assertEquals(0, $ver->compare_version_specification($equal2));
    $this->assertEquals(0, $ver->compare_version_specification($equal3));
    $this->assertGreaterThan(0, $ver->compare_version_specification($greater));

    // any
    $equal1 = CriticalI_Package_Version::canonify_version_specification('*');
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
  }
  
}

?>