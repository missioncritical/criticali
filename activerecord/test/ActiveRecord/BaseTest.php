<?php

class Name extends ActiveRecord_Base {
}

abstract class TestClassBase extends ActiveRecord_Base {
}

class Document extends TestClassBase {
}

class Student extends TestClassBase {
  protected function init_class() {
    $this->set_primary_key('student_id');
    $this->set_table_name('student_records');
  }
}

class PrefixedObject extends TestClassBase {
  protected function init_class() {
    $this->set_table_name_prefix('prfx_');
  }
}

class SuffixedObject extends TestClassBase {
  protected function init_class() {
    $this->set_table_name_suffix('_sfx');
  }
}

class Vehicle extends TestClassBase {
}

class Car extends Vehicle {
}

class Truck extends Vehicle {
}

class Pastry extends TestClassBase {
  protected function init_class() {
    $this->set_inheritance_column('kind');
  }
}

class Puff extends Pastry {
}

class Choux extends Pastry {
}

class Base_UnprotectedUser extends ActiveRecord_Base {
  public function init_class() {
    $this->set_table_name('users');
  }
}

class Base_ProtectedUser extends ActiveRecord_Base {
  public function init_class() {
    $this->set_table_name('users');
    $this->attr_protected('password');
  }
}

class ActiveRecord_BaseTest extends Vulture_DBTestCase {

