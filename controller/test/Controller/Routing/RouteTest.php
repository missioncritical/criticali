<?php

class Test1322354290Controller {
}

class Controller_Routing_RouteTest extends CriticalI_TestCase {
  
  public function testPosition() {
    $route = new Controller_Routing_Route(4);
    
    $this->assertEquals(4, $route->position());
    
    $route->set_position(44);
    $this->assertEquals(44, $route->position());
  }
    
  public function testConstraints() {
    $route = new Controller_Routing_Route(1, null, array('method'=>'get'));
    
    $this->assertEquals(array('method'=>'get'), $route->constraints());
    
    $route->set_constraints(array('method'=>'get', 'id'=>"/\\A\\d+\\z/"));
    $this->assertEquals(array('method'=>'get', 'id'=>"/\\A\\d+\\z/"), $route->constraints());
  }
  
  public function testDefaults() {
    $route = new Controller_Routing_Route(1, null, array(), array('controller'=>'alpha'));
    
    $this->assertEquals(array('controller'=>'alpha'), $route->defaults());
    
    $route->set_defaults(array('controller'=>'alpha', 'action'=>'index'));
    $this->assertEquals(array('controller'=>'alpha', 'action'=>'index'), $route->defaults());
  }
  
  public function testName() {
    $route = new Controller_Routing_Route(1, null, array(), array(), 'alpha');
    
    $this->assertEquals('alpha', $route->name());
    
    $route->set_name('beta');
    $this->assertEquals('beta', $route->name());
  }
  
  public function testFirstSegment() {
    $route = new Controller_Routing_Route(1, new Controller_Routing_StaticSegment('alpha'));
    
    $this->assertEquals('alpha', $route->first_segment()->value());
    
    $route->set_first_segment(new Controller_Routing_StaticSegment('beta'));
    $this->assertEquals('beta', $route->first_segment()->value());
  }

  public function testPassesMethodConstraints() {
    $route = new Controller_Routing_Route(1);

    $this->assertTrue($route->passes_method_constraints('get'));
    $this->assertTrue($route->passes_method_constraints('post'));

    $route = new Controller_Routing_Route(1, null, array('method'=>'get'));
    
    $this->assertTrue($route->passes_method_constraints('get'));
    $this->assertTrue($route->passes_method_constraints('GET'));
    $this->assertFalse($route->passes_method_constraints('post'));

    $route = new Controller_Routing_Route(1, null, array('method'=>array('get', 'post')));
    
    $this->assertTrue($route->passes_method_constraints('get'));
    $this->assertTrue($route->passes_method_constraints('GET'));
    $this->assertTrue($route->passes_method_constraints('post'));
    $this->assertFalse($route->passes_method_constraints('put'));
  }
  
  public function testPassesParameterConstraints() {
    $route = new Controller_Routing_Route(1);

    $this->assertTrue($route->passes_parameter_constraints(array()));
    $this->assertTrue($route->passes_parameter_constraints(array('id'=>'100')));

    $route = new Controller_Routing_Route(1, null, array('method'=>'get'));
    
    $this->assertTrue($route->passes_parameter_constraints(array()));
    $this->assertTrue($route->passes_parameter_constraints(array('id'=>'100')));

    $route = new Controller_Routing_Route(1, null, array('id'=>'100'));

    $this->assertFalse($route->passes_parameter_constraints(array()));
    $this->assertTrue($route->passes_parameter_constraints(array('id'=>'100')));
    $this->assertFalse($route->passes_parameter_constraints(array('id'=>'500')));

    $route = new Controller_Routing_Route(1, null, array('id'=>"/\\A\\d+\\z/"));

    $this->assertFalse($route->passes_parameter_constraints(array()));
    $this->assertTrue($route->passes_parameter_constraints(array('id'=>'100')));
    $this->assertTrue($route->passes_parameter_constraints(array('id'=>'500')));
    $this->assertFalse($route->passes_parameter_constraints(array('id'=>'ABC500')));
  }
  
