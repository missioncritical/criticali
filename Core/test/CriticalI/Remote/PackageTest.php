<?php

class CriticalI_Remote_PackageTest extends CriticalI_TestCase {
  
  public function testConstructor() {
    $pkg = new CriticalI_Remote_Package(array(
      'name'=>'alpha',
      'version'=>'1.2.3',
      'properties'=>array(
        'dependencies'=>array(
          'beta'=>'1.0',
          'gamma'=>'1.0'
        )
      )
    ), null);
    
    $this->assertEquals($pkg->name(), 'alpha');
    $this->assertEquals($pkg->count(), 1);
    $this->assertTrue($pkg->newest() instanceof CriticalI_Remote_PackageVersion);
    $this->assertEquals($pkg->newest()->package()->name(), 'alpha');
    $this->assertEquals($pkg->newest()->version_string(), '1.2.3');
    $this->assertEquals($pkg->newest()->property('dependencies'),
      array('beta'=>'1.0', 'gamma'=>'1.0'));
  }
  
  public function testAddVersion() {
    $pkg = new CriticalI_Remote_Package(array(
      'name'=>'alpha',
      'version'=>'1.2.3',
      'properties'=>array(
        'dependencies'=>array(
          'beta'=>'1.0',
          'gamma'=>'1.0'
        )
      )
    ), null);
    
    $pkg->add_version(array(
      'name'=>'alpha',
      'version'=>'1.1.2',
      'properties'=>array(
        'dependencies'=>array('beta'=>'1.0')
      )
    ), null);

    $this->assertEquals($pkg->name(), 'alpha');
    $this->assertEquals($pkg->count(), 2);

    $this->assertTrue($pkg->newest() instanceof CriticalI_Remote_PackageVersion);
    $this->assertEquals($pkg->newest()->package()->name(), 'alpha');
    $this->assertEquals($pkg->newest()->version_string(), '1.2.3');
    $this->assertEquals($pkg->newest()->property('dependencies'),
      array('beta'=>'1.0', 'gamma'=>'1.0'));

    $this->assertTrue($pkg->oldest() instanceof CriticalI_Remote_PackageVersion);
    $this->assertEquals($pkg->oldest()->package()->name(), 'alpha');
    $this->assertEquals($pkg->oldest()->version_string(), '1.1.2');
    $this->assertEquals($pkg->oldest()->property('dependencies'),
      array('beta'=>'1.0'));
  }

}

?>