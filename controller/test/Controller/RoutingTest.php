<?php

class Controller_RoutingTester extends Controller_Routing {
  public function normalize_proxy($url) { return $this->normalize($url); }
  protected function add_configured_routes() { /* no-op */ }
}

class Controller_RoutingTest extends CriticalI_TestCase {
  
  public function testNormalize() {
    $rtg = new Controller_RoutingTester();
    $this->assertEquals('/foo/bar', $rtg->normalize_proxy('http://example.com/foo/bar'));
    $this->assertEquals('/foo/bar', $rtg->normalize_proxy('http://example.com/foo/bar#alpha'));
    $this->assertEquals('/foo/bar', $rtg->normalize_proxy('http://example.com/foo/bar?beta=gamma&delta=epsilon#alpha'));
    $this->assertEquals('/foo/bar', $rtg->normalize_proxy('http://example.com/foo/bar/?beta=gamma&delta=epsilon#alpha'));
    $this->assertEquals('', $rtg->normalize_proxy('http://example.com/'));
    $this->assertEquals('', $rtg->normalize_proxy('http://example.com'));
    $this->assertEquals('/bar', $rtg->normalize_proxy('http://example.com/foo/../bar'));
    $this->assertEquals('/bar', $rtg->normalize_proxy('http://example.com/foo/../../bar/'));
    $this->assertEquals('/foo/bar', $rtg->normalize_proxy('http://example.com/foo/./bar'));
    $this->assertEquals('/foo/bar', $rtg->normalize_proxy('http://example.com/foo/././bar/.'));
  }
  
  public function testUrlFor() {
    $route = new Controller_RoutingTester();

    $builder = new Controller_Routing_Builder($route);
    $builder->root(array('controller'=>'home'));
    $builder->match('/login', array('controller'=>'session', 'action'=>'login'));
    $builder->match('/logout', array('controller'=>'session', 'action'=>'logout'));
    $builder->match('/reset_password/:token', array('controller'=>'session', 'action'=>'reset_password'));
    $builder->match('/:controller/:action/:id');
    $builder->match('/:controller/:action');
    $builder->match('/:controller');
    
    try {
      $route->url_for(array());
      $this->fail("Built a URL without a controller");
    } catch (Exception $e) {
      // expected
    }
    
    $this->assertEquals('/', $route->url_for(array('controller'=>'home')));
    $this->assertEquals('/login', $route->url_for(array('controller'=>'session', 'action'=>'login')));
    $this->assertEquals('/logout', $route->url_for(array('controller'=>'session', 'action'=>'logout')));
    $this->assertEquals('/reset_password/QWERTYU', $route->url_for(array('controller'=>'session',
      'action'=>'reset_password', 'token'=>'QWERTYU')));
    $this->assertEquals('/alpha/beta/5', $route->url_for(array('controller'=>'alpha', 'action'=>'beta',
     'id'=>'5')));
    $this->assertEquals('/alpha/beta', $route->url_for(array('controller'=>'alpha', 'action'=>'beta')));
    $this->assertEquals('/alpha/beta?page=2', $route->url_for(array('controller'=>'alpha',
      'action'=>'beta', 'page'=>2)));
    $this->assertEquals('/alpha/index', $route->url_for(array('controller'=>'alpha',
      'action'=>'index')));
    $this->assertEquals('/alpha', $route->url_for(array('controller'=>'alpha')));


    $route = new Controller_RoutingTester();

    $builder = new Controller_Routing_Builder($route);
    $builder->root(array('controller'=>'home'));
    $builder->match('/login', array('controller'=>'session', 'action'=>'login'));
    $builder->match('/logout', array('controller'=>'session', 'action'=>'logout'));
    $builder->match('/reset_password/:token', array('controller'=>'session', 'action'=>'reset_password'));
    
    try {
      $route->url_for(array('controller'=>'alpha', 'action'=>'list'));
      $this->fail("Built a URL with no matching route");
    } catch (Exception $e) {
      // expected
    }
  }
  
}

?>