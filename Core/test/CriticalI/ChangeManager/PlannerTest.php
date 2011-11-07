<?php

class CriticalI_ChangeManager_PlannerTest_Version extends CriticalI_Package_Version {
  public function __construct($pkg, $version, $depends) {
    $this->package = $pkg;
    
    list($major, $minor, $revision) = CriticalI_Package_Version::canonify_version($version);
    
    $this->major = $major;
    $this->minor = $minor;
    $this->revision = $revision;

    $this->directory = null;
    $this->properties = array('dependencies'=>$depends);
  }
}

class CriticalI_ChangeManager_PlannerTest_Package extends CriticalI_Package {
  public function __construct($name, $versions) {
    $this->name = $name;
    $this->versions = array();
    
    foreach ($versions as $ver=>$depends) {
      $this->versions[] = new CriticalI_ChangeManager_PlannerTest_Version($this, $ver, $depends);
    }
    usort($this->versions, array('CriticalI_Package_Version', 'compare_versions'));
  }
}

class CriticalI_ChangeManager_PlannerTest extends CriticalI_TestCase {
  
  public function testInstallPlan() {
    $planner = new CriticalI_ChangeManager_Planner($this->buildPackageList('repositoryAB'),
      $this->buildPackageList('emptyProject'), false);
    
    // no dependencies
    $this->assertTrue($this->planMatches($planner->install_plan('A', '1.0'),
      array('A'=>'1.0.0'), array()));
    $this->assertTrue($this->planMatches($planner->install_plan('A'),
      array('A'=>'1.0.0'), array()));
    
    // single dependency
    $this->assertTrue($this->planMatches($planner->install_plan('B', '1.0'),
      array('A'=>'1.0.0', 'B'=>'1.0.0'), array()));
    
    // not found
    try {
      $planner->install_plan('C', '1.0');
      $this->fail("Found unknown package C");
    } catch (CriticalI_UnknownPackageError $e) {
      // expected
    }

    try {
      $planner->install_plan('B', '10.0');
      $this->fail("Found unknown package version B 10.0");
    } catch (CriticalI_UnknownPackageVersionError $e) {
      // expected
    }
    
    // already installed
    $planner = new CriticalI_ChangeManager_Planner($this->buildPackageList('repositoryAB'),
      $this->buildPackageList('aProject'), false);
    $this->assertTrue($this->planMatches($planner->install_plan('A', '1.0'),
      array(), array()));
    
    // upgrade
    $planner = new CriticalI_ChangeManager_Planner($this->buildPackageList('repositoryABC'),
      $this->buildPackageList('abProject'), false);
    /*
    $this->assertTrue($this->planMatches($planner->install_plan('C', '1.0'),
      array('B'=>'1.2.0', 'C'=>'1.0.0'), array('B'=>'1.0.0')));
    */
    
    // fail when upgrade not allowed
    try {
      $planner->install_plan('C', '1.0');
      $this->fail("Upgraded a package when not allowed");
    } catch (CriticalI_ChangeManager_ResolutionError $e) {
      // expected
    }
    
    // lower version to meet requirements
    $planner = new CriticalI_ChangeManager_Planner($this->buildPackageList('repositoryABCDE'),
      $this->buildPackageList('aeProject'), false);
    $this->assertTrue($this->planMatches($planner->install_plan('D', '1.0'),
      array('B'=>'1.0.0', 'C'=>'1.0.0', 'D'=>'1.0.0'), array()));
    
    // impossible dependencies
    $planner = new CriticalI_ChangeManager_Planner($this->buildPackageList('repositoryABCDEF'),
      $this->buildPackageList('emptyProject'), false);
    try {
      $planner->install_plan('F');
      $this->fail("Installed a package with impossible dependencies");
    } catch (CriticalI_ChangeManager_ResolutionError $e) {
      // expected
    }
    
    // multiple versions allowed
    $planner = new CriticalI_ChangeManager_Planner($this->buildPackageList('repositoryABC'),
      $this->buildPackageList('abProject'), true);
    $this->assertTrue($this->planMatches($planner->install_plan('C', '1.0'),
      array('C'=>'1.0.0', 'B'=>'1.2.0'), array()));
    
    // cycle
    $planner = new CriticalI_ChangeManager_Planner($this->buildPackageList('repositoryABCDEFG'),
      $this->buildPackageList('emptyProject'), false);
    $this->assertTrue($this->planMatches($planner->install_plan('E'),
      array('A'=>'1.0.0', 'E'=>'1.0.0', 'F'=>'1.0.0', 'G'=>'1.0.0'), array()));
    
    // ignore dependencies
    $planner = new CriticalI_ChangeManager_Planner($this->buildPackageList('repositoryABCDEF'),
      $this->buildPackageList('emptyProject'), false);
    $this->assertTrue($this->planMatches($planner->install_plan('F', '1.0', false),
      array('F'=>'1.0.0'), array()));
  }
  
  /**
   * Return data for testing
   */
  protected function buildPackageList($name) {
    $result = array();
    
    $source = $this->$name;
    
    foreach ($source as $name=>$versions) {
      $result[$name] = new CriticalI_ChangeManager_PlannerTest_Package($name, $versions);
    }
    
    return $result;
  }
  
