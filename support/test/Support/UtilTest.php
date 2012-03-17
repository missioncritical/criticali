<?php

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
  
}

?>