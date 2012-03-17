<?php

class Support_ArrayHelperTest extends CriticalI_TestCase {

  public function testIsAssociative() {
    $blank = array();
    $assoc = array('color'=>'red', 'shape'=>'square');
    $nonassoc = array('red', 'orange', 'yellow', 'green', 'blue');
    $hardassoc = array('red', 'orange', 'color'=>'yellow', 'green');
    $hardassoc2 = array('red', 'orange', 'color'=>'yellow');
    
    $this->assertFalse(Support_ArrayHelper::is_associative($blank));
    $this->assertTrue(Support_ArrayHelper::is_associative($assoc));
    $this->assertFalse(Support_ArrayHelper::is_associative($nonassoc));
    $this->assertFalse(Support_ArrayHelper::is_associative($hardassoc));
    $this->assertFalse(Support_ArrayHelper::is_associative($hardassoc2));

    $this->assertFalse(Support_ArrayHelper::is_associative($blank, false));
    $this->assertTrue(Support_ArrayHelper::is_associative($assoc, false));
    $this->assertFalse(Support_ArrayHelper::is_associative($nonassoc, false));
    $this->assertTrue(Support_ArrayHelper::is_associative($hardassoc, false));
    $this->assertTrue(Support_ArrayHelper::is_associative($hardassoc2, false));
  }
  
  public function testMergeSorted() {
    $a = array(1, 3, 5, 6, 7, 9);
    $b = array(2, 3, 4, 8);
    $blank = array();
    
    $this->assertEquals(Support_ArrayHelper::merge_sorted($a, $b), array(1, 2, 3, 4, 5, 6, 7, 8, 9));
    $this->assertEquals(Support_ArrayHelper::merge_sorted($b, $a), array(1, 2, 3, 4, 5, 6, 7, 8, 9));
    $this->assertEquals(Support_ArrayHelper::merge_sorted($a, $blank), $a);
    $this->assertEquals(Support_ArrayHelper::merge_sorted($blank, $a), $a);
  }
  
  public function testExcludeSorted() {
    $a = array(1, 2, 3, 4, 5);
    $b = array(2, 4);
    $this->assertEquals(Support_ArrayHelper::exclude_sorted($a, $b), array(1, 3, 5));
    
    $a = array(1, 2, 3, 4, 5);
    $b = array(1, 3, 5);
    $this->assertEquals(Support_ArrayHelper::exclude_sorted($a, $b), array(2, 4));

    $a = array(1, 2, 3, 4, 5);
    $b = array(1, 3, 5, 7);
    $this->assertEquals(Support_ArrayHelper::exclude_sorted($a, $b), array(2, 4));

    $a = array(1, 2, 3, 4, 5);
    $b = array(-1, 3, 5);
    $this->assertEquals(Support_ArrayHelper::exclude_sorted($a, $b), array(1, 2, 4));
    
    $blank = array();
    $this->assertEquals(Support_ArrayHelper::exclude_sorted($a, $blank), $a);
    $this->assertEquals(Support_ArrayHelper::exclude_sorted($blank, $a), $blank);
  }
  
  public function testKexcludeSorted() {
    $a = array('a'=>'A', 'b'=>'B', 'c'=>'C', 'd'=>'D', 'e'=>'E');
    $b = array('b', 'd');
    $this->assertEquals(array('a'=>'A', 'c'=>'C', 'e'=>'E'), Support_ArrayHelper::kexclude_sorted($a, $b));

    $a = array('a'=>'A', 'b'=>'B', 'c'=>'C', 'd'=>'D', 'e'=>'E');
    $b = array('a', 'c', 'e');
    $this->assertEquals(array('b'=>'B', 'd'=>'D'), Support_ArrayHelper::kexclude_sorted($a, $b));

    $a = array('a'=>'A', 'b'=>'B', 'c'=>'C', 'd'=>'D', 'e'=>'E');
    $b = array('a', 'c', 'e', 'g');
    $this->assertEquals(array('b'=>'B', 'd'=>'D'), Support_ArrayHelper::kexclude_sorted($a, $b));

    $a = array('a'=>'A', 'b'=>'B', 'c'=>'C', 'd'=>'D', 'e'=>'E');
    $b = array(' ', 'c', 'e');
    $this->assertEquals(array('a'=>'A', 'b'=>'B', 'd'=>'D'), Support_ArrayHelper::kexclude_sorted($a, $b));
  }
  
}


?>