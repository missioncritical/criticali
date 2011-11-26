<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

/**
 * A routing segment that matches a URL based on a simple pattern with
 * embedded parameter names.
 */
class Controller_Routing_DynamicSegment extends Controller_Routing_Segment {
  
  protected $pattern;
  protected $regex;
  protected $vars;
  
  /**
   * Constructor
   *
   * @param string $pattern The pattern to match
   * @param Controller_Routing_Segment $next The next segment in the route
   */
  public function __construct($pattern, $next = null) {
    parent::__construct($next);
    
    $this->pattern = $pattern;
    $this->vars = array();
    $this->regex = $this->build_regex($pattern);
  }
  
  /**
   * Return the value of this segment in human readable form.
   *
   * @return string
   */
  public function value() {
    return $this->pattern;
  }
  
  /**
   * Return the regular expression used to match this segment
   *
   * @return string
   */
  public function regex() {
    return $this->regex;
  }

  /**
   * Test a portion of a URL against this segment. Returns true if this
   * segment matches, false otherwise.
   *
   * @param string $url The URL portion to match
   * @param array &$params Output parameter for any parameters discovered by this segment
   * @param string &$unmatched Output parameter for the portion of the URL that was not matched (to be passed to the next segment
   * @return boolean
   */
  public function match($url, &$params, &$unmatched) {
    $unmatched = $url;
    $token = $this->next_url_token($url, $remainder);
    
    if (preg_match($this->regex, $token, $matches)) {
      $unmatched = $remainder;

      foreach ($this->vars as $idx=>$name) {
        $params[$name] = $matches[$idx+1];
      }
      
      return true;
    }
    
    return false;
  }
  
  /**
   * Compares this segment to another segment.
   *
   * Returns a value of -1, 0, 1 to indicate $segment is less than, equal
   * to, or greater than this segment, respectively. This method is used
   * to construct routing trees. Segments which are equal will all be
   * grouped together in a branch.
   *
   * @param Controller_Routing_Segment $segment The segment to compare
   * @return int
   */
  public function compare($segment) {
    if ($segment instanceof Controller_Routing_DynamicSegment)
      return strcmp($this->regex, $segment->regex());
    else
      return strcmp(get_class($this), get_class($segment));
  }
  
  /**
   * Build the regular expression for matching URLs
   */
  protected function build_regex($pattern) {
    // escape the sequence first
    $regex = str_replace(
      array("\\E", '/'),
      array("\\E\\\\E\\Q", "\\E\\/\\Q"),
      $pattern
      );
    
    $regex = "/\\A\\Q$regex\\E\\z/";
    
    // handle any parameters encountered
    $regex = preg_replace_callback("/(:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/",
      array($this, 'regex_replace_var'), $regex);
    
    // clean up any needless quoting
    if (substr($regex, 0, 7) == "/\\A\\Q\\E")
      $regex = "/\\A" . substr($regex, 7);
    if (substr($regex, -7) == "\\Q\\E\\z/")
      $regex = substr($regex, 0, -7) . "\\z/";
    
    return $regex;
  }
  
  /**
   * Callback for build_regex replacements
   */
  protected function regex_replace_var($matches) {
    $this->vars[] = substr($matches[1], 1);
    
    return "\\E(.+)\\Q";
  }
  
}

?>