  /*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
   * TESTS FOR PUBLIC GENERAL CLASS-LEVEL OPERATIONS
   *++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/


  public function testSaveNew() {
    $name = new Name();
    $name->first_name = 'John';
    $name->last_name = 'Doe';

    $this->assertTrue($name->save());

    $rows = $name->connection()->selectAll("SELECT * FROM names WHERE id=".$name->id());
    $this->assertEquals(1, count($rows));

    $this->assertEquals('John', $rows[0]['first_name']);
    $this->assertEquals('Doe',  $rows[0]['last_name']);
  }

  public function testFindById() {
    $name = new Name();
    $jane = $name->find(1);

    $this->assertEquals(1,       $jane->id);
    $this->assertEquals('Jane',  $jane->first_name);
    $this->assertEquals('Smith', $jane->last_name);

    $this->assertTrue($jane->save()); // tests no extra attributes were set
  }

  public function testSaveExisting() {
    $name = new Name();
    $gabe = $name->find(2);
    $gabe->last_name = 'Garcia Marquez';
    $this->assertTrue($gabe->save());

    $rows = $gabe->connection()->selectAll("SELECT * FROM names WHERE id=2");
    $this->assertEquals(1, count($rows));

    $this->assertEquals('Gabriel',        $rows[0]['first_name']);
    $this->assertEquals('Garcia Marquez', $rows[0]['last_name']);
  }

  public function testSetAttributes() {
    $maggy = new Name();
    $maggy->set_attributes(array('last_name'=>'Atwood', 'first_name'=>'Margaret'));

    $this->assertEquals('Margaret', $maggy->first_name);
    $this->assertEquals('Atwood',   $maggy->last_name);
    $this->assertTrue($maggy->save());

    $maggy->set_attributes(array('id'=>200));
    $this->assertTrue($maggy->id != 200);

    try {
      $maggy->set_attributes(array('nonesuch'=>'foo'));
      $this->fail("Expected exception ActiveRecord_UnknownPropertyError was not raised.");
    } catch (ActiveRecord_UnknownPropertyError $err) {
      // expected
    }
  }

  public function testFindMultipleId() {
    $name = new Name();
    $results = $name->find(1, 3);

    $this->assertEquals(2, count($results));

    $janeS = $results[0]->id == 1 ? $results[0] : $results[1];
    $janeA = $results[0]->id == 1 ? $results[1] : $results[0];

    $this->assertEquals(1,       $janeS->id);
    $this->assertEquals('Jane',  $janeS->first_name);
    $this->assertEquals('Smith', $janeS->last_name);

    $this->assertEquals(3,       $janeA->id);
    $this->assertEquals('Jane',  $janeA->first_name);
    $this->assertEquals('Ayre',  $janeA->last_name);

    try {
      $name->find(200);
      $this->fail('Did not raise ActiveRecord_NotFoundError for find() with a non-existent id.');
    } catch (ActiveRecord_NotFoundError $err) {
      // expected
    }

    try {
      $name->find(1, 200);
      $this->fail('Did not raise ActiveRecord_NotFoundError for find() with a non-existent id included in a list.');
    } catch (ActiveRecord_NotFoundError $err) {
      // expected
    }
  }

  public function testFindFirst() {
    $name = new Name();

    $results = $name->find_first(array('conditions'=>"first_name='Jane'"));
    $this->assertEquals(1, count($results));
    $this->assertTrue($results->id == 1 || $results->id == 3);
    $this->assertFalse($results->readonly());

    $results = $name->find_first(array('conditions'=>'id=200'));
    $this->assertTrue(is_null($results));

    $results = $name->find_first(array('conditions'=>"first_name='Jane'", 'order'=>'last_name'));
    $this->assertEquals(3, $results->id);

    $results = $name->find_first(array('conditions'=>array('first_name=?', 'Jane'), 'order'=>'last_name'));
    $this->assertEquals(3, $results->id);

    $results = $name->find_first(array('conditions'=>"first_name='Jane'", 'order'=>'last_name', 'offset'=>2));
    $this->assertEquals(1, $results->id);

    $results = $name->find_first(array('conditions'=>"first_name='Jane'", 'order'=>'last_name', 'readonly'=>true));
    $this->assertTrue($results->readonly());
    
    $results = $name->find_first();
    $this->assertEquals('Name', get_class($results));

    try {
      $results = $name->find_first(array('conditions'=>"first_name='Jane'", 'nonesuch'=>1));
      $this->fail("Did not error out on invalid find option \"nonesuch\"");
    } catch (ActiveRecord_UnknownOptionError $err) {
      // expected
    }
  }

  public function testFindAll() {
    $name = new Name();

    $results = $name->find_all(array('conditions'=>"first_name='Jane'"));
    $this->assertEquals(2, count($results));

    $janeS = $results[0]->id == 1 ? $results[0] : $results[1];
    $janeA = $results[0]->id == 1 ? $results[1] : $results[0];

    $this->assertEquals(1,       $janeS->id);
    $this->assertEquals(3,       $janeA->id);
    
    $results = $name->find_all();
    $this->assertEquals(15, count($results));
  }

  public function testFindBySQL() {
    $name = new Name();

    $results = $name->find_by_sql("SELECT id, first_name, last_name, first_name || ' ' || last_name AS full_name FROM names WHERE first_name='Jane'");
    $this->assertEquals(2, count($results));

    $janeS = $results[0]->id == 1 ? $results[0] : $results[1];
    $janeA = $results[0]->id == 1 ? $results[1] : $results[0];

    $this->assertEquals(1,            $janeS->id);
    $this->assertEquals('Jane Smith', $janeS->full_name);

    $this->assertEquals(3,            $janeA->id);
    $this->assertEquals('Jane Ayre',  $janeA->full_name);
  }

  public function testDynamicFindById() {
    $name = new Name();
    $jane = $name->find_by_id(1);

    $this->assertEquals(1,       $jane->id);
    $this->assertEquals('Jane',  $jane->first_name);
    $this->assertEquals('Smith', $jane->last_name);
    
    $this->assertEquals(null, $name->find_by_id(100));
  }

  public function testExists() {
    $name = new Name();

    $this->assertTrue($name->exists(1));
    $this->assertFalse($name->exists(200));
    $this->assertTrue($name->exists(array('first_name=?', 'Jane')));
    $this->assertFalse($name->exists(array('first_name=?', 'Eloise')));
  }

  public function testCreate() {
    $name = new Name();

    $this->assertTrue($name->new_record());

    $blank = $name->create();
    $this->assertFalse($blank->new_record());

    $eudora = $name->create(array('first_name' => 'Eudora',
                                  'last_name'  => 'Welty'));
    $this->assertFalse($eudora->new_record());
    $this->assertEquals('Eudora', $eudora->first_name);
    $this->assertEquals('Welty',  $eudora->last_name);
    $this->assertFalse(empty($eudora->id));
  }

  public function testUpdate() {
    $name = new Name();

    $gael = $name->update(5, array('last_name'=>'Garcia Bernal'));
    $this->assertEquals(5,               $gael->id);
    $this->assertEquals('Gael',          $gael->first_name);
    $this->assertEquals('Garcia Bernal', $gael->last_name);

    list($first, $second) = $name->update(array(6, 8), array(array('first_name'=>'Andres Manuel'), array('last_name'=>'Fox Quesada')));

    $this->assertEquals(6,               $first->id);
    $this->assertEquals('Andres Manuel', $first->first_name);
    $this->assertEquals('Lopez Obrador', $first->last_name);

    $this->assertEquals(8,               $second->id);
    $this->assertEquals('Vicente',       $second->first_name);
    $this->assertEquals('Fox Quesada',   $second->last_name);
  }

  public function testDelete() {
    $name = new Name();

    $this->assertTrue($name->exists(9));
    $this->assertTrue($name->exists(10));
    $this->assertTrue($name->exists(11));

    $this->assertEquals(1, $name->delete(10));
    $this->assertFalse($name->exists(10));
    $this->assertEquals(2, $name->delete(9, 11));
    $this->assertFalse($name->exists(9));
    $this->assertFalse($name->exists(11));
  }

  public function testUpdateAll() {
    $name = new Name();

    $this->assertEquals(2, $name->update_all("first_name='Francisco'", "id IN (4, 7)"));

    list($sanPancho, $pancho) = $name->find_all(array('conditions'=>'id IN(4, 7)', 'order'=>'id'));

    $this->assertEquals(7,               $pancho->id);
    $this->assertEquals('Francisco',     $pancho->first_name);
    $this->assertEquals('Villa',         $pancho->last_name);

    $this->assertEquals(4,               $sanPancho->id);
    $this->assertEquals('Francisco',     $sanPancho->first_name);
    $this->assertEquals('Bernardone',    $sanPancho->last_name);
  }

  public function testDeleteAll() {
    $name = new Name();

    $this->assertTrue($name->exists(12));
    $this->assertTrue($name->exists(13));

    $name->delete_all("last_name='Bayless'");

    $this->assertFalse($name->exists(12));
    $this->assertFalse($name->exists(13));
  }

  public function testDestroyAll() {
    $name = new Name();

    $this->assertTrue($name->exists(14));
    $this->assertTrue($name->exists(15));

    $name->destroy_all("last_name='Pepin'");

    $this->assertFalse($name->exists(14));
    $this->assertFalse($name->exists(15));
  }

  public function testCount() {
    $name = new Name();

    $this->assertEquals(2, $name->count(array('conditions'=>"first_name='Jane'")));
    $this->assertEquals(0, $name->count(array('conditions'=>"first_name='Eloise'")));
  }

  public function testCountBySql() {
    $name = new Name();

    $this->assertEquals(2, $name->count_by_sql("SELECT COUNT(*) FROM names WHERE first_name='Jane'"));
    $this->assertEquals(0, $name->count_by_sql("SELECT COUNT(*) FROM names WHERE first_name='Eloise'"));
  }

  public function testIncrementCounter() {
    $doc = new Document();

    $rdbms = $doc->create(array('title'=>'A Relational Model of Data for Large Shared Data Banks', 'author'=>'E. F. Codd'));

    $this->assertEquals(0, $rdbms->retrievals);

    $this->assertEquals(1, $doc->increment_counter('retrievals', $rdbms->id));

    $rdbms = $doc->find($rdbms->id);
    $this->assertEquals(1, $rdbms->retrievals);

    $this->assertEquals(0, $doc->increment_counter('retrievals', 200));
  }

  public function testDecrementCounter() {
    $doc = new Document();

    $rdbms = $doc->create(array('title'=>'A Relational Model of Data for Large Shared Data Banks', 'author'=>'E. F. Codd', 'retrievals'=>10));

    $this->assertEquals(10, $rdbms->retrievals);

    $this->assertEquals(1, $doc->decrement_counter('retrievals', $rdbms->id));

    $rdbms = $doc->find($rdbms->id);
    $this->assertEquals(9, $rdbms->retrievals);

    $this->assertEquals(0, $doc->decrement_counter('retrievals', 200));
  }


  /*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
   * TESTS FOR PUBLIC GENERAL CLASS-LEVEL OPERATIONS
   *++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/


  public function testPrimaryKey() {
    $student = new Student();

    $this->assertEquals('student_id', $student->primary_key());

    $johnQ = $student->find('000-12-3456');
    $this->assertEquals('John',  $johnQ->first_name);
    $this->assertEquals('Q',     $johnQ->middle_initial);
    $this->assertEquals('Adams', $johnQ->last_name);
  }

  public function testTableName() {
    $student = new Student();

    $this->assertEquals('student_records', $student->table_name());
  }

  public function testTableNamePrefix() {
    $obj = new PrefixedObject();

    $this->assertEquals('prfx_', $obj->table_name_prefix());
    $this->assertEquals(5,       $obj->property);
  }

  public function testTableNameSuffix() {
    $obj = new SuffixedObject();

    $this->assertEquals('_sfx', $obj->table_name_suffix());
    $this->assertEquals(10,     $obj->property);
  }

  public function testInheritance() {
    $vehicle = new Vehicle();
    $car   = $vehicle->find(1);
    $truck = $vehicle->find(2);

    $this->assertTrue($car   instanceof Car);
    $this->assertTrue($truck instanceof Truck);

    $bmw = new Car(array('make'=>'BMW', 'model'=>'325i'));
    $this->assertEquals('Car', $bmw->type);
    $bmw->save();
    $bmw = $vehicle->find($bmw->id);
    $this->assertTrue($bmw instanceof Car);
    $this->assertEquals('Car', $bmw->type);
  }

  public function testInheritanceColumn() {
    $pastry = new Pastry();
    $milleFeuille = $pastry->find(1);
    $eclair       = $pastry->find(2);

    $this->assertEquals('kind', $pastry->inheritance_column());

    $this->assertTrue($milleFeuille instanceof Puff);
    $this->assertTrue($eclair       instanceof Choux);
  }

  public function testColumns() {
    $student = new Student();
    $cols = $student->columns();

    $this->assertEquals(5, count($cols));
    $this->assertEquals('student_id',     $cols[0]->name());
    $this->assertEquals('last_name',      $cols[1]->name());
    $this->assertEquals('first_name',     $cols[2]->name());
    $this->assertEquals('middle_initial', $cols[3]->name());
    $this->assertEquals('dob',            $cols[4]->name());
  }

  public function testColumnsHash() {
    $student = new Student();
    $cols = $student->columns_hash();

    $this->assertEquals(5, count($cols));
    $this->assertEquals('student_id',     $cols['student_id']->name());
    $this->assertEquals('first_name',     $cols['first_name']->name());
    $this->assertEquals('last_name',      $cols['last_name']->name());
    $this->assertEquals('middle_initial', $cols['middle_initial']->name());
    $this->assertEquals('dob',            $cols['dob']->name());
  }

  public function testColumnNames() {
    $student = new Student();
    $cols = $student->column_names();

    $this->assertEquals(5, count($cols));
    $this->assertEquals('student_id',     $cols[0]);
    $this->assertEquals('last_name',      $cols[1]);
    $this->assertEquals('first_name',     $cols[2]);
    $this->assertEquals('middle_initial', $cols[3]);
    $this->assertEquals('dob',            $cols[4]);
  }

  public function testContentColumns() {
    $student = new Student();
    $cols = $student->content_columns();

    $this->assertEquals(4, count($cols));
    // student_id ommitted
    $this->assertEquals('last_name',      $cols[0]->name());
    $this->assertEquals('first_name',     $cols[1]->name());
    $this->assertEquals('middle_initial', $cols[2]->name());
    $this->assertEquals('dob',            $cols[3]->name());
  }

  public function testColumnForAttribute() {
    $student = new Student();

    $this->assertEquals('last_name',  $student->column_for_attribute('last_name')->name());
    $this->assertEquals('first_name', $student->column_for_attribute('first_name')->name());
    $this->assertNull($student->column_for_attribute('bogus'));
  }


  /*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
   * TESTS FOR PUBLIC INSTANCE OPERATIONS
   *++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/

  public function testSerialize() {
    $janeA = new Name();
    $janeA = $janeA->find(1);

    $str = serialize($janeA);
    $janeB = unserialize($str);

    $this->assertEquals($janeA->id,         $janeB->id);
    $this->assertEquals($janeA->first_name, $janeB->first_name);
    $this->assertEquals($janeA->last_name,  $janeB->last_name);
  }

  /*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
   * TESTS FOR MASS ASSIGNMENT PROTECTION
   *++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/
   
  public function testAttrProtected() {
    $jmiller = new Base_UnprotectedUser(array('username'=>'jmiller', 'password'=>'none', 'disabled'=>false, 'last_login'=>'2010-06-11 10:57:24'));
    $jwong = new Base_ProtectedUser(array('username'=>'jwong', 'password'=>'none', 'disabled'=>false, 'last_login'=>'2010-06-11 10:57:24'));
    
    $this->assertEquals('jmiller',             $jmiller->username);
    $this->assertEquals('none',                $jmiller->password);
    $this->assertFalse($jmiller->disabled);
    $this->assertEquals('2010-06-11 10:57:24', $jmiller->last_login);

    $this->assertEquals('jwong',               $jwong->username);
    $this->assertEquals('',                    $jwong->password);
    $this->assertFalse($jwong->disabled);
    $this->assertEquals('2010-06-11 10:57:24', $jwong->last_login);
  }

}

?>
