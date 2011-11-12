<?php

class ProjectStub extends CriticalI_Project {
  public function __construct($type, $properties = array()) {
    $this->type = $type;
    $this->properties = $properties;
  }
  
  public function public_add_init_file($file) { $this->add_init_file($file); }
  public function public_remove_init_file($file) { $this->remove_init_file($file); }
  public function public_install_property_defaults($install, $pkg)
    { $this->install_property_defaults($install, $pkg); }
  public function public_install_dependency_list($pkg) { $this->install_dependency_list($pkg); }
  public function public_install_package_in_list($install, $pkg)
    { $this->install_package_in_list($install, $pkg); }
  public function public_uninstall_dependency_list($packageName)
    { $this->uninstall_dependency_list($packageName); }
  public function public_uninstall_package_in_list($packageName)
    { $this->uninstall_package_in_list($packageName); }
  public function public_uninstall_init_script_listings($manifest)
    { $this->uninstall_init_script_listings($manifest); }
}

class InstallStub extends CriticalI_Project_InstallOperation {
  public $defaultConfigValues = array();
  
  public function set_default_config_value($name, $value) {
    $this->defaultConfigValues[$name] = $value;
  }
  
  public function addFile($file) { $this->filesAdded[] = $file; }
}

class CriticalI_ProjectTest extends CriticalI_TestCase {
  
  public function testAddInitFile() {
    // empty
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC, array());
    $project->public_add_init_file('Alpha/Hook/Init.php');
    $this->assertEquals('Alpha/Hook/Init.php', $project->property('init_files'));
    
