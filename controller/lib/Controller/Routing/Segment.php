<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

/**
 * Represents a segment of a route. A segment is a list of rules for
 * matching a portion of a route. A collection of one or more segments
 * comprise a route.
 */
abstract class Controller_Routing_Segment {
  
  protected $next;
  
  /**
   * Constructor
   *
   * @param Controller_Routing_Segment $next The next segment in the route
   */
  public function __construct($next = null) {
    $this->next = $next;
  }
  
  /**
   * Return the next segment in the route
   *
   * @return Controller_Routing_Segment
   */
  public function next() {
    return $this->next;
  }
  
  /**
   * Set the next segment in the route
   *
   * @param Controller_Routing_Segment $next The next segment for the route
   */
  public function set_next($next) {
    return $this->next = $next;
  }
  
  /**
   * Return the value of this segment in human readable form.
   *
   * @return string
   */
  abstract public function value();
  
  /**
   * Test a portion of a URL against this segment. Returns true if this
   * segment matches, false otherwise.
   *
   * @param string $url The URL portion to match
   * @param array &$params Output parameter for any parameters discovered by this segment
   * @param string &$unmatched Output parameter for the portion of the URL that was not matched (to be passed to the next segment
   * @return boolean
   */
  abstract public function match($url, &$params, &$unmatched);
  
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
  abstract public function compare($segment);
  
  /**
   * Essentially the reverse of match(), this method assembles a URL
   * segment from a set of parameters. If this segment cannot construct a
   * URL chunk for the parameters, it returns false. Note that upon
   * completion, $params contains only unconsumed parameters.
   *
   * @param array &$params The parameters to use for assembling the URL
   * @return mixed
   */
  abstract public function url_for(&$params);
  
  /**
   * Breaks a URL into a next token and remaining segment
   *
   * @param string $url The URL to tokenize
   * @param string &$remainder Output parameter for the remaining segment
   * @return string
   */
  protected function next_url_token($url, &$remainder) {
    // strip leading slashes
    while (strlen($url) > 0 && $url[0] == '/') {
      $url = substr($url, 1);
    }
    
    // tokenize
    list($token, $remainder) = explode('/', $url, 2);
    
    return urldecode($token);
  }
  
}

?>