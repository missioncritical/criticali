<?php

class Migration_BaseTest_SimpleModel {
}

class Migration_BaseTest_Empty extends Migration_Base {
  public function up() { }
  public function model_instance() {
    return $this->model('Migration_BaseTest_SimpleModel');
  }
}

class Migration_BaseTest extends CriticalI_TestCase {
  
  public function testModel() {
    $migration = new Migration_BaseTest_Empty();

    $this->assertTrue(($migration->model_instance() instanceof Migration_BaseTest_SimpleModel));
  }
  
}
