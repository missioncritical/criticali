<?php

class Migration_RunnerTest extends CriticalI_DBTestCase {
  
  public function setUp() {
    $conn = $this->dbConnection();
    $conn->exec("DROP TABLE IF EXISTS example_records");
    $conn->exec("DROP TABLE IF EXISTS migrations");
  }
  
  public function testForward() {
    $conn = $this->dbConnection();
    
    $this->assertFalse($this->hasTable('example_records'));
    
    $runner = new Migration_Runner('migrations');
    $runner->run();

    $this->assertTrue($this->hasTable('example_records'));
    $this->assertEquals('one', $conn->selectValue("SELECT value FROM example_records WHERE value='one'"));
    $this->assertEquals('two', $conn->selectValue("SELECT value FROM example_records WHERE value='two'"));
  }
  
  public function testPartialForward() {
    $conn = $this->dbConnection();
    
    $this->assertFalse($this->hasTable('example_records'));
    
    $runner = new Migration_Runner('migrations');
    $runner->run('002');

    $this->assertTrue($this->hasTable('example_records'));
    $this->assertEquals('one', $conn->selectValue("SELECT value FROM example_records WHERE value='one'"));
    $this->assertEquals(null, $conn->selectValue("SELECT value FROM example_records WHERE value='two'"));
  }

  public function testReverse() {
    $this->testForward();

    $runner = new Migration_Runner('migrations');
    $runner->run('0');

    $this->assertFalse($this->hasTable('example_records'));
  }

  public function testPartialReverse() {
    $this->testForward();

    $runner = new Migration_Runner('migrations');
    $runner->run('001');

    $conn = $this->dbConnection();
    $this->assertTrue($this->hasTable('example_records'));
    $this->assertEquals(null, $conn->selectValue("SELECT value FROM example_records WHERE value='one'"));
    $this->assertEquals(null, $conn->selectValue("SELECT value FROM example_records WHERE value='two'"));
    
    $runner->run('002');
    $this->assertTrue($this->hasTable('example_records'));
    $this->assertEquals('one', $conn->selectValue("SELECT value FROM example_records WHERE value='one'"));
    $this->assertEquals(null, $conn->selectValue("SELECT value FROM example_records WHERE value='two'"));
    
    $conn->exec("INSERT INTO example_records (value) VALUES ('two')");
    $runner->run('001');
    $this->assertTrue($this->hasTable('example_records'));
    $this->assertEquals(null, $conn->selectValue("SELECT value FROM example_records WHERE value='one'"));
    $this->assertEquals('two', $conn->selectValue("SELECT value FROM example_records WHERE value='two'"));
  }

  protected function hasTable($name) {
    $conn = $this->dbConnection();
    foreach ($conn->tables() as $table) {
      if ($table == $name)
        return true;
    }
    return false;
  }
  
  protected function dbConnection() {
    return Support_Resources::db_connection(true, false, 'activerecord');
  }

}
  
?>