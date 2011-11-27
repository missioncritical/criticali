<?php

class Test1322341897Controller {
}

class Controller_Routing_ControllerSegmentTest_AController {
}

class Controller_Routing_ControllerSegmentTest extends CriticalI_TestCase {
  
  public function testMatch() {
    $segment = new Controller_Routing_ControllerSegment();
    $params = array();
    
    $match = $segment->match("test1322341897", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array('controller'=>'test1322341897'), $params);
    $this->assertEquals(null, $remainder);

    $match = $segment->match("test1322341897/alpha", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array('controller'=>'test1322341897'), $params);
    $this->assertEquals('alpha', $remainder);

    $params = array();
    $match = $segment->match("alpha/beta", $params, $remainder);
    $this->assertFalse($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals("alpha/beta", $remainder);

    $match = $segment->match("/controller/routing/controller_segment_test/a/alpha",
      $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array('controller'=>'controller/routing/controller_segment_test/a'), $params);
    $this->assertEquals('alpha', $remainder);

    $match = $segment->match("/controller/routing/controller-segment-test/a/alpha",
      $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array('controller'=>'controller/routing/controller_segment_test/a'), $params);
    $this->assertEquals('alpha', $remainder);
  }
  
  public function testCompare() {
    $segment1 = new Controller_Routing_ControllerSegment();
    $segment2 = new Controller_Routing_ControllerSegment();
    
    $this->assertEquals(0, $segment1->compare($segment2));
    $this->assertEquals(0, $segment2->compare($segment1));

    $segment2 = new Controller_Routing_StaticSegment("controller");
    $this->assertTrue($segment1->compare($segment2) < 0);
  }
  
  public function testUrlFor() {
    $segment = new Controller_Routing_ControllerSegment();
    
    $params = array('action'=>'bravo');
    $this->assertFalse($segment->url_for($params));
    $this->assertEquals(array('action'=>'bravo'), $params);

    $params = array('controller'=>'alpha', 'action'=>'bravo');
    $this->assertEquals('alpha', $segment->url_for($params));
    $this->assertEquals(array('action'=>'bravo'), $params);
  }
  
}

?>