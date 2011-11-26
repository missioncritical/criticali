<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

/**
 * A routing segment that matches a URL based on a regular expression.
 */
class Controller_Routing_RegExSegment extends Controller_Routing_Segment {
  
  protected $pattern;
  
  /**
   * Constructor
   *
   * @param string $pattern The pattern to match
   * @param Controller_Routing_Segment $next The next segment in the route
   */
  public function __construct($pattern, $next = null) {
    parent::__construct($next);
    
    $this->pattern = $pattern;
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
    
    if (preg_match($this->pattern, $url)) {
      $unmatched = null;
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
    if ($segment instanceof Controller_Routing_RegExSegment)
      return strcmp($this->pattern, $segment->value());
    else
      return strcmp(get_class($this), get_class($segment));
  }
  
}

?>