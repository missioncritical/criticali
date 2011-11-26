<?php

class Controller_Routing_StaticSegmentTest extends CriticalI_TestCase {
  
  public function testConstructor() {
    $segment = new Controller_Routing_StaticSegment("alpha");
    
    $this->assertEquals("alpha", $segment->value());
    $this->assertEquals(null, $segment->next());
    
    $segment = new Controller_Routing_StaticSegment("alpha",
      new Controller_Routing_StaticSegment("beta"));
    $this->assertEquals("alpha", $segment->value());
    $this->assertEquals("beta", $segment->next()->value());
    $this->assertEquals(null, $segment->next()->next());
  }
  
  public function testSetNext() {
    $segment = new Controller_Routing_StaticSegment("alpha");
    $segment->set_next(new Controller_Routing_StaticSegment("beta"));
    
    $this->assertEquals("alpha", $segment->value());
    $this->assertEquals("beta", $segment->next()->value());
    $this->assertEquals(null, $segment->next()->next());
  }
  
  public function testMatch() {
    $segment = new Controller_Routing_StaticSegment("alpha");
    $params = array();
    
    $match = $segment->match("alpha", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals(null, $remainder);

    $match = $segment->match("beta/alpha", $params, $remainder);
    $this->assertFalse($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals("beta/alpha", $remainder);

    $match = $segment->match("alpha/beta", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals("beta", $remainder);

    $match = $segment->match("/alpha/beta", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals("beta", $remainder);

    $match = $segment->match("//alpha/beta", $params, $remainder);
    $this->assertTrue($match);
    $this->assertEquals(array(), $params);
    $this->assertEquals("beta", $remainder);
  }
  
  public function testCompare() {
    $segment1 = new Controller_Routing_StaticSegment("alpha");
    $segment2 = new Controller_Routing_StaticSegment("alpha");
    
    $this->assertEquals(0, $segment1->compare($segment2));
    $this->assertEquals(0, $segment2->compare($segment1));

    $segment2 = new Controller_Routing_StaticSegment("beta");
    $this->assertTrue($segment1->compare($segment2) < 0);
    $this->assertTrue($segment2->compare($segment1) > 0);

    $segment2 = new Controller_Routing_DynamicSegment("beta");
    $this->assertTrue($segment1->compare($segment2) > 0);
  }
  
}

?>