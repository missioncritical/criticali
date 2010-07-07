<?php

class CriticalI_GlobberTest extends CriticalI_TestCase {
  public function setUp() {
    if (!mkdir('globber/foo', 0777, true)) throw new Exception("Couldn't create directory");
    if (!mkdir('globber/bar', 0777, true)) throw new Exception("Couldn't create directory");
    
    if (file_put_contents('globber/foo/foo.txt', '1') == false) throw new Exception("Couldn't create file");
    if (file_put_contents('globber/foo/foo2.txt', '1') == false) throw new Exception("Couldn't create file");
    if (file_put_contents('globber/foo/README', '1') == false) throw new Exception("Couldn't create file");
    if (file_put_contents('globber/bar/bar.txt', '1') == false) throw new Exception("Couldn't create file");
    if (file_put_contents('globber/bar/bar2.txt', '1') == false) throw new Exception("Couldn't create file");
    if (file_put_contents('globber/bar/README', '1') == false) throw new Exception("Couldn't create file");
  }
  
  public function tearDown() {
    unlink('globber/bar/README');
    unlink('globber/bar/bar2.txt');
    unlink('globber/bar/bar.txt');
    unlink('globber/foo/README');
    unlink('globber/foo/foo2.txt');
    unlink('globber/foo/foo.txt');
    rmdir('globber/bar');
    rmdir('globber/foo');
    rmdir('globber');
  }
  
  public function testMatch() {
    // single pattern
    $matches = CriticalI_Globber::match('globber', 'foo/*.txt');
    sort($matches);
    $this->assertEquals($matches, array('globber/foo/foo.txt', 'globber/foo/foo2.txt'));

    // no match
    $matches = CriticalI_Globber::match('globber', 'foo/*.tar');
    $this->assertEquals($matches, array());

    // no origin
    $matches = CriticalI_Globber::match('', 'globber/foo/*.txt');
    sort($matches);
    $this->assertEquals($matches, array('globber/foo/foo.txt', 'globber/foo/foo2.txt'));

    // multiple patterns
    $matches = CriticalI_Globber::match('globber', 'foo/*.txt,bar/README');
    sort($matches);
    $this->assertEquals($matches, array('globber/bar/README', 'globber/foo/foo.txt', 'globber/foo/foo2.txt'));

    // multiple patterns with whitespace
    $matches = CriticalI_Globber::match('globber', "foo/*.txt,\n     bar/README");
    sort($matches);
    $this->assertEquals($matches, array('globber/bar/README', 'globber/foo/foo.txt', 'globber/foo/foo2.txt'));

    // multiple patterns, only one match
    $matches = CriticalI_Globber::match('globber', 'foo/*.tar,bar/README');
    sort($matches);
    $this->assertEquals($matches, array('globber/bar/README'));
  }
}

?>