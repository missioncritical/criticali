<?php

class STI_Pastry extends ActiveRecord_Base {
  protected function init_class() {
    $this->set_table_name('pastries');
    $this->set_inheritance_column('kind');
    $this->validates_uniqueness_of('name');
  }
  
  public function expose_meta_info() { return $this->get_meta_info(); }
}

class STI_Choux extends STI_Pastry {
}

class STI_Puff extends STI_Pastry {
}


/**
 * Test inheritance and meta information issues specific to Single Table
 * Inheritance (STI)
 */
class ActiveRecord_Base_STIInheritanceTest extends CriticalI_DBTestCase {
  
  public function testValidationDuplication() {
    $choux = new STI_Choux();
    $this->assertEquals(0, count($choux->expose_meta_info()->validations));
    $this->assertEquals(1, count($choux->expose_meta_info()->parent->validations));
  }
  
  public function testValidationScope() {
    $choux = new STI_Choux();
    $choux->name = 'Mille-Feuille';
    
    $this->assertFalse($choux->is_valid());
  }
  
}

?>
