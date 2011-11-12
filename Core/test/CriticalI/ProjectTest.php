<?php

class ProjectStub extends CriticalI_Project {
  public function __construct($type, $properties = array()) {
    $this->type = $type;
    $this->properties = $properties;
  }
  
  public function public_add_init_file($file) { $this->add_init_file($file); }
  public function public_remove_init_file($file) { $this->remove_init_file($file); }
  public function public_uninstall_init_script_listings($manifest)
    { $this->uninstall_init_script_listings($manifest); }
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
