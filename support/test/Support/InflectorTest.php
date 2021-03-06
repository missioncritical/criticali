<?php

class Support_InflectorTest extends CriticalI_TestCase {
  
  public function testCamelize() {
    $this->assertEquals('TableName', Support_Inflector::camelize('table_name'));
    $this->assertEquals('TableName', Support_Inflector::camelize('table name'));
    $this->assertEquals('TableName', Support_Inflector::camelize('TableName'));
    $this->assertEquals('TableName', Support_Inflector::camelize('Table name'));
    $this->assertEquals('tableName', Support_Inflector::camelize('table_name', false));
    $this->assertEquals('Table/Name', Support_Inflector::camelize('table/name'));
    $this->assertEquals('table/name', Support_Inflector::camelize('table/name', false));
    $this->assertEquals('tableName/more', Support_Inflector::camelize('table_name/more', false));
  }
  
  public function testUnderscore() {
    $this->assertEquals('table_name', Support_Inflector::underscore('table_name'));
    $this->assertEquals('table_name', Support_Inflector::underscore('TableName'));
  }
  
  public function testTableize() {
    $this->assertEquals('table_names', Support_Inflector::tableize('table_name'));
    $this->assertEquals('table_names', Support_Inflector::tableize('TableName'));
  }

  public function testHumanize() {
    $this->assertEquals('Table name', Support_Inflector::humanize('table_name'));
    $this->assertEquals('Table name', Support_Inflector::humanize('table name'));
    $this->assertEquals('TableName', Support_Inflector::humanize('TableName'));
    $this->assertEquals('Table name', Support_Inflector::humanize('Table name'));
    $this->assertEquals('Table name', Support_Inflector::humanize('table_name_id'));
  }

}

?>