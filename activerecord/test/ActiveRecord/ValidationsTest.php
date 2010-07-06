<?php

class ValidationName extends ActiveRecord_Base {
  protected function init_class() {
    $this->validates_presence_of(array('first_name', 'last_name'));
  }
}

class ConditionalName extends ActiveRecord_Base {

  protected $doValidateFirst = false;
  protected $doValidateLast = false;

  protected function init_class() {
    $this->set_table_name('validation_names');
    $this->validates_presence_of(array('first_name'), array('if'=>'should_validate_first'));
    $this->validates_presence_of(array('last_name'),  array('if'=>'should_validate_last'));
  }

  public function set_should_validate_first($flag) { $this->doValidateFirst = $flag; }
  public function should_validate_first() { return $this->doValidateFirst; }
  public function set_should_validate_last($flag) { $this->doValidateLast = $flag; }
  public function should_validate_last() { return $this->doValidateLast; }
}

class ActiveRecord_ValidationsTest extends Vulture_DBTestCase {

  public function testValidatesPresenceOf() {
    $name = new ValidationName();
    $name->first_name = 'John';
    $name->last_name = 'Doe';

    $this->assertTrue($name->is_valid());
    $this->assertTrue($name->save());

    $jane = new ValidationName();
    $jane->first_name = 'Jane';
    $jane->last_name = 'Doe';

    $this->assertTrue($jane->save());

    $smith = new ValidationName();
    $smith->last_name = 'Smith';

    $this->assertFalse($smith->is_valid());
    $this->assertFalse($smith->save());

    $jones = new ValidationName();
    $jones->last_name = 'Jones';

    $this->assertFalse($jones->save());

    $this->assertTrue($name->errors()->is_empty());
    $this->assertFalse($jones->errors()->is_empty());
    $this->assertEquals(1, $jones->errors()->size());
    $msgs = $jones->errors()->full_messages();
    $this->assertEquals('First name is required', $msgs[0]);
    $this->assertTrue($jones->errors()->is_invalid('first_name'));
    $this->assertFalse($jones->errors()->is_invalid('last_name'));
    $msgs = $jones->errors()->on('first_name');
    $this->assertEquals('is required', $msgs[0]);

    $this->assertTrue($name->exists($name->id));
    $this->assertTrue($name->exists($jane->id));
    $this->assertFalse($name->exists(array('last_name=?', 'Smith')));
    $this->assertFalse($name->exists(array('last_name=?', 'Jones')));
  }

  public function testValidatesWithIf() {
    $hasFirst = new ConditionalName();
    $hasFirst->first_name = 'First';

    $hasLast = new ConditionalName();
    $hasLast->last_name = 'Last';

    $hasBoth = new ConditionalName();
    $hasBoth->first_name = 'First';
    $hasBoth->last_name = 'Last';

    // test condition false
    $this->assertTrue($hasFirst->is_valid());
    $this->assertTrue($hasLast->is_valid());
    $this->assertTrue($hasBoth->is_valid());

    // test condition true
    $hasFirst->set_should_validate_first(true);
    $hasLast->set_should_validate_first(true);
    $hasBoth->set_should_validate_first(true);

    $this->assertTrue($hasFirst->is_valid());
    $this->assertFalse($hasLast->is_valid());
    $this->assertTrue($hasBoth->is_valid());
  }
}

?>
