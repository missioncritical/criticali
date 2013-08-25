<?php

class Helper_BaseTest_ExampleHelper extends Helper_Base {
  
  public function now() {
    return strftime('%Y-%m-%d %H:%M:%S');
  }
  
  public function timestamp($format = '%Y-%m-%d %H:%M:%S', $hash = array()) {
    return strftime($format);
  }
  
  public function modifier_h($value, $encoding = 'UTF-8') {
    return htmlspecialchars($value, ENT_COMPAT | ENT_HTML401, $encoding);
  }
  
  public function block_wrap($width = 70, $content = null, &$repeat = true, $options = array()) {
    $options = array_merge(array('break'=>"<br/>\n"), $options);
    
    if (!$repeat)
      return wordwrap($content, $width, $options['break']);
  }
  
}

class Helper_BaseTest extends CriticalI_TestCase {
  
  public function testController() {
    $helper = new Helper_BaseTest_ExampleHelper();
    
    $random = rand();
    
    $helper->set_controller($random);
    $this->assertEquals($random, $helper->controller());
  }
  
  public function testTemplateEngine() {
    $helper = new Helper_BaseTest_ExampleHelper();
    
    $random = rand();
    
    $helper->set_template_engine($random);
    $this->assertEquals($random, $helper->template_engine());
  }
  
  public function testHelperFunctions() {
    $helper = new Helper_BaseTest_ExampleHelper();
    
    $expected = array(
      'now'=>new Helper_MethodInfo('now', Helper_MethodInfo::STANDARD_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'now', array(), array(), array($helper, 'now')),
      'timestamp'=>new Helper_MethodInfo('timestamp', Helper_MethodInfo::STANDARD_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'timestamp', array('format', 'hash'),
        array('format'=>'%Y-%m-%d %H:%M:%S', 'hash'=>array()), array($helper, 'timestamp')),
      'h'=>new Helper_MethodInfo('h', Helper_MethodInfo::MODIFIER_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'modifier_h', array('value', 'encoding'),
        array('encoding'=>'UTF-8'), array($helper, 'modifier_h')),
      'wrap'=>new Helper_MethodInfo('wrap', Helper_MethodInfo::BLOCK_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'block_wrap', array('width', 'content', 'repeat', 'options'),
        array('width'=>70, 'content'=>null, 'repeat'=>true, 'options'=>array()),
        array($helper, 'block_wrap'))
    );
    
    $this->assertEquals($this->stripHMI($expected), $this->stripHMI($helper->helper_functions()));
  }

  public function testStandardFunctions() {
    $helper = new Helper_BaseTest_ExampleHelper();
    
    $expected = array(
      'now'=>new Helper_MethodInfo('now', Helper_MethodInfo::STANDARD_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'now', array(), array(), array($helper, 'now')),
      'timestamp'=>new Helper_MethodInfo('timestamp', Helper_MethodInfo::STANDARD_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'timestamp', array('format', 'hash'),
        array('format'=>'%Y-%m-%d %H:%M:%S', 'hash'=>array()), array($helper, 'timestamp'))
    );
    
    $this->assertEquals($this->stripHMI($expected), $this->stripHMI($helper->standard_functions()));
  }
  
  public function testModifierFunctions() {
    $helper = new Helper_BaseTest_ExampleHelper();
    
    $expected = array(
      'h'=>new Helper_MethodInfo('h', Helper_MethodInfo::MODIFIER_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'modifier_h', array('value', 'encoding'),
        array('encoding'=>'UTF-8'), array($helper, 'modifier_h'))
    );
    
    $this->assertEquals($this->stripHMI($expected), $this->stripHMI($helper->modifier_functions()));
  }
  
  public function testBlockFunctions() {
    $helper = new Helper_BaseTest_ExampleHelper();
    
    $expected = array(
      'wrap'=>new Helper_MethodInfo('wrap', Helper_MethodInfo::BLOCK_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'block_wrap', array('width', 'content', 'repeat', 'options'),
        array('width'=>70, 'content'=>null, 'repeat'=>true, 'options'=>array()),
        array($helper, 'block_wrap'))
    );
    
    $this->assertEquals($this->stripHMI($expected), $this->stripHMI($helper->block_functions()));
  }
  
  public function tetDisableHelperFunction() {
    $helper = new Helper_BaseTest_ExampleHelper();
    $helper->disable_helper_function('timestamp');
    
    $expected = array(
      'now'=>new Helper_MethodInfo('now', Helper_MethodInfo::STANDARD_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'now', array(), array(), array($helper, 'now')),
      'h'=>new Helper_MethodInfo('h', Helper_MethodInfo::MODIFIER_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'modifier_h', array('value', 'encoding'),
        array('encoding'=>'UTF-8'), array($helper, 'modifier_h')),
      'wrap'=>new Helper_MethodInfo('wrap', Helper_MethodInfo::BLOCK_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'block_wrap', array('width', 'content', 'repeat', 'options'),
        array('width'=>70, 'content'=>null, 'repeat'=>true, 'options'=>array()),
        array($helper, 'block_wrap'))
    );
    
    $this->assertEquals($this->stripHMI($expected), $this->stripHMI($helper->helper_functions()));
  }
  
