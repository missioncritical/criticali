<?php

class CriticalI_Project_ConfigFileWriterTest extends CriticalI_TestCase {
  
  protected function config_file() {
    return dirname(__FILE__) . '/../../test_config.php';
  }
  
  protected function config_contents() {
    return file_get_contents($this->config_file());
  }
  
  protected function set_config_contents($str) {
    if (file_put_contents($this->config_file(), $str) === false)
      throw new Exception("Could not write to file ".$this->config_file());
  }

  public function setUp() {
    $cfg = $this->config_file();
    if (file_exists($cfg)) unlink($cfg);
  }
  
  public function tearDown() {
    $cfg = $this->config_file();
    if (file_exists($cfg)) unlink($cfg);
  }

  public function testWriteFileNone() {
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_default('fruit', 'apple');
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"fruit\"] = \"apple\";\n?>", $this->config_contents());
  }
  
  public function testWriteFileAppend() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_default('fruit', 'apple');
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = \"apple\";\n", $this->config_contents());
  }

  public function testWriteFileInsert() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_default('fruit', 'apple');
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = \"apple\";\n?>", $this->config_contents());
  }
  
  public function testRevertFile() {
    $original = "<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n?>";
    $this->set_config_contents($original);
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_default('fruit', 'apple');
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = \"apple\";\n?>", $this->config_contents());
    
    $writer->revert_file();
    $this->assertEquals($original, $this->config_contents());
  }

  public function testSetDefaultNew() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $this->assertTrue($writer->set_default('fruit', 'apple'));
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = \"apple\";\n?>", $this->config_contents());
  }

  public function testSetDefaultExisting() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"fruit\"] = \"orange\";\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $this->assertFalse($writer->set_default('fruit', 'apple'));
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"fruit\"] = \"orange\";\n?>", $this->config_contents());
  }

  public function testSetDefaultMulti() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $this->assertTrue($writer->set_default('fruit/tree/stone', 'peach'));
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"][\"stone\"] = \"peach\";\n?>", $this->config_contents());
  }

  public function testSetDefaultPartial() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = array();\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $this->assertTrue($writer->set_default('fruit/tree/stone', 'peach'));
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"][\"stone\"] = \"peach\";\n?>", $this->config_contents());
  }

  public function testSetValueNew() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_value('fruit', 'apple');
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = \"apple\";\n?>", $this->config_contents());
  }

  public function testSetValueExisting() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"fruit\"] = \"orange\";\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_value('fruit', 'apple');
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"fruit\"] = \"orange\";\n\$APP_CONFIG[\"fruit\"] = \"apple\";\n?>", $this->config_contents());
  }

  public function testSetValueNonArray() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"fruit\"] = \"orange\";\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_value('fruit/tree/stone', 'peach');
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"fruit\"] = \"orange\";\n\$APP_CONFIG[\"fruit\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"][\"stone\"] = \"peach\";\n?>", $this->config_contents());
  }

  public function testSetValuePartialExisting() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = array();\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_value('fruit/tree/stone', 'peach');
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"][\"stone\"] = \"peach\";\n?>", $this->config_contents());
  }

  public function testSetValueNewMulti() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_default('fruit/tree/stone', 'peach');
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"][\"stone\"] = \"peach\";\n?>", $this->config_contents());
  }

  public function testSetValueNewPartial() {
    $this->set_config_contents("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = array();\n?>");
    
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_default('fruit/tree/stone', 'peach');
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"number\"] = \"one\";\n\$APP_CONFIG[\"fruit\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"] = array();\n\$APP_CONFIG[\"fruit\"][\"tree\"][\"stone\"] = \"peach\";\n?>", $this->config_contents());
  }
  
  public function testCurlyBracesExpand() {
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_default('message', "this directory (#{dirname(__FILE__)}) is not empty");
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"message\"] = \"this directory (\".dirname(__FILE__).\") is not empty\";\n?>", $this->config_contents());
  }

  public function testCurlyBracesEscaped() {
    $writer = new CriticalI_Project_ConfigFileWriter($this->config_file());
    $writer->set_default('message', "this directory (\\#{dirname(__FILE__)}) is not empty");
    $writer->write_file();
    
    $this->assertEquals("<?php\n\$APP_CONFIG[\"message\"] = \"this directory (#{dirname(__FILE__)}) is not empty\";\n?>", $this->config_contents());
  }

}

?>