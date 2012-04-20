<?php

class Support_UtilTest_ModelA {
  public $value;
}
class Support_UtilTest_ModelB {
  public $value;
  public $readonly = false;
  public function set_readonly($flag) { $this->readonly = $flag; }
}


class Support_UtilTest extends CriticalI_TestCase {
  
  public function testValidateOptions() {
    // valid
    Support_Util::validate_options(array('a'=>'A', 'b'=>'B'), array('a'=>1, 'b'=>1, 'c'=>1));
    
    // invalid
    try {
      Support_Util::validate_options(array('a'=>'A', 'b'=>'B', 'd'=>'D'), array('a'=>1, 'b'=>1, 'c'=>1));
      throw new Exception("Invalid options passed validation");
    } catch (Support_UnknownOptionError $e) {
      $this->assertEquals('d', $e->optionName());
    }
  }
  
  public function testOptionsFromArgumentList() {
    $args = array();
    $this->assertEquals(array(), Support_Util::options_from_argument_list($args));

    $args = array(1, 2);
    $this->assertEquals(array(), Support_Util::options_from_argument_list($args));
    $this->assertEquals(array(1, 2), $args);

    $args = array(1, 2, array('foo', 'bar'));
    $this->assertEquals(array(), Support_Util::options_from_argument_list($args));
    $this->assertEquals(array(1, 2, array('foo', 'bar')), $args);

    $args = array(1, 2, array('foo'=>'bar'));
    $this->assertEquals(array('foo'=>'bar'), Support_Util::options_from_argument_list($args));
    $this->assertEquals(array(1, 2), $args);

    $args = array(array('foo'=>'bar'));
    $this->assertEquals(array('foo'=>'bar'), Support_Util::options_from_argument_list($args));
    $this->assertEquals(array(), $args);

    $args = array(1, 2, array('foo'=>'bar'), 3);
    $this->assertEquals(array(), Support_Util::options_from_argument_list($args));
    $this->assertEquals(array(1, 2, array('foo'=>'bar'), 3), $args);
  }
  
  public function testModel() {
    $a1 = Support_Util::model('Support_UtilTest_ModelA');
    
    $this->assertTrue(($a1 instanceof Support_UtilTest_ModelA));
    
    $a1->value = 5;
    $a2 = Support_Util::model('Support_UtilTest_ModelA');

    $this->assertTrue(($a2 instanceof Support_UtilTest_ModelA));
    $this->assertEquals(5, $a1->value);
    $this->assertEquals(5, $a2->value);

    $b = Support_Util::model('Support_UtilTest_ModelB');

    $this->assertTrue(($b instanceof Support_UtilTest_ModelB));
    $this->assertTrue($b->readonly);
    
    try {
      $unknown = Support_Util::model('Support_UtilTest_UnknownClassName');
      $this->fail('Provided an instance of an unknown model');
    } catch (Support_UnknownClassError $e) {
      // expected
    }
  }
  
}

?>