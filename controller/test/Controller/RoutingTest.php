<?php

class Controller_RoutingTester extends Controller_Routing {
  public function normalize_proxy($url) { return $this->normalize($url); }
}

class Controller_RoutingTest extends Vulture_TestCase {
  
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
  
}

?>