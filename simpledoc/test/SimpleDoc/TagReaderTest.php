<?php

class SimpleDoc_TagReaderTest extends CriticalI_TestCase {
  
  public function setUp() {
    SimpleDoc_ErrorManager::reset();
  }
  
  public function testParseTags() {
    $comment = <<<COMMENT
Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam
nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat.

Volutpat ut wisi enim ad minim veniam, quis nostrud exerci tation
ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat.
Duis autem vel eum iriure dolor in hendrerit.

@api
@filesource
@author Author Name <some.address@example.com>
@category Category text
@copyright Copyright text
@license License text
@package Package text
@subpackage Subpackage text
@version Version text
@deprecated Because it's better that way
@since 1.0 But who's counting?
@ignore
@internal
@todo Fix me!
@method int someFunc() A description
@method someFunc2() A description
@method int someFunc3(\$param1) A description
@method int someFunc4(string \$param1) A description
@method int someFunc5(string \$param1, int \$param2) A description
@param \$p1 Short description
@param string \$p2 Claritas est etiam processus dynamicus, qui sequitur
mutationem consuetudium lectorum. Mirum est notare quam littera gothica,
quam nunc putamus.
@param array &\$p3 Output Param
@property-read \$prop1 A description
@property-write int \$prop2 A description
@property string \$prop3 A description
@return mixed Duis autem vel eum iriure dolor in hendrerit in vulputate
velit esse molestie consequat, vel illum dolore.
@throws ExampleException
@throws AnotherExampleException With a description
@var int A var of type int
@var string \$foo A named variable of type string

Typi non habent claritatem insitam; est usus legentis in iis qui facit
eorum claritatem. Investigationes demonstraverunt lectores
COMMENT
    ;
    
    $tags = array();
    $result = SimpleDoc_TagReader::parse_tags($comment, $tags);
      
    $this->assertEquals(array(), SimpleDoc_ErrorManager::events());
    
    $this->assertEquals(<<<COMMENT
Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam
nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat.

Volutpat ut wisi enim ad minim veniam, quis nostrud exerci tation
ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat.
Duis autem vel eum iriure dolor in hendrerit.
































Typi non habent claritatem insitam; est usus legentis in iis qui facit
eorum claritatem. Investigationes demonstraverunt lectores
COMMENT
      , $result);
    
    $this->assertEquals(array(
      'api'=>true,
      'filesource'=>true,
      'author'=>array('name'=>'Author Name', 'email'=>'some.address@example.com'),
      'category'=>'Category text',
      'copyright'=>'Copyright text',
      'license'=>'License text',
      'package'=>'Package text',
      'subpackage'=>'Subpackage text',
      'version'=>'Version text',
      'deprecated'=>array('version'=>null, 'description'=>'Because it\'s better that way'),
      'since'=>array('version'=>'1.0', 'description'=>'But who\'s counting?'),
      'nodoc'=>true,
      'internal'=>true,
      'todo'=>'Fix me!',
      'methods'=>array(
        array('name'=>'someFunc', 'type'=>'int', 'params'=>array(), 'description'=>'A description'),
        array('name'=>'someFunc2', 'type'=>null, 'params'=>array(), 'description'=>'A description'),
        array('name'=>'someFunc3', 'type'=>'int', 'params'=>array(array('type'=>'', 'name'=>'$param1')), 'description'=>'A description'),
        array('name'=>'someFunc4', 'type'=>'int', 'params'=>array(array('type'=>'string', 'name'=>'$param1')), 'description'=>'A description'),
        array('name'=>'someFunc5', 'type'=>'int', 'params'=>array(array('type'=>'string', 'name'=>'$param1'),array('type'=>'int','name'=>'$param2')), 'description'=>'A description'),
        ),
        'params'=>array(
          array('name'=>'$p1', 'type'=>null, 'description'=>'Short description'),
          array('name'=>'$p2', 'type'=>'string', 'description'=>"Claritas est etiam processus dynamicus, qui sequitur\nmutationem consuetudium lectorum. Mirum est notare quam littera gothica,\nquam nunc putamus."),
          array('name'=>'&$p3', 'type'=>'array', 'description'=>'Output Param')
        ),
        'properties'=>array(
          array('name'=>'$prop1', 'type'=>null, 'description'=>'A description', 'rw'=>'r'),
          array('name'=>'$prop2', 'type'=>'int', 'description'=>'A description', 'rw'=>'w'),
          array('name'=>'$prop3', 'type'=>'string', 'description'=>'A description', 'rw'=>'rw')
        ),
        'return'=>array('type'=>'mixed', 'description'=>"Duis autem vel eum iriure dolor in hendrerit in vulputate\nvelit esse molestie consequat, vel illum dolore."),
        'throws'=>array(
          array('type'=>'ExampleException', 'description'=>null),
          array('type'=>'AnotherExampleException', 'description'=>'With a description')
        ),
        'vars'=>array(
          array('name'=>null, 'type'=>'int', 'description'=>'A var of type int'),
          array('name'=>'$foo', 'type'=>'string', 'description'=>'A named variable of type string')
        )
      ), $tags);
  }
  
}
