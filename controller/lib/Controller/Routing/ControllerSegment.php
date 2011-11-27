<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

/**
 * A routing segment that matches a controller name in the URL.
 */
class Controller_Routing_ControllerSegment extends Controller_Routing_Segment {
  
  /**
   * Return the value of this segment in human readable form.
   *
   * @return string
   */
  public function value() {
    return ':controller';
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
    $str = $url;
    $className = array();
    $controller = array();
    
    while (($token = $this->next_url_token($str, $remainder)) !== '') {
      $className[] = Support_Inflector::camelize(str_replace('-', '_', $token));
      $controller[] = str_replace('-', '_', $token);
      $str = $remainder;

      if (class_exists(implode('_', $className) . 'Controller')) {
        $params['controller'] = implode('/', $controller);
        $unmatched = $remainder;
        return true;
      }
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
    if ($segment instanceof Controller_Routing_ControllerSegment)
      return 0;
    else
      return strcmp(get_class($this), get_class($segment));
  }
  
  /**
   * Essentially the reverse of match(), this method assembles a URL
   * segment from a set of parameters. If this segment cannot construct a
   * URL chunk for the parameters, it returns false. Note that upon
   * completion, $params contains only unconsumed parameters.
   *
   * @param array &$params The parameters to use for assembling the URL
   * @return mixed
   */
  public function url_for(&$params) {
    if (isset($params['controller'])) {
      
      $name = $params['controller'];
      unset($params['controller']);
      
      // if name is a class name, reverse it
      if (substr($name, -10) == 'Controller' && class_exists($name)) {
        $name = substr($name, 0, -10);
        $name = Support_Inflector::underscore(str_replace('_', '/', $name));
      }
      
      return $name;
    }
    
    return false;
  }

}

?>