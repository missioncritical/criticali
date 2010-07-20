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
}

?>