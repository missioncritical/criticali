<?php
// Copyright (c) 2008-2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package helper */

/**
 * Helper functions related to the head element in a document when
 * outputting HTML.
 */
class Helper_DefaultHelpers_HeadHelper extends Helper_Base {
  
  protected $javascriptUrls = array();
  protected $cssUrls = array();
  
  /**
   * Append or assign a value to a specific index of the $blocks template
   * variable
   */
  protected function set_block_index($index, $value, $append = false) {
    $tpl = $this->template_engine();
    if (!$tpl)
      return;

    $blocks  = $tpl->get_template_vars('blocks');
    if (!$blocks) $blocks = array();

    if (isset($blocks[$index]) && $append)
      $blocks[$index] .= $value;
    else
      $blocks[$index] = $value;

    $tpl->assign('blocks', $blocks);
  }
  
  /**
   * Capture a block of content and append (or optionally overwrite) it to
   * an index in the blocks variable.
   *
   * @param string  $block   The index in blocks where it should be stored
   * @param boolean $replace If true, replace any existing content already stored
   * @param boolean $output  If true, output the content in addition to saving it
   */
  public function block_add_to_block($block, $replace = false, $output = false,
    $content = '', &$repeat = false, $params = array()) {
    
    // open tag, continue
    if ($repeat)
      return;
    
    $this->set_block_index($block, $content, (!$replace));
    
    // only output the content if requested
    if ($output)
      return $content;
    else
      return '';
  }
  
  /**
   * Add content to the head block
   */
  public function block_add_to_head($content = '', &$repeat = false, $params = array()) {
    if ($repeat)
      return;
    
    $this->set_block_index('head', $content, true);
  }
  
  /**
   * Add a script tag to the head block for including javascript
   *
   * @param string $src    The URL of the javascript to link
   * @param array  $params Any additional parameters are passed through as html attributes
   */
  public function link_javascript($src, $params = array()) {
    // prevent multiple inclusions
    if (isset($this->javascriptUrls[$src]))
      return;
    else
      $this->javascriptUrls[$src] = 1;
    
    $attrs = array_merge(array('type'=>'text/javascript'), $params, array('src'=>$src));
    
    $tag = Support_TagHelper::content_tag('script', '', $attrs) . "\n";
    
    $this->set_block_index('head', $tag, true);
  }
  
  /**
   * Add a link tag to the head block for including CSS
   *
   * @param string $href  The URL of the CSS to link
   * @param array  $params Any additional parameters are passed through as html attributes
   */
  public function link_css($href, $params = array()) {
    // prevent multiple inclusions
    if (isset($this->cssUrls[$href]))
      return;
    else
      $this->cssUrls[$href] = 1;

    $attrs = array_merge(array('rel'=>'stylesheet', 'type'=>'text/css'), $params, array('href'=>$href));
    
    $tag = Support_TagHelper::tag('link', $attrs) . "\n";
    
    $this->set_block_index('head', $tag, true);
  }
  
}
