<?php

class Form_DataType_TimeTest extends CriticalI_TestCase {
  
  public function testParseControlValue() {
    $dt = new Form_DataType_Time();

    $this->assertEquals('04:24:59',
      $dt->parse_control_value(array('hour'=>'04', 'minute'=>'24', 'second'=>'59')));

    $this->assertEquals('16:24:59',
      $dt->parse_control_value(array('hour'=>'16', 'minute'=>'24', 'second'=>'59')));

    $this->assertEquals('04:24:59',
      $dt->parse_control_value(array('hour'=>'04', 'minute'=>'24', 'second'=>'59', 'meridian'=>'am')));

    $this->assertEquals('16:24:59',
      $dt->parse_control_value(array('hour'=>'04', 'minute'=>'24', 'second'=>'59', 'meridian'=>'pm')));
  }
  
}
