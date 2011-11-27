<?php

class EmptyTestController extends Controller_Base {
  public function before_filter_value() { return $this->fire_event('before_filter', true); }
  public function after_filter_value() { return $this->fire_event('after_filter'); }
  public function false_check() { return false; }
  public function true_check() { return true; }
  public function fatal_check() { throw new Exception("Filter called after chain stopped."); }
}

class OKTestController extends EmptyTestController {
  public function __construct() {
    $this->before_filter('true_check');

    $this->after_filter('true_check');
  }
}

class RejectTestController extends EmptyTestController {
  public function __construct() {
    $this->before_filter('true_check');
    $this->before_filter('false_check');
    $this->before_filter('fatal_check');

    $this->after_filter('true_check');
    $this->after_filter('false_check');
    $this->after_filter('true_check');
  }
}

class OnlyTestController extends EmptyTestController {
  public function __construct($action) {
    $this->action = $action;
    $this->before_filter('true_check');
    $this->before_filter('false_check', array('only'=>'failure'));
  }
}

class ExceptTestController extends EmptyTestController {
  public function __construct($action) {
    $this->action = $action;
    $this->before_filter('true_check');
    $this->before_filter('false_check', array('except'=>'success'));
  }
}

class Controller_BaseTest_TestAController extends Controller_Base {
}


class Controller_BaseTest extends CriticalI_TestCase {
  
  public function testBeforeFilter() {
    $empty  = new EmptyTestController();
    $ok     = new OKTestController();
    $reject = new RejectTestController();
    
    $this->assertTrue($empty->before_filter_value());
    $this->assertTrue($ok->before_filter_value());
    $this->assertFalse($reject->before_filter_value());
  }
  
  public function testAfterFilter() {
    $empty  = new EmptyTestController();
    $ok     = new OKTestController();
    $reject = new RejectTestController();
    
    $this->assertTrue($empty->after_filter_value());
    $this->assertTrue($ok->after_filter_value());
    $this->assertFalse($reject->after_filter_value());
  }

  public function testFilterOptions() {
    $onlySuccess     = new OnlyTestController('success');
    $onlyFailure     = new OnlyTestController('failure');
    $exceptSuccess   = new ExceptTestController('success');
    $exceptFailure   = new ExceptTestController('failure');

    $this->assertTrue($onlySuccess->before_filter_value());
    $this->assertFalse($onlyFailure->before_filter_value());
    $this->assertTrue($exceptSuccess->before_filter_value());
    $this->assertFalse($exceptFailure->before_filter_value());
  }
  
  public function testControllerName() {
    $empty = new EmptyTestController();
    $testA = new Controller_BaseTest_TestAController();
    
    $this->assertEquals('empty_test', $empty->controller_name());
    $this->assertEquals('controller/base_test/test_a', $testA->controller_name());
  }
  
}

?>