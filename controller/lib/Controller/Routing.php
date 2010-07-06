<?php

/**
 * Handles simple routing rules for dispatching requests to controllers
 * by URL.  For the initial iteration this is just a hard-coded set of rules.
 */
class Controller_Routing {
  
  /**
   * Constructor
   */
  public function __construct() {
  }
  
  /**
   * Return the controller for a given URL
   *
   * @param string $url         The URL to route
   * @param array &$parameters  Output array for any parameters specified in the URL
   *
   * @return Controller_Base
   */
  public function controller_for($url, &$parameters) {
    $url = $this->normalize($url);

    $parts = explode('/', $url);
    // treat everything as an absolute path for now
    if ($parts[0] != '') array_unshift($parts, '');
    
    // empty path
    if (count($parts) == 1)
      return $this->default_controller();
    
    if ((count($parts) > 1) && (count($parts) < 5)) {
      // controller
      $controller = $parts[1];
      // controller/action
      if (count($parts) > 2)
        $parameters['action'] = $parts[2];
      // controller/action/id
      if (count($parts) > 3)
        $parameters['id'] = $parts[3];
      
      // determine the controller class name
      $class = Support_Inflector::camelize($controller) . 'Controller';
      if (!class_exists($class))
        return $this->not_found();
      else
        return new $class();
    
    } else {
      return $this->unmatched_route();
    }
  }
  
  /**
   * Returns the controller for the default route
   *
   * @return Controller_Base
   */
  protected function default_controller() {
    $class = Cfg::get('routes/default');
    if (!class_exists($class))
      return $this->not_found();
    else
      return new $class();
  }

  /**
   * Performs the behavior for an unmatched route.  (Currently this is
   * just the same as not found)
   *
   * @return Controller_Base
   */
  protected function unmatched_route() {
    return $this->not_found();
  }

  
  /**
   * Returns the controller to be used when a route pattern is matched
   * but no controller was found.
   *
   * @return Controller_Base
   */
  protected function not_found() {
    header('HTTP/1.0 404 Not Found');
    
    $class = Cfg::get('routes/404');
    if (!class_exists($class))
      exit();
    else
      return new $class();
  }
  
  /**
   * Normalize a URL to a request path
   *
   * @param string $url  The URL to normalize
   *
   * @return string
   */
  protected function normalize($url) {
    $matches = array();
    
    $url = str_replace("\\", '/', $url);
    
    // no schema
    if (preg_match("/\\A[^:\\/]+:(\\/\\/.*)/", $url, $matches))
      $url = $matches[1];
    // no host name or login information
    if (preg_match("/\\A\\/\\/[^\\/]+(.*)/", $url, $matches))
      $url = $matches[1];
    // strip fragments
    if (preg_match("/\\A([^#]+)#/", $url, $matches))
      $url = $matches[1];
    // strip query string
    if (preg_match("/\\A([^?]+)\\?/", $url, $matches))
      $url = $matches[1];
    // no trailing slash
    if (substr($url, -1) == '/')
      $url = substr($url, 0, -1);
      
    // process . and ..
    $cleaned = array();
    foreach (explode('/', $url) as $folder) {
      if ($folder == '.')
        continue;
      if (($folder == '') && (count($cleaned) > 0))
        continue;
        
      if ($folder == '..') {
        if (count($cleaned) > 1)
          array_pop($cleaned);
      } else {
        $cleaned[] = $folder;
      }
    }
    
    return implode('/', $cleaned);
  }
}

?>