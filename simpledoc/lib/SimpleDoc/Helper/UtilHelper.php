<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Utility helper routines for document templates
 *
 * @package simpledoc
 */
class SimpleDoc_Helper_UtilHelper extends Helper_Base {
  
  /**
   * Ensure a value is safe for use in a file name
   * @param string $value The value to use
   * @return string A copy of the original value with any unsafe or
   * unpermitted characters converted to underscores.
   */
  public function modifier_file_safe($name) {
    return preg_replace('/[^,.0-9A-Z_a-z-]+/', '_', $name);
  }
  
  /**
   * Return the full signature HTML for a method
   * @param SimpleDoc_Model_Method $method The method to use
   * @return string The HTML of the full signature
   */
  public function modifier_method_signature($method) {
    $html = '<span class="visibility">' . $method->visibility() . '</span>';
    
    if ($method->is_static)
      $html .= ' <span class="modifier">static</span>';
    if ($method->is_final)
      $html .= ' <span class="modifier">final</span>';
    if ($method->is_abstract)
      $html .= ' <span class="modifier">abstract</span>';
    
    if ($method->type)
      $html .= ' <span class="return_type">' . htmlspecialchars($method->type) . '</span>';
    
    $html .=' <span class="name">' .
      ($method->is_byref ? '&amp;' : '') .
      htmlspecialchars($method->name) .
      '</span>';
    
    $html .= '<span class="params">(' . $method->parameter_declaration() . ')</span>';
    
    return $html;
  }
  
  /**
   * Return the full signature HTML for a function
   * @param SimpleDoc_Model_Function $function The function to use
   * @return string The HTML of the full signature
   */
  public function modifier_function_signature($function) {
    $html = '';
    
    if ($function->type)
      $html .= ' <span class="return_type">' . htmlspecialchars($function->type) . '</span>';
    
    $html .=' <span class="name">' .
      ($function->is_byref ? '&amp;' : '') .
      htmlspecialchars($function->name) .
      '</span>';
    
    $html .= '<span class="params">(' . $function->parameter_declaration() . ')</span>';
    
    return $html;
  }

  /**
   * Output HTML from a markdown source string
   * @param string $markup The markdown source
   * @return string
   */
  public function modifier_markdown($markup) {
    $parser = new Markdown_Parser();
    return $parser->transform($markup);
  }
  
  /**
   * Return the named class, or null if not found
   */
  public function class_for_name($name) {
    foreach ($this->controller()->packages as $pkg) {
      foreach ($pkg->classes as $class) {
        if ($class->name == $name)
          return $class;
      }
    }
    
    return null;
  }
  
  /**
   * Return a link to the named class, or the class name if it is not in
   * the documentation set.
   */
  public function class_link($class, $options = array()) {
    $name = htmlspecialchars($class instanceof SimpleDoc_Model_Class ? $class->name : $class);
    $obj = $class instanceof SimpleDoc_Model_Class ? $class : $this->class_for_name($class);
    
    if ($obj) {
      unset($options['class']);
      
      $options['href'] = $this->controller()->path_to_root .
        $this->modifier_file_safe($obj->package_name) .
        '/' . $this->modifier_file_safe($obj->name) . '.html';
        
      return Support_TagHelper::content_tag('a', $name, $options);
    } else {
      return $name;
    }
  }
  
  /**
   * Sort a list of items and assign them to an output variable
   *
   * @param array $from The list of items to sort
   * @param string $to The variable name to assign them to
   */
  public function sort_to($from, $to, $options = array()) {
    $list = $from;

    sort($list);
    
    $this->template_engine()->assign($to, $list);
  }

  /**
   * Sort a list of objects by property (defaults to name) and assign
   * them to an output variable
   *
   * @param array $from The list of objects to sort
   * @param string $to The variable name to assign them to
   * @param string $by The property to sort by
   */
  public function propsort_to($from, $to, $by = 'name', $options = array()) {
    $list = $from;
    $sorter = new SimpleDoc_Helper_UtilHelper_PropSorter(explode(',', $by));

    usort($list, array($sorter, 'compare'));
    
    $this->template_engine()->assign($to, $list);
  }
  
  /**
   * Filter a list of objects so that only those whose method or property
   * with the given name has the provided value.
   *
   *
   * @param array $from The list of objects to sort
   * @param string $to The variable name to assign them to
   * @param string $by The method or property to filter by
   * @param mixed $equals The value $by must equal to pass
   */
  public function filter_objects($from, $to, $by, $equals = true, $options = array()) {
    $result = array();
    
    foreach ($from as $item) {
      if (is_object($item) && method_exists($item, $by))
        $val = $item->$by();
      else if (is_object($item))
        $val = $item->$by;
      else
        $val = $item[$by];
      
      if ($val == $equals)
        $result[] = $item;
    }
    
    $this->template_engine()->assign($to, $result);
  }
  
}

/**
 * Comparison routine for property sorting
 * @nodoc
 */
class SimpleDoc_Helper_UtilHelper_PropSorter {
  public $by;
  public function __construct($by = array('name')) { $this->by = $by; }
  
  public function compare($a, $b) {
    $cmp = 0;
    
    foreach ($this->by as $by) {
      $cmp = strcmp($a->$by, $b->$by);

      if ($cmp !== 0) return $cmp;
    }
    
    return $cmp;
  }
}

