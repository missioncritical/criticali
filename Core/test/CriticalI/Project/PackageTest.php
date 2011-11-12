<?php

class CriticalI_Project_PackageTest extends CriticalI_TestCase {
  
  public function testConstructor() {
    $pkg = new CriticalI_Project_Package('alpha', '1.2.3',
      array('dependencies'=>array('beta'=>'1.0', 'gamma'=>'1.0')));
    
    $this->assertEquals($pkg->name(), 'alpha');
    $this->assertEquals($pkg->count(), 1);
    $this->assertTrue($pkg->newest() instanceof CriticalI_Project_PackageVersion);
    $this->assertEquals($pkg->newest()->package()->name(), 'alpha');
    $this->assertEquals($pkg->newest()->version_string(), '1.2.3');
    $this->assertEquals($pkg->newest()->property('dependencies'),
      array('beta'=>'1.0', 'gamma'=>'1.0'));
  }

}

?>