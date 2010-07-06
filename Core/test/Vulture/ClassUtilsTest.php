<?php

class Vulture_ClassUtilsTest extends Vulture_TestCase {
  
  public function testClassName() {
    $this->assertEquals('Vulture', Vulture_ClassUtils::class_name('Vulture.php'));
    $this->assertEquals('Vulture', Vulture_ClassUtils::class_name('lib/Vulture.php'));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name('lib/Vulture/Test.php'));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name('lib/Vulture/Test.php', ''));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name('lib/Vulture/Test.php', 'bogus'));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name('lib/Vulture/Test'));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name('lib/Vulture/Test.inc'));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name('/usr/local/vulture/Core/lib/Vulture/Test.php', '/usr/local/vulture/Core'));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name('/usr/local/vulture/Core/lib/Vulture/Test.php', '/usr/local/vulture/Core/'));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name("\\usr\\local\\vulture\\Core\\lib\\Vulture\\Test.php", '/usr/local/vulture/Core/'));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name('/usr/local/vulture/Core/lib/Vulture/Test.php', "\\usr\\local\\vulture/Core/"));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name("C:\\usr\\local\\vulture\\core\\lib\\Vulture\\Test.php"));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name("\\\\SERVER\\usr\\local\\vulture\\core\\lib\\Vulture\\Test.php"));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name("Http://LocalHost/usr/local/vulture/core/lib/Vulture/Test.php"));
    $this->assertEquals('Vulture_Test', Vulture_ClassUtils::class_name("Http://Someone@LocalHost/usr/local/vulture/core/lib/Vulture/Test.php"));
    $this->assertEquals('', Vulture_ClassUtils::class_name('/usr/local/bin'));
  }
  
}

?>