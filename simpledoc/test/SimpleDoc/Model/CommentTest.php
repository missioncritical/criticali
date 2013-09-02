<?php

class SimpleDoc_Model_CommentTest extends CriticalI_TestCase {
  
  public function testStripDelimiters() {
    $comment = new SimpleDoc_Model_Comment(new PHPParser_Comment(<<<COMMENT
/**
     * Line 1
     * Line 2
     */
COMMENT
    ));
    
    $this->assertEquals("Line 1\nLine 2\n", $comment->text);

    $comment = new SimpleDoc_Model_Comment(new PHPParser_Comment(<<<COMMENT
/**
      Line 1
      Line 2
      */
COMMENT
    ));
    
    $this->assertEquals("Line 1\nLine 2\n", $comment->text);

    $comment = new SimpleDoc_Model_Comment(new PHPParser_Comment(<<<COMMENT
/**
      Line 1
      Line 2 */
COMMENT
    ));
    
    $this->assertEquals("Line 1\nLine 2", $comment->text);
    
    $comment = new SimpleDoc_Model_Comment(new PHPParser_Comment("/** Line 1 */"));
    $this->assertEquals("Line 1", $comment->text);

    $comment = new SimpleDoc_Model_Comment(new PHPParser_Comment(<<<COMMENT
// Line 1
      // Line 2
      // Line 3
COMMENT
    ));
    
    $this->assertEquals("Line 1\nLine 2\nLine 3", $comment->text);

    $comment = new SimpleDoc_Model_Comment(new PHPParser_Comment(<<<COMMENT
# Line 1
      # Line 2
      # Line 3
COMMENT
    ));
    
    $this->assertEquals("Line 1\nLine 2\nLine 3", $comment->text);
  }
  
}

?>