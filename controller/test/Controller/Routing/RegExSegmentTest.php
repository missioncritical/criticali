<?php

class Controller_Routing_RegExSegmentTest extends CriticalI_TestCase {
  
  public function testConstructor() {
    $segment = new Controller_Routing_RegExSegment("/alpha/");
    
    $this->assertEquals("/alpha/", $segment->value());
    $this->assertEquals(null, $segment->next());
  }
  
  public function testMatch() {
    $segment = new Controller_Routing_RegExSegment("/alpha\\d/");
    $params = array();
    
    $match = $segment->match("alpha1", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals(null, $remainder);

    $match = $segment->match("beta/alpha", $params, $remainder);
    $this->assertFalse($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals("beta/alpha", $remainder);

    $match = $segment->match("alpha%31", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals(null, $remainder);

    $match = $segment->match("beta/alpha5/gamma", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals(null, $remainder);
  }
  
  public function testCompare() {
    $segment1 = new Controller_Routing_RegExSegment("/alpha/");
    $segment2 = new Controller_Routing_RegExSegment("/alpha/");
    
    $this->assertEquals(0, $segment1->compare($segment2));
    $this->assertEquals(0, $segment2->compare($segment1));

    $segment2 = new Controller_Routing_RegExSegment("/alpha\\d/");
    $this->assertTrue($segment1->compare($segment2) < 0);
    $this->assertTrue($segment2->compare($segment1) > 0);

    $segment2 = new Controller_Routing_StaticSegment("beta");
    $this->assertTrue($segment1->compare($segment2) < 0);
  }
  
  public function testUrlFor() {
    $segment = new Controller_Routing_RegExSegment("/alpha/");
    $params = array();
    
    $this->assertFalse($segment->url_for($params));
  }
  
}

?>