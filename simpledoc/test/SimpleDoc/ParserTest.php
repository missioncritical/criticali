<?php

class SimpleDoc_ParserTest extends CriticalI_TestCase {
  
  public function testParser() {
    $parser = new PHPParser_Parser(new PHPParser_Lexer());
    return;
    
    $html = " some text here ";
    $php = "<?php\n/** another\n * doc comment\n */ /** doc comment */ class Foo { public \$m = 'foo'; public function x() { } } class Bar { var \$m; function x() { } }";
    $phpDef = "<?php\n\ndefine('FOO', 'a classic example');\n\$bar = 'another one';\n\$x;\n\n?>";
    $phpDef = "<?php\n\nclass A {\n /** some documentation */ \npublic \$myprop = '20'; } ?>";
    $badPhp = "<?php class Bar(; ?>";
    
    print_r($parser->parse($html));
    print_r($parser->parse($php));
    print_r($parser->parse($phpDef));
    try {
      print_r($parser->parse($badPhp));
      $this->fail("Invalid PHP parsed");
    } catch (PHPParser_Error $e) {
      // expected
    }
  }
  
}

?>