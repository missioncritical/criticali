<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * This class handles parsing of supported document tags
 *
 * @package simpledoc
 */
class SimpleDoc_TagReader {
  
  protected $tags;
  
  protected function __construct(&$tags) {
    $this->tags = &$tags;
  }
  
  /**
   * Read and strip the tags from a formatted comment block
   *
   * @param string $text  The comment text to process
   * @param array  &$tags The array to output tag information to
   * @return string The comment block with recognized tags removed
   */
  public static function parse_tags($text, &$tags) {
    if (!$tags) $tags = array();
    
    $reader = new SimpleDoc_TagReader($tags);
    
    return preg_replace_callback(
      '/' .
        '^[^\S\\r\\n]*@(param|return)[^\S\\r\\n]*(.*$(?:(?:\\r\\n?|\\n)[^@\s].*$)*)' . '|' .
        '^[^\S\\r\\n]*@(\S+)(.*)' .
      '/m',
      array($reader, 'process_tag'),
      $text);
  }
  
  /**
   * Callback for processing a tag
   */
  public function process_tag($matches) {
    $name = $matches[1] ? $matches[1] : $matches[3];
    $rest = $matches[1] ? trim($matches[2]) : trim($matches[4]);
    
    switch ($name) {
      case 'api':
      case 'filesource':
        $this->tags[$name] = true;
        return '';
        
      case 'author':
        if (preg_match('/\s*((?:[^<]\S*\s*)+)<([^>]+)>/', $rest, $matches))
          $this->tags['author'] = array('name'=>trim($matches[1]), 'email'=>$matches[2]);
        else
          $this->tags['author'] = array('name'=>$rest, 'email'=>null);
        return '';
      
      case 'category':
      case 'copyright':
      case 'license':
      case 'package':
      case 'subpackage':
      case 'version':
        $this->tags[$name] = $rest;
        return '';

      case 'deprecated':
      case 'since':
        $matches = array();
        preg_match('/\s*(\d+(?:\.\S*))?\s*(.*)/', $rest, $matches);
        $this->tags[$name] = array('version'=>$matches[1], 'description'=>$matches[2]);
        return '';

      case 'ignore':
      case 'nodoc':
        $this->tags['nodoc'] = true;
        return '';

      case 'internal':
      case 'todo':
        $this->tags[$name] = $rest ? $rest : true;
        return '';
      
      case 'method':
        if (preg_match('/^\s*(\S+)\s*([^\(]\S*)?\s*\(([^\)]*)\)\s*(.*)/', $rest, $matches)) {
          $mname = $matches[2] ? $matches[2] : $matches[1];
          $rtype = $matches[2] ? $matches[1] : '';
          $params = array();
          foreach (explode(',', $matches[3]) as $p) {
            if ($p) {
              list($ptype, $pname) = preg_split('/\s+/', trim($p), 2);
              $params[] = array('type'=>($pname ? trim($ptype) : ''), 'name'=>($pname ? $pname : $ptype));
            }
          }
          $desc = trim($matches[4]);
          if (!$this->tags['methods']) $this->tags['methods'] = array();
          $this->tags['methods'][] =
            array('name'=>$mname, 'type'=>$rtype, 'params'=>$params, 'description'=>$desc);
          
        } else {
          SimpleDoc_ErrorManager::warn("Ignoring @method tag with incorrect syntax: $rest");
        }
        return '';
      
      case 'param':
        if (preg_match('/^\s*([^\$]\S*)?\s*(&?\$\S+)\s*((?:.|\\r|\\n)*)/', $rest, $matches)) {
          if (!$this->tags['params']) $this->tags['params'] = array();
          $this->tags['params'][] =
            array('name'=>$matches[2], 'type'=>$matches[1], 'description'=>$matches[3]);
        } else {
          SimpleDoc_ErrorManager::warn("Ignoring @param tag with incorrect syntax: $rest");
        }
        return '';
      
      case 'property-read':
      case 'property-write':
      case 'property':
        if (preg_match('/^\s*([^\$\s]+)?\s*(\$\S+)\s*(.*)/', $rest, $matches)) {
          $rw = 'rw';
          if ($name == 'property-read') $rw = 'r';
          if ($name == 'property-write') $rw = 'w';
          if (!$this->tags['properties']) $this->tags['properties'] = array();
          $this->tags['properties'][] =
            array('name'=>$matches[2], 'type'=>$matches[1], 'description'=>$matches[3], 'rw'=>$rw);
        } else {
          SimpleDoc_ErrorManager::warn("Ignoring @$name tag with incorrect syntax: $rest");
        }
        return '';

      case 'return':
        list($type, $description) = preg_split('/\s+/', $rest, 2);
        $this->tags['return'] = array('type'=>$type, 'description'=>$description);
        return '';

      case 'throws':
        list($type, $description) = preg_split('/\s+/', $rest, 2);
        if (!$this->tags['throws']) $this->tags['throws'] = array();
        $this->tags['throws'][] = array('type'=>$type, 'description'=>$description);
        return '';

      case 'var':
        if (preg_match('/^\s*(\S+)\s*(\$\S+)?\s*(.*)/', $rest, $matches)) {
          if (!$this->tags['vars']) $this->tags['vars'] = array();
          $this->tags['vars'][] =
            array('name'=>$matches[2], 'type'=>$matches[1], 'description'=>$matches[3]);
        } else {
          SimpleDoc_ErrorManager::warn("Ignoring @var tag with incorrect syntax: $rest");
        }
        return '';

      default:
        SimpleDoc_ErrorManager::warn("Ignoring unknown tag \"@$name\"");
        return $matches[0];
    }
    
  }
}
