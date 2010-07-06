<?php

class Vulture_Package_VersionTest extends Vulture_TestCase {
  
  public function testCanonifyVersionSpecification() {
    $result = Vulture_Package_Version::canonify_version_specification('1.2.3');
    $this->assertEquals(new Vulture_Package_VersionSpec('1.2.3'), $result);
  }
  
  public function testCompareVersionSpecification() {
    $ver = new Vulture_Package_Version(null, '1', '2', '3', '.');
    
    // default
    $less = Vulture_Package_Version::canonify_version_specification('2.2.3');
    $equal1 = Vulture_Package_Version::canonify_version_specification('1.2.3');
    $equal2 = Vulture_Package_Version::canonify_version_specification('1.2.2');
    $greater = Vulture_Package_Version::canonify_version_specification('0.2.3');
    $this->assertLessThan(0, $ver->compare_version_specification($less));
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
    $this->assertEquals(0, $ver->compare_version_specification($equal2));
    $this->assertGreaterThan(0, $ver->compare_version_specification($greater));

    // greater than
    $less = Vulture_Package_Version::canonify_version_specification('1.2.4+');
    $equal1 = Vulture_Package_Version::canonify_version_specification('1.2.3+');
    $equal2 = Vulture_Package_Version::canonify_version_specification('0.2.3+');
    $this->assertLessThan(0, $ver->compare_version_specification($less));
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
    $this->assertEquals(0, $ver->compare_version_specification($equal2));

    // less than
    $equal1 = Vulture_Package_Version::canonify_version_specification('1.2.3-');
    $equal2 = Vulture_Package_Version::canonify_version_specification('2.2.3-');
    $greater = Vulture_Package_Version::canonify_version_specification('1.2.2-');
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
    $this->assertEquals(0, $ver->compare_version_specification($equal2));
    $this->assertGreaterThan(0, $ver->compare_version_specification($greater));

    // exact
    $less = Vulture_Package_Version::canonify_version_specification('1.2.4!');
    $equal1 = Vulture_Package_Version::canonify_version_specification('1.2.3!');
    $greater = Vulture_Package_Version::canonify_version_specification('1.2.2!');
    $this->assertLessThan(0, $ver->compare_version_specification($less));
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
    $this->assertGreaterThan(0, $ver->compare_version_specification($greater));

    // range
    $less = Vulture_Package_Version::canonify_version_specification('1.2.4-2.2.3');
    $equal1 = Vulture_Package_Version::canonify_version_specification('1.2.3-1.2.4');
    $equal2 = Vulture_Package_Version::canonify_version_specification('1.2.2-1.2.4');
    $equal3 = Vulture_Package_Version::canonify_version_specification('1.2.2-1.2.3');
    $greater = Vulture_Package_Version::canonify_version_specification('0.2.3-1.2.2');
    $this->assertLessThan(0, $ver->compare_version_specification($less));
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
    $this->assertEquals(0, $ver->compare_version_specification($equal2));
    $this->assertEquals(0, $ver->compare_version_specification($equal3));
    $this->assertGreaterThan(0, $ver->compare_version_specification($greater));

    // any
    $equal1 = Vulture_Package_Version::canonify_version_specification('*');
    $this->assertEquals(0, $ver->compare_version_specification($equal1));
  }
  
}

?>