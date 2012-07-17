<?php

class Form_Object_ModelCollectionTest_Invoice extends ActiveRecord_Base {
  protected function init_class() {
    $this->set_table_name('invoices');
    $this->has_many('invoice_lines', array('class_name'=>'Form_Object_ModelCollectionTest_InvoiceLine', 'foreign_key'=>'invoice_id'));
  }
}

class Form_Object_ModelCollectionTest_InvoiceLine extends ActiveRecord_Base {
  protected function init_class() {
    $this->set_table_name('invoice_lines');
    $this->belongs_to('invoice', array('class_name'=>'Form_Object_ModelCollectionTest_Invoice'));
  }
}

class Form_Object_ModelCollectionTest extends CriticalI_DBTestCase {
  
  public function testAssignRequest() {
    $request = array('id'=>1,
      'invoice'=>array(
        'invoice_number'=>'12345',
        'invoice_lines'=>array(
          '_4'=>array('id'=>4, 'description'=>'Line 1', 'amount'=>'10.01'),
          '_1'=>array('id'=>1, 'description'=>'Line 2', 'amount'=>'10.02'),
          '_2'=>array('id'=>2, 'description'=>'Line 3', 'amount'=>'10.03'),
          '__1341874977'=>array('description'=>'Line 4', 'amount'=>'10.04')
        )
      )
    );
    
    $invoice = new Form_Object_ModelCollectionTest_Invoice();
    $invoice = $invoice->find($request['id']);
    
    $this->assertEquals('54321', $invoice->invoice_number);
    $this->assertEquals(4, $invoice->invoice_lines->count());
    $this->assertEquals(array('id'=>1, 'invoice_id'=>1, 'description'=>'empty', 'amount'=>0),
      $invoice->invoice_lines[0]->attributes);
    $this->assertEquals(array('id'=>2, 'invoice_id'=>1, 'description'=>'empty', 'amount'=>0),
      $invoice->invoice_lines[1]->attributes);
    $this->assertEquals(array('id'=>3, 'invoice_id'=>1, 'description'=>'empty', 'amount'=>0),
      $invoice->invoice_lines[2]->attributes);
    $this->assertEquals(array('id'=>4, 'invoice_id'=>1, 'description'=>'empty', 'amount'=>0),
      $invoice->invoice_lines[3]->attributes);
    
    $form = new Form_Default('Form_Object_ModelCollectionTest_InvoiceForm');
    $form->include_model('invoice', array('class_name'=>'Form_Object_ModelCollectionTest_Invoice'))
      ->include_association('invoice_lines');

    $form->assign_request($request, $invoice);

    $this->assertEquals('12345', $invoice->invoice_number);
    $this->assertEquals(4, $invoice->invoice_lines->count());
    $this->assertEquals(array('id'=>4, 'invoice_id'=>1, 'description'=>'Line 1', 'amount'=>'10.01'),
      $invoice->invoice_lines[0]->attributes);
    $this->assertEquals(array('id'=>1, 'invoice_id'=>1, 'description'=>'Line 2', 'amount'=>'10.02'),
      $invoice->invoice_lines[1]->attributes);
    $this->assertEquals(array('id'=>2, 'invoice_id'=>1, 'description'=>'Line 3', 'amount'=>'10.03'),
      $invoice->invoice_lines[2]->attributes);
    $this->assertEquals(array('id'=>null, 'invoice_id'=>1, 'description'=>'Line 4', 'amount'=>'10.04'),
      $invoice->invoice_lines[3]->attributes);
  }
  
}
