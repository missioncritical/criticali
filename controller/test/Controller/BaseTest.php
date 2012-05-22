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

class Controller_BaseTest_TestRandomController extends Controller_Base {
  public function __construct() {
    $this->caches_action('random_cached');
  }
  
  public function random() {
    print mt_rand();
    $this->set_rendered(true);
  }
  
  public function random_cached() {
    $this->random();
  }
  
  protected function cache_options() { return array('engine'=>'memory'); }
}

class Controller_BaseTest_SimpleModel {
}

class Controller_BaseTest_SimpleController extends Controller_Base {
  
  public function model_instance() {
    return $this->model('Controller_BaseTest_SimpleModel');
  }
  
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
  
  public function testCache() {
    $pkgs = CriticalI_Package_List::get();
    if (!isset($pkgs['cache']))
      $this->markTestSkipped("The cache package is not installed");
    
    CriticalI_Package_List::add_package_to_autoloader('cache', '0.1.0');
    Support_Resources::register_cache(new Cache_Provider(), 'php', true);

    $controller = new Controller_BaseTest_TestRandomController();
    
    $val = $this->run_request($controller, 'random_cached');
    
    $this->assertNotEquals($val, $this->run_request($controller, 'random'));
    $this->assertEquals($val, $this->run_request($controller, 'random_cached'));
    
    $controller->expire_action(array('action'=>'random_cached'));
    $this->assertNotEquals($val, $this->run_request($controller, 'random_cached'));
  }
  
  public function testModel() {
    $controller = new Controller_BaseTest_SimpleController();
    
    $this->assertTrue(($controller->model_instance() instanceof Controller_BaseTest_SimpleModel));
  }
  
  protected function run_request($controller, $action, $params = array()) {
    $oldRequest = $_REQUEST;
    $_REQUEST = $params;
    $_REQUEST['action'] = $action;
    
    ob_start();
    $controller->handle_request();
    
    $_REQUEST = $oldRequest;
    return ob_get_clean();
  }
  
}

?>