<?php

class CriticalI_ClassUtilsTest extends CriticalI_TestCase {
  
  public function testClassName() {
    $this->assertEquals('CriticalI', CriticalI_ClassUtils::class_name('CriticalI.php'));
    $this->assertEquals('CriticalI', CriticalI_ClassUtils::class_name('lib/CriticalI.php'));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name('lib/CriticalI/Test.php'));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name('lib/CriticalI/Test.php', ''));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name('lib/CriticalI/Test.php', 'bogus'));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name('lib/CriticalI/Test'));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name('lib/CriticalI/Test.inc'));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name('/usr/local/criticali/Core/lib/CriticalI/Test.php', '/usr/local/criticali/Core'));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name('/usr/local/criticali/Core/lib/CriticalI/Test.php', '/usr/local/criticali/Core/'));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name("\\usr\\local\\criticali\\Core\\lib\\CriticalI\\Test.php", '/usr/local/criticali/Core/'));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name('/usr/local/criticali/Core/lib/CriticalI/Test.php', "\\usr\\local\\criticali/Core/"));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name("C:\\usr\\local\\criticali\\core\\lib\\CriticalI\\Test.php"));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name("\\\\SERVER\\usr\\local\\criticali\\core\\lib\\CriticalI\\Test.php"));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name("Http://LocalHost/usr/local/criticali/core/lib/CriticalI/Test.php"));
    $this->assertEquals('CriticalI_Test', CriticalI_ClassUtils::class_name("Http://Someone@LocalHost/usr/local/criticali/core/lib/CriticalI/Test.php"));
    $this->assertEquals('', CriticalI_ClassUtils::class_name('/usr/local/bin'));
  }
  
}

?>