  public function testStandardFunction() {
    $helper = new Helper_BaseTest_ExampleHelper();
    
    $expected = array(
      'now'=>new Helper_MethodInfo('now', Helper_MethodInfo::STANDARD_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'now', array(), array(), array($helper, 'now')),
      'timestamp'=>new Helper_MethodInfo('timestamp', Helper_MethodInfo::STANDARD_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'timestamp', array('format', 'hash'),
        array('format'=>'%Y-%m-%d %H:%M:%S', 'hash'=>array()), array($helper, 'timestamp')),
      'wrap'=>new Helper_MethodInfo('wrap', Helper_MethodInfo::BLOCK_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'block_wrap', array('width', 'content', 'repeat', 'options'),
        array('width'=>70, 'content'=>null, 'repeat'=>true, 'options'=>array()),
        array($helper, 'block_wrap')),
      'modifier_h'=>new Helper_MethodInfo('modifier_h', Helper_MethodInfo::STANDARD_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'modifier_h', array('value', 'encoding'),
        array('encoding'=>'UTF-8'), array($helper, 'modifier_h')),
    );
    
    $helper->standard_function('modifier_h');
    $this->assertEquals($this->stripHMI($expected), $this->stripHMI($helper->helper_functions()));

    $helper->disable_helper_function('modifier_h');
    $expected2 = $expected;
    unset($expected2['modifier_h']);
    $this->assertEquals($this->stripHMI($expected2), $this->stripHMI($helper->helper_functions()));

    $helper->standard_function('modifier_h');
    $this->assertEquals($this->stripHMI($expected), $this->stripHMI($helper->helper_functions()));
  }
  
  public function testModifierFunction() {
    $helper = new Helper_BaseTest_ExampleHelper();
    
    $expected = array(
      'now'=>new Helper_MethodInfo('now', Helper_MethodInfo::STANDARD_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'now', array(), array(), array($helper, 'now')),
      'timestamp'=>new Helper_MethodInfo('timestamp', Helper_MethodInfo::MODIFIER_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'timestamp', array('format', 'hash'),
        array('format'=>'%Y-%m-%d %H:%M:%S', 'hash'=>array()), array($helper, 'timestamp')),
      'h'=>new Helper_MethodInfo('h', Helper_MethodInfo::MODIFIER_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'modifier_h', array('value', 'encoding'),
        array('encoding'=>'UTF-8'), array($helper, 'modifier_h')),
      'wrap'=>new Helper_MethodInfo('wrap', Helper_MethodInfo::BLOCK_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'block_wrap', array('width', 'content', 'repeat', 'options'),
        array('width'=>70, 'content'=>null, 'repeat'=>true, 'options'=>array()),
        array($helper, 'block_wrap'))
    );
    
    $helper->modifier_function('timestamp');
    $this->assertEquals($this->stripHMI($expected), $this->stripHMI($helper->helper_functions()));
  }
  
  public function testBlockFunction() {
    $helper = new Helper_BaseTest_ExampleHelper();
    
    $expected = array(
      'now'=>new Helper_MethodInfo('now', Helper_MethodInfo::STANDARD_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'now', array(), array(), array($helper, 'now')),
      'timestamp'=>new Helper_MethodInfo('timestamp', Helper_MethodInfo::BLOCK_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'timestamp', array('format', 'hash'),
        array('format'=>'%Y-%m-%d %H:%M:%S', 'hash'=>array()), array($helper, 'timestamp')),
      'h'=>new Helper_MethodInfo('h', Helper_MethodInfo::MODIFIER_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'modifier_h', array('value', 'encoding'),
        array('encoding'=>'UTF-8'), array($helper, 'modifier_h')),
      'wrap'=>new Helper_MethodInfo('wrap', Helper_MethodInfo::BLOCK_FUNCTION,
        'Helper_BaseTest_ExampleHelper', 'block_wrap', array('width', 'content', 'repeat', 'options'),
        array('width'=>70, 'content'=>null, 'repeat'=>true, 'options'=>array()),
        array($helper, 'block_wrap'))
    );
    
    $helper->block_function('timestamp');
    $this->assertEquals($this->stripHMI($expected), $this->stripHMI($helper->helper_functions()));
  }

  protected function stripHMI($hmis) {
    if (!is_array($hmis)) $hmis = array($hmis);
    
    $out = array();
    foreach ($hmis as $hmi) {
      $out[] = array('name'=>$hmi->name, 'type'=>$hmi->type, 'class_name'=>$hmi->class_name,
        'method_name'=>$hmi->method_name, 'parameter_names'=>$hmi->parameter_names,
        'defaults'=>$hmi->defaults);
    }
    
    return $out;
  }
  
}
