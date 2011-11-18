<?php

class CriticalI_Remote_PackageListTestRemote {
  public function index() {
    return array(
      array(
        'name'=>'alpha',
        'version'=>'1.2.3',
        'properties'=>array(
          'dependencies'=>array(
            'beta'=>'1.0',
            'gamma'=>'1.0'
          )
        )
      ),
      
      array(
        'name'=>'beta',
        'version'=>'1.1.0',
        'properties'=>array()
      ),
      
      array(
        'name'=>'gamma',
        'version'=>'1.0.1',
        'properties'=>array()
      )
      
    );
  }
}

class CriticalI_Remote_PackageListTest extends CriticalI_TestCase {
  
  public function testConstructor() {
    $remote = new CriticalI_Remote_PackageListTestRemote();
    $list = new CriticalI_Remote_PackageList(array($remote));
    
    $this->assertTrue(isset($list['alpha']));
    $this->assertEquals($list['alpha']->name(), 'alpha');
    $this->assertEquals($list['alpha']->count(), 1);
    $this->assertTrue($list['alpha']->newest() instanceof CriticalI_Remote_PackageVersion);
    $this->assertEquals($list['alpha']->newest()->package()->name(), 'alpha');
    $this->assertEquals($list['alpha']->newest()->version_string(), '1.2.3');
    $this->assertEquals($list['alpha']->newest()->property('dependencies'),
      array('beta'=>'1.0', 'gamma'=>'1.0'));
  }
  
  public function testGetIterator() {
    $remote = new CriticalI_Remote_PackageListTestRemote();
    $list = new CriticalI_Remote_PackageList(array($remote));
    
    $names = array();
    foreach ($list as $package) {
      $names[] = $package->name();
    }
    
    $this->assertEquals(array('alpha', 'beta', 'gamma'), $names);
  }

}

?>