  /**
   * Test that a returned plan matches a description
   */
  protected function planMatches($plan, $addList, $removeList) {
    try {
      
      // test add
      foreach ($plan->add_list() as $ver) {
        $name = $ver->package()->name();
        $str = $ver->version_string();
        if (!isset($addList[$name]))
          throw new Exception("Added unexpected package $name $str");
        
        $expected = $addList[$name];
        if (is_array($expected)) {

          $go = true;
          for ($idx = count($expected) - 1; $go && $idx >= 0; $idx -= 1) {
            $test = CriticalI_Package_Versions::canonify_version($expected[$idx]);
            if ($ver->compare_version_number($test) == 0)
              $go = false;
          }
          
          if ($idx >= 0)
            array_splice($addList[$name], $idx, 1);
          else
            throw new Exception("Added unexpected version of $name $str");
          
          if (count($addList[$name]) == 0)
            unset($addList[$name]);
          
        } else {
          unset($addList[$name]);
          $expected = CriticalI_Package_Version::canonify_version($expected);
          if ($ver->compare_version_number($expected) != 0)
            throw new Exception("Added unexpected version of $name $str");
        }
      }
      
      if (count($addList) != 0)
        throw new Exception("Missing expected packages to add");
      

      // test remove
      foreach ($plan->remove_list() as $ver) {
        $name = $ver->package()->name();
        $str = $ver->version_string();
        if (!isset($removeList[$name]))
          throw new Exception("Removed unexpected package $name $str");
        
        $expected = $removeList[$name];
        if (is_array($expected)) {

          $go = true;
          for ($idx = count($expected) - 1; $go && $idx >= 0; $idx -= 1) {
            $test = CriticalI_Package_Versions::canonify_version($expected[$idx]);
            if ($ver->compare_version_number($test) == 0)
              $go = false;
          }
          
          if ($idx >= 0)
            array_splice($removeList[$name], $idx, 1);
          else
            throw new Exception("Removed unexpected version of $name $str");
          
          if (count($removeList[$name]) == 0)
            unset($removeList[$name]);
          
        } else {
          unset($removeList[$name]);
          $expected = CriticalI_Package_Version::canonify_version($expected);
          if ($ver->compare_version_number($expected) != 0)
            throw new Exception("Removed unexpected version of $name $str");
        }
      }
      
      if (count($removeList) != 0)
        throw new Exception("Missing expected packages to remove");

    } catch (Exception $e) {
      print_r($plan);
      $this->fail($e->getMessage());
      return false;
    }
    
    return true;
  }
  
  //
  // Test Data
  //
  
  protected $emptyProject = array();
  
  protected $aProject = array('A'=>array('1.0.0'=>array()));
  
  protected $abProject = array('A'=>array('1.0.0'=>array()),
    'B'=>array('1.0.0'=>array('A'=>'1.0')));

  protected $aeProject = array('A'=>array('1.0.0'=>array()),
    'E'=>array('1.0.0'=>array('A'=>'1.0')));

  protected $repositoryAB = array('A'=>array('1.0.0'=>array()),
    'B'=>array('1.0.0'=>array('A'=>'1.0')));
  
  protected $repositoryABC = array('A'=>array('1.0.0'=>array()),
    'B'=>array('1.0.0'=>array('A'=>'1.0'), '1.2.0'=>array('A'=>'1.0')),
    'C'=>array('1.0.0'=>array('B'=>'1.2')));
  
  protected $repositoryABCDE = array('A'=>array('1.0.0'=>array(), '2.0.0'=>array()),
    'B'=>array('1.0.0'=>array('A'=>'1.0'), '1.2.0'=>array('A'=>'2.0')),
    'C'=>array('1.0.0'=>array('B'=>'1.0'), '1.2.0'=>array('A'=>'2.0', 'B'=>'1.2')),
    'D'=>array('1.0.0'=>array('C'=>'1.0')),
    'E'=>array('1.0.0'=>array('A'=>'1.0')));

  protected $repositoryABCDEF = array('A'=>array('1.0.0'=>array(), '2.0.0'=>array()),
    'B'=>array('1.0.0'=>array('A'=>'1.0'), '1.2.0'=>array('A'=>'2.0')),
    'C'=>array('1.0.0'=>array('B'=>'1.0'), '1.2.0'=>array('A'=>'2.0', 'B'=>'1.2')),
    'D'=>array('1.0.0'=>array('C'=>'1.0')),
    'E'=>array('1.0.0'=>array('A'=>'1.0')),
    'F'=>array('1.0.0'=>array('A'=>'1.0', 'B'=>'1.2')));

  protected $repositoryABCDEFG = array('A'=>array('1.0.0'=>array(), '2.0.0'=>array()),
    'B'=>array('1.0.0'=>array('A'=>'1.0'), '1.2.0'=>array('A'=>'2.0')),
    'C'=>array('1.0.0'=>array('B'=>'1.0'), '1.2.0'=>array('A'=>'2.0', 'B'=>'1.2')),
    'D'=>array('1.0.0'=>array('C'=>'1.0')),
    'E'=>array('1.0.0'=>array('A'=>'1.0', 'F'=>'1.0')),
    'F'=>array('1.0.0'=>array('A'=>'1.0', 'G'=>'1.0')),
    'G'=>array('1.0.0'=>array('E'=>'1.0')));
}

?>