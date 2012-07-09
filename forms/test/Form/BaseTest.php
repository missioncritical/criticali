<?php

class Form_BaseTest extends CriticalI_TestCase {
  
  public function testName() {
    $form = new Form_Default();
    $this->assertEquals('form/default', $form->name());

    $form = new Form_Default('foo');
    $this->assertEquals('foo', $form->name());
  }
  
}
