<?php

class CriticalI_Project_Simple extends CriticalI_Project {

  public function __construct($properties) {
    $this->directory = null;
    $this->type = self::INSIDE_PUBLIC;
    $this->properties = $properties;
    $this->packageList = null;
  }

}

class CriticalI_Project_PackageListTest extends CriticalI_TestCase {
  
  public function testConstructor() {
    $project = new CriticalI_Project_Simple($this->properties);
    $list = new CriticalI_Project_PackageList($project);
    
    $this->assertTrue(isset($list['alpha']));
    $this->assertEquals($list['alpha']->name(), 'alpha');
    $this->assertEquals($list['alpha']->count(), 1);
    $this->assertTrue($list['alpha']->newest() instanceof CriticalI_Project_PackageVersion);
    $this->assertEquals($list['alpha']->newest()->package()->name(), 'alpha');
    $this->assertEquals($list['alpha']->newest()->version_string(), '1.2.3');
    $this->assertEquals($list['alpha']->newest()->property('dependencies'),
      array('beta'=>'1.0', 'gamma'=>'1.0'));
    $this->assertEquals($list['alpha']->newest()->property('uninstallers'), array());
    $this->assertEquals($list['beta']->newest()->property('uninstallers'),
      array('BetaUninstallHook', 'Beta_FurtherUninstall'));
  }
  
  public function testGetIterator() {
    $project = new CriticalI_Project_Simple($this->properties);
    $list = new CriticalI_Project_PackageList($project);
    
    $names = array();
    foreach ($list as $package) {
      $names[] = $package->name();
    }
    
    $this->assertEquals(array('alpha', 'beta', 'gamma'), $names);
  }

  protected $properties = array(
      'packages' => array(
        'alpha'=>'1.2.3',
        'beta'=>'1.1.0',
        'gamma'=>'1.0.1'
      ),
      
      'manifests' => array(
        'alpha'=>'alpha files',
        'beta'=>'beta files',
        'gamma'=>'gamma files'
      ),
      
      'depends_on' => array(
        'alpha'=>'beta=1.0,gamma=1.0'
      ),
      
      'uninstallers' => array(
        'beta'=>'BetaUninstallHook,Beta_FurtherUninstall'
      )
      
    );
}

?>