  public function testMatch() {
    $route = new Controller_Routing_Route(1, new Controller_Routing_StaticSegment('alpha'));
    $params = array();
    
    $this->assertFalse($route->match('', 'get', $params));
    $this->assertFalse($route->match('/', 'get', $params));
    $this->assertFalse($route->match('/beta', 'get', $params));
    $this->assertTrue($route->match('/alpha', 'get', $params));
    $this->assertTrue($route->match('/alpha/', 'get', $params));
    $this->assertFalse($route->match('/alpha/beta', 'get', $params));
    $this->assertEquals(array(), $params);

    $route = new Controller_Routing_Route(1, new Controller_Routing_StaticSegment('alpha',
      new Controller_Routing_StaticSegment('beta')));
    
    $this->assertFalse($route->match('', 'get', $params));
    $this->assertFalse($route->match('/', 'get', $params));
    $this->assertFalse($route->match('/beta', 'get', $params));
    $this->assertFalse($route->match('/alpha', 'get', $params));
    $this->assertTrue($route->match('/alpha/beta', 'get', $params));
    $this->assertFalse($route->match('/alpha/beta/gamma', 'get', $params));
    $this->assertEquals(array(), $params);

    $route = new Controller_Routing_Route(1, new Controller_Routing_StaticSegment('alpha',
      new Controller_Routing_StaticSegment('beta')), array('method'=>'get', 'type'=>'html'),
      array('b'=>'Bravo'));
    
    $this->assertFalse($route->match('/alpha', 'get', $params));
    $this->assertFalse($route->match('/alpha/beta', 'get', $params));
    $params = array('type'=>'html');
    $this->assertTrue($route->match('/alpha/beta', 'get', $params));
    $this->assertEquals(array('type'=>'html', 'b'=>'Bravo'), $params);
    $this->assertFalse($route->match('/alpha/beta/gamma', 'get', $params));
    $this->assertFalse($route->match('/alpha/beta', 'post', $params));
    $this->assertEquals(array('type'=>'html', 'b'=>'Bravo'), $params);

    $route = new Controller_Routing_Route(1, new Controller_Routing_ControllerSegment(
      new Controller_Routing_DynamicSegment(':action',
        new Controller_Routing_DynamicSegment(':id'))));
    $params = array();
    
    $this->assertFalse($route->match('/test1322354290', 'get', $params));
    $this->assertEquals(array(), $params);
    $this->assertTrue($route->match('/test1322354290/show/1877', 'get', $params));
    $this->assertEquals(array('controller'=>'test1322354290', 'action'=>'show', 'id'=>'1877'), $params);
  }
  
  public function testUrlFor() {
    $route = new Controller_Routing_Route(null, new Controller_Routing_ControllerSegment(
      new Controller_Routing_DynamicSegment(':action')));
    
    $this->assertEquals('/alpha/list',
      $route->url_for(array('controller'=>'alpha', 'action'=>'list'), 'get'));

    $route = new Controller_Routing_Route(null, new Controller_Routing_DynamicSegment(':action'));
    $this->assertFalse($route->url_for(array('controller'=>'alpha', 'action'=>'list'), 'get'));

    $route = new Controller_Routing_Route(null, new Controller_Routing_ControllerSegment(
      new Controller_Routing_DynamicSegment(':type')));
    $this->assertEquals('/alpha/beta',
      $route->url_for(array('controller'=>'alpha', 'type'=>'beta'), 'get'));
    $this->assertFalse($route->url_for(array('controller'=>'alpha'), 'get'));

    $route = new Controller_Routing_Route(null, new Controller_Routing_ControllerSegment(
      new Controller_Routing_DynamicSegment(':action')), array('method'=>'get', 'format'=>'html'));
    $this->assertEquals('/alpha/list?format=html',
      $route->url_for(array('controller'=>'alpha', 'action'=>'list', 'format'=>'html'), 'get'));
    $this->assertFalse($route->url_for(array('controller'=>'alpha', 'action'=>'list',
      'format'=>'html'), 'post'));
    $this->assertFalse($route->url_for(array('controller'=>'alpha', 'action'=>'list'), 'get'));

    $route = new Controller_Routing_Route(null, new Controller_Routing_ControllerSegment(
      new Controller_Routing_DynamicSegment(':action')), array(), array('format'=>'html'));
    $this->assertEquals('/alpha/list',
      $route->url_for(array('controller'=>'alpha', 'action'=>'list', 'format'=>'html'), 'get'));
    
    try {
      $route->url_for(array('action'=>'index'), 'get');
      $this->fail("Accepted a call to url_for with no controller specified");
    } catch (Exception $e) {
      // expected
    }
    
    $route = new Controller_Routing_Route(null, new Controller_Routing_StaticSegment(''),
      array(), array('controller'=>'home'));
    $this->assertEquals('/', $route->url_for(array('controller'=>'home'), 'get'));
  }
  
}

?>