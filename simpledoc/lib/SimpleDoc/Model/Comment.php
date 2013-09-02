<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * A document comment which may be associated with one or more items found in a source file.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Comment {
  
  /** The comment text */
  public $text;
  
  /** The collection of tags found within the comment */
  public $tags;
  
  /**
   * Constructor
   *
   * @param PHPParser_Comment $comment The parsed comment
   */
  public function __construct($comment) {
    $extractedText = $this->strip_delimiters($comment->getText());
    $this->tags = array();
    
    $this->text = SimpleDoc_TagReader::parse_tags($extractedText, $this->tags);
  }
  
  /**
   * Strip the comment start, end, and optional leading * from a comment
   */
  protected function strip_delimiters($text) {
    
    // comments like:
    //  /**
    //   * Text
    //   */
    if (preg_match('/\A\/\*[^\\r\\n]*[\\r\\n]+\s*\*/', $text)) {
      $text = preg_replace('/\A\/\*+[ \t]*[\r\n]*|[ \t]*\*+\/\s*\z/', '', $text);
      $text = preg_replace('/^\s*\*\s?/m', '', $text);
    
    // comments like:
    //  /**
    //    Text
    //  */
    } elseif (preg_match('/\A\/\*/', $text) && preg_match('/[\\n\\r]+([ \t]*)\*+\/\s*\z/', $text, $matches)) {
      $text = preg_replace('/\A\/\*+[ \t]*[\r\n]*|[ \t]*\*+\/\s*\z/', '', $text);
      $text = preg_replace('/^' . preg_quote($matches[1]) . '\s?/m', '', $text);
    
    // comments like:
    //  /** Text
    //      Text */
    } elseif (preg_match('/\A\/\*[^\r\n]+[\r\n]+(\s+)\S/', $text, $matches)) {
      $text = preg_replace('/\A\/\*+[ \t]*[\r\n]*|[ \t]*\*+\/\s*\z/', '', $text);
      $text = preg_replace('/^' . preg_quote($matches[1]) . '/m', '', $text);
    
    // comments like:
    //  /** Text */
    } elseif (substr($text, 0, 2) == '/*') {
      $text = preg_replace('/\A\/\*+[ \t]*[\r\n]*|[ \t]*\*+\/\s*\z/', '', $text);
      
    // comments like:
    //  // Text
    } elseif (substr($text, 0, 2) == '//') {
      $text = preg_replace('/^\s*\/\/\s?/m', '', $text);
    
    
    // comments like:
    //  # Text
    } elseif (substr($text, 0, 1) == '#') {
      $text = preg_replace('/^\s*#+\s?/m', '', $text);
    }
    
    return $text;
  }
}
