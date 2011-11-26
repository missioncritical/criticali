<?php

class Controller_Routing_DynamicSegmentTest extends CriticalI_TestCase {
  
  public function testConstructor() {
    $segment = new Controller_Routing_DynamicSegment("alpha");
    
    $this->assertEquals("alpha", $segment->value());
    $this->assertEquals("/\\A\\Qalpha\\E\\z/", $segment->regex());
    $this->assertEquals(null, $segment->next());
    
    $segment = new Controller_Routing_DynamicSegment("alpha",
      new Controller_Routing_StaticSegment("beta"));
    $this->assertEquals("alpha", $segment->value());
    $this->assertEquals("/\\A\\Qalpha\\E\\z/", $segment->regex());
    $this->assertEquals("beta", $segment->next()->value());
    $this->assertEquals(null, $segment->next()->next());
  }
  
  public function testMatch() {
    $segment = new Controller_Routing_DynamicSegment("alpha");
    $params = array();
    
    $match = $segment->match("alpha", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals(null, $remainder);

    $match = $segment->match("beta/alpha", $params, $remainder);
    $this->assertFalse($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals("beta/alpha", $remainder);

    $segment = new Controller_Routing_DynamicSegment(":id");
    $match = $segment->match("alpha/beta", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array('id'=>'alpha'), $params);
    $this->assertEquals('beta', $remainder);

    $match = $segment->match("alpha%20beta/gamma", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array('id'=>'alpha beta'), $params);
    $this->assertEquals('gamma', $remainder);

    $segment = new Controller_Routing_DynamicSegment("alpha:id");
    $match = $segment->match("alphabeta/gamma", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array('id'=>'beta'), $params);
    $this->assertEquals('gamma', $remainder);

    $segment = new Controller_Routing_DynamicSegment("alpha :id");
    $match = $segment->match("alpha%20beta/gamma", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array('id'=>'beta'), $params);
    $this->assertEquals('gamma', $remainder);

    $segment = new Controller_Routing_DynamicSegment(":id.:format");
    $match = $segment->match("alpha.xml", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array('id'=>'alpha', 'format'=>'xml'), $params);
    $this->assertEquals(null, $remainder);
  }
  
  public function testCompare() {
    $segment1 = new Controller_Routing_DynamicSegment("alpha");
    $segment2 = new Controller_Routing_DynamicSegment("alpha");
    
    $this->assertEquals(0, $segment1->compare($segment2));
    $this->assertEquals(0, $segment2->compare($segment1));

    $segment1 = new Controller_Routing_DynamicSegment(":alpha");
    $segment2 = new Controller_Routing_DynamicSegment(":alpha");
    
    $this->assertEquals(0, $segment1->compare($segment2));
    $this->assertEquals(0, $segment2->compare($segment1));

    $segment2 = new Controller_Routing_DynamicSegment(":beta");
    
    $this->assertEquals(0, $segment1->compare($segment2));
    $this->assertEquals(0, $segment2->compare($segment1));

    $segment1 = new Controller_Routing_DynamicSegment("alpha.:id");
    $segment2 = new Controller_Routing_DynamicSegment("beta.:id");

    $this->assertTrue($segment1->compare($segment2) < 0);
    $this->assertTrue($segment2->compare($segment1) > 0);

    $segment1 = new Controller_Routing_DynamicSegment("alpha");
    $segment2 = new Controller_Routing_StaticSegment("alpha");
    $this->assertTrue($segment1->compare($segment2) < 0);
  }
  
}

?>