    // populated
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC, array('init_files'=>'Alpha/Hook/Init.php'));
    $project->public_add_init_file('Beta/Hook/Init.php');
    $this->assertEquals('Alpha/Hook/Init.php,Beta/Hook/Init.php', $project->property('init_files'));

    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('init_files'=>'Alpha/Hook/Init.php,Beta/Hook/Init.php'));
    $project->public_add_init_file('Gamma/Hook/Init.php');
    $this->assertEquals('Alpha/Hook/Init.php,Beta/Hook/Init.php,Gamma/Hook/Init.php',
      $project->property('init_files'));
    
    // duplicate
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('init_files'=>'Alpha/Hook/Init.php,Beta/Hook/Init.php'));
    $project->public_add_init_file('Beta/Hook/Init.php');
    $this->assertEquals('Alpha/Hook/Init.php,Beta/Hook/Init.php', $project->property('init_files'));
  }
  
  public function testRemoveInitFile() {
    // empty (not present)
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC, array());
    $project->public_remove_init_file('Alpha/Hook/Init.php');
    $this->assertEquals('', $project->property('init_files', ''));

    // present
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC, array('init_files'=>'Alpha/Hook/Init.php'));
    $project->public_remove_init_file('Alpha/Hook/Init.php');
    $this->assertEquals('', $project->property('init_files', ''));

    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('init_files'=>'Alpha/Hook/Init.php,Beta/Hook/Init.php'));
    $project->public_remove_init_file('Alpha/Hook/Init.php');
    $this->assertEquals('Beta/Hook/Init.php', $project->property('init_files'));

    // not present (not empty)
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('init_files'=>'Alpha/Hook/Init.php,Beta/Hook/Init.php'));
    $project->public_remove_init_file('Gamma/Hook/Init.php');
    $this->assertEquals('Alpha/Hook/Init.php,Beta/Hook/Init.php', $project->property('init_files'));
  }
  
  public function testInstallPropertyDefaults() {
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC, array());

    // none
    $package = new CriticalI_Project_Package('alpha', '1.2.3', array());
    $pkg = $package->newest();
    $install = new InstallStub($project, $pkg);
    $project->public_install_property_defaults($install, $pkg);
    $this->assertEquals(array(), $install->defaultConfigValues);

    // one
    $package = new CriticalI_Project_Package('alpha', '1.2.3',
      array('config.defaults'=>array('B'=>'Bravo')));
    $pkg = $package->newest();
    $install = new InstallStub($project, $pkg);
    $project->public_install_property_defaults($install, $pkg);
    $this->assertEquals(array('B'=>'Bravo'), $install->defaultConfigValues);

    // multiple
    $package = new CriticalI_Project_Package('alpha', '1.2.3',
      array('config.defaults'=>array('B'=>'Bravo', 'L'=>'Lima', 'T'=>'Tango')));
    $pkg = $package->newest();
    $install = new InstallStub($project, $pkg);
    $project->public_install_property_defaults($install, $pkg);
    $this->assertEquals(array('B'=>'Bravo', 'L'=>'Lima', 'T'=>'Tango'), $install->defaultConfigValues);
  }
  
  public function testInstallDependencyList() {
    // none
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC, array());
    $package = new CriticalI_Project_Package('alpha', '1.2.3', array());
    $project->public_install_dependency_list($package->newest());
    $this->assertEquals(array(), $project->property('depends_on', array()));
    
    // one
    $package = new CriticalI_Project_Package('alpha', '1.2.3',
      array('dependencies'=>array('Beta'=>'1.0')));
    $project->public_install_dependency_list($package->newest());
    $this->assertEquals(array('alpha'=>'Beta=1.0'), $project->property('depends_on'));
    
    // multiple
    $package = new CriticalI_Project_Package('alpha', '1.2.3',
      array('dependencies'=>array('Beta'=>'1.0', 'Gamma'=>'1.2')));
    $project->public_install_dependency_list($package->newest());
    $this->assertEquals(array('alpha'=>'Beta=1.0,Gamma=1.2'), $project->property('depends_on'));
  }
  
  public function testInstallPackageInList() {
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC, array());

    $package = new CriticalI_Project_Package('alpha', '1.2.3', array());
    $pkg = $package->newest();

    $install = new InstallStub($project, $pkg);
    $install->addFile('private/vendor/Alpha/Whiskey.php');
    $install->addFile('private/vendor/Alpha/Tango.php');
    $install->addFile('private/vendor/Alpha/Foxtrot.php');
    
    $project->public_install_package_in_list($install, $pkg);
    
    $this->assertEquals(array('alpha'=>'1.2.3'), $project->property('packages'));
    
    $this->assertEquals(array('alpha'=>serialize(array('private/vendor/Alpha/Whiskey.php',
      'private/vendor/Alpha/Tango.php', 'private/vendor/Alpha/Foxtrot.php'))),
      $project->property('manifests'));
    
    $list = $project->package_list();
    $this->assertTrue(isset($list['alpha']));
  }
  
  public function testUninstallDependencyList() {
    // no dependencies
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC, array());
    $project->public_uninstall_dependency_list('alpha');
    $this->assertEquals(array(), $project->property('depends_on', array()));
    
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('depends_on'=>array('beta'=>'gamma=1.0')));
    $project->public_uninstall_dependency_list('alpha');
    $this->assertEquals(array('beta'=>'gamma=1.0'), $project->property('depends_on'));

    // has dependencies
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('depends_on'=>array('alpha'=>'gamma=1.0,delta=1.0', 'beta'=>'gamma=1.0')));
    $project->public_uninstall_dependency_list('alpha');
    $this->assertEquals(array('beta'=>'gamma=1.0'), $project->property('depends_on'));
  }
  
  public function testUninstallPackageInList() {
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array(
        'packages'=>array(
          'alpha'=>'1.0',
          'beta'=>'1.2.3'
        ),
        
        'manifests'=>array(
          'alpha'=>serialize(array(
            'private/vendor/Alpha/Bravo.php',
            'private/vendor/Alpha/Charlie.php'
          )),
          'beta'=>serialize(array(
            'private/vendor/Beta/Whiskey.php',
            'private/vendor/Beta/Tango.php',
            'private/vendor/Beta/Foxtrot.php'
          ))
        ),
        
        'uninstallers'=>array(
          'alpha'=>'Alpha/Charlie.php',
          'beta'=>'Bravo/Foxtrot.php'
        )
      ));
    
    $list = $project->package_list();
    $this->assertTrue(isset($list['alpha']));
    
    $project->public_uninstall_package_in_list('alpha');
    
    $this->assertEquals(array('beta'=>'1.2.3'), $project->property('packages'));
    $this->assertEquals(array('beta'=>serialize(array(
      'private/vendor/Beta/Whiskey.php', 'private/vendor/Beta/Tango.php',
      'private/vendor/Beta/Foxtrot.php'))), $project->property('manifests'));
    $this->assertEquals(array('beta'=>'Bravo/Foxtrot.php'), $project->property('uninstallers'));

    $list = $project->package_list();
    $this->assertFalse(isset($list['alpha']));
  }
  
  public function testUninstallInitScriptListings() {
    // empty list (none found)
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC, array());
    $project->public_uninstall_init_script_listings(array(
      'private/vendor/Alpha/Alpha.php', 'private/vendor/Alpha/Bravo.php',
      'private/vendor/Alpha/Charlie.php'));
    $this->assertEquals('', $project->property('init_files', ''));

    // none found
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC, array('init_files'=>'Beta/Hook/Init.php'));
    $project->public_uninstall_init_script_listings(array(
      'private/vendor/Alpha/Alpha.php', 'private/vendor/Alpha/Bravo.php',
      'private/vendor/Alpha/Charlie.php'));
    $this->assertEquals('Beta/Hook/Init.php', $project->property('init_files'));

    // found - inside public / single forward slash
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('init_files'=>'Alpha/Hook/Init.php,Beta/Hook/Init.php'));
    $project->public_uninstall_init_script_listings(array(
      'private/vendor/Alpha/Alpha.php', 'private/vendor/Alpha/Bravo.php',
      'private/vendor/Alpha/Charlie.php', 'private/vendor/Alpha/Hook/Init.php'));
    $this->assertEquals('Beta/Hook/Init.php', $project->property('init_files'));

    // found multiple
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('init_files'=>'Alpha/Hook/Init.php,Beta/Hook/Init.php,Alpha/Hook/Init2.php'));
    $project->public_uninstall_init_script_listings(array(
      'private/vendor/Alpha/Alpha.php', 'private/vendor/Alpha/Bravo.php',
      'private/vendor/Alpha/Charlie.php', 'private/vendor/Alpha/Hook/Init.php',
      'private/vendor/Alpha/Hook/Init2.php'));
    $this->assertEquals('Beta/Hook/Init.php', $project->property('init_files'));

    // found - inside public / double forward slash
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('init_files'=>'Alpha/Hook/Init.php,Beta/Hook/Init.php'));
    $project->public_uninstall_init_script_listings(array(
      'private/vendor//Alpha/Alpha.php', 'private/vendor//Alpha/Bravo.php',
      'private/vendor//Alpha/Charlie.php', 'private/vendor//Alpha/Hook/Init.php'));
    $this->assertEquals('Beta/Hook/Init.php', $project->property('init_files'));

    // found - inside public / backslash
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('init_files'=>"Alpha\\Hook\\Init.php,Beta\\Hook\\Init.php"));
    $project->public_uninstall_init_script_listings(array(
      "private\\vendor\\Alpha\\Alpha.php", "private\\vendor\\Alpha\\Bravo.php",
      "private\\vendor\\Alpha\\Charlie.php", "private\\vendor\\Alpha\\Hook\\Init.php"));
    $this->assertEquals("Beta\\Hook\\Init.php", $project->property('init_files'));

    // found - outside public
    $project = new ProjectStub(ProjectStub::OUTSIDE_PUBLIC,
      array('init_files'=>'Alpha/Hook/Init.php,Beta/Hook/Init.php'));
    $project->public_uninstall_init_script_listings(array(
      'vendor/Alpha/Alpha.php', 'vendor/Alpha/Bravo.php',
      'vendor/Alpha/Charlie.php', 'vendor/Alpha/Hook/Init.php'));
    $this->assertEquals('Beta/Hook/Init.php', $project->property('init_files'));
    
    // dedup
    $project = new ProjectStub(ProjectStub::INSIDE_PUBLIC,
      array('init_files'=>'Alpha/Hook/Init.php,Beta/Hook/Init.php,Alpha/Hook/Init.php'));
    $project->public_uninstall_init_script_listings(array(
      'private/vendor/Alpha/Alpha.php', 'private/vendor/Alpha/Bravo.php',
      'private/vendor/Alpha/Charlie.php', 'private/vendor/Alpha/Hook/Init.php'));
    $this->assertEquals('Beta/Hook/Init.php', $project->property('init_files'));
  }
  
}
