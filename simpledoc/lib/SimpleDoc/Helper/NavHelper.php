<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Helper routines for navigation in document templates
 *
 * @package simpledoc
 */
class SimpleDoc_Helper_NavHelper extends Helper_Base {
  
  /**
   * Output an unordered list of navigation links.
   */
  public function navlist($selected = false, $options = array()) {
    $pkgName = null;

    if ($selected && $selected instanceof SimpleDoc_Model_Package)
      $pkgName = $selected->name;
    else if ($selected)
      $pkgName = $selected->package_name;
    
    $items = array();
    
    foreach($this->controller()->packages as $pkg) {
      $attrs = array('class'=>'package');
      
      if ($pkg->name == $pkgName)
        $attrs['class'] .= ' selected';
      
      $html = $this->package_navlink($pkg) . "\n" . $this->package_navlist($pkg, $selected);
      
      $items[] = Support_TagHelper::content_tag('li', $html, $attrs);
    }
    
    return '<ul class="navlist">' . "\n" . implode("\n", $items) . "\n</ul>";
  }
  
  /**
   * Return the navigation link for a package
   */
  protected function package_navlink($pkg) {
    return Support_TagHelper::content_tag('a', $this->esc($pkg->name),
      array('href'=>$this->controller()->path_to_root .
                    $this->file_safe($pkg->name) . '/index.html'));
  }
  
  /**
   * Return the navigation list for a package
   */
  protected function package_navlist($pkg, $selected) {
    $items = array();

    foreach ($pkg->classes as $cls) {
      $items[] = $this->package_navlist_item($pkg, $cls, $this->file_safe($cls->name),
        $selected, array('class'=>'class'));
    }

    if (count($pkg->functions) > 0) {
      $a = Support_TagHelper::content_tag('a', 'Functions',
        array('href'=>$this->controller()->path_to_root .
                      $this->file_safe($pkg->name) . '/_functions.html'));
    
      $attrs = array('class'=>'functions');
      
      if ($pkg->functions === $selected)
        $attrs['class'] .= ' selected';
    
      $items[] = Support_TagHelper::content_tag('li', $a, $attrs);
    }
      
    foreach ($pkg->guides as $guide) {
      if (!$guide->is_index)
        $items[] = $this->package_navlist_item($pkg, $guide, '_guides/' . $this->file_safe($guide->name),
          $selected, array('class'=>'guide'));
    }

    return '<ul>' . "\n" . implode("\n", $items) . "\n</ul>";
  }

  /**
   * Return the navigation list item for a package member
   */
  protected function package_navlist_item($pkg, $item, $fname, $selected, $attrs) {
    $a = Support_TagHelper::content_tag('a', $item->name,
      array('href'=>$this->controller()->path_to_root .
                    $this->file_safe($pkg->name) . '/' .
                    $fname . '.html'));
    
    if ($item == $selected)
      $attrs['class'] .= ' selected';
    
    return Support_TagHelper::content_tag('li', $a, $attrs);
  }

  /**
   * Ensure a value is safe for use in a file name
   * @param string $value The value to use
   * @return string A copy of the original value with any unsafe or
   * unpermitted characters converted to underscores.
   */
  protected function file_safe($name) {
    return preg_replace('/[^,.0-9A-Z_a-z-]+/', '_', $name);
  }

  /**
   * Perform HTML escaping on a string
   */
  protected function esc($value) {
    return htmlspecialchars($value);
  }
  
}
