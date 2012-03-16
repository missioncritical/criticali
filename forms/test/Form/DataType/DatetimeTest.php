<?php

class Form_DataType_DatetimeTest extends CriticalI_TestCase {
  
  public function testParseControlValue() {
    $dt = new Form_DataType_Datetime();

    $this->assertEquals('2012-03-15 04:24:59',
      $dt->parse_control_value(array('year'=>'2012', 'month'=>'03', 'day'=>'15',
                                     'hour'=>'04', 'minute'=>'24', 'second'=>'59')));

    $this->assertEquals('2012-03-15 16:24:59',
      $dt->parse_control_value(array('year'=>'2012', 'month'=>'03', 'day'=>'15',
                                     'hour'=>'16', 'minute'=>'24', 'second'=>'59')));

    $this->assertEquals('2012-03-15 04:24:59',
      $dt->parse_control_value(array('year'=>'2012', 'month'=>'03', 'day'=>'15',
                                     'hour'=>'04', 'minute'=>'24', 'second'=>'59', 'meridian'=>'am')));

    $this->assertEquals('2012-03-15 16:24:59',
      $dt->parse_control_value(array('year'=>'2012', 'month'=>'03', 'day'=>'15',
                                     'hour'=>'04', 'minute'=>'24', 'second'=>'59', 'meridian'=>'pm')));
  }
  
}
