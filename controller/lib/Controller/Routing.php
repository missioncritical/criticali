<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

/**
 * Handles simple routing rules for dispatching requests to controllers.
 *
 * This class looks for a file named routes.php in the config folder and
 * will load it to generate the list of configured routes. Within the
 * context of routes.php, the variable $route contains an instance of
 * Controller_Routing_Builder, which can be used to generate routes.
 *
 * See the documentation for Controller_Routing_Builder for more
 * information.
 */
class Controller_Routing {
  
  protected $routes;
  protected $logger;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->routes = array();
    
    $this->add_configured_routes();
  }
  
  /**
   * Return the controller for a given URL
   *
   * @param string $url         The URL to route
   * @param array &$parameters  Output array for any parameters specified in the URL
   * @param string $method      The request method
   *
   * @return Controller_Base
   */
  public function controller_for($url, &$parameters, $method = 'get') {
    $url = $this->normalize($url);
    $params = $parameters;
    
    // walk the routes
    foreach ($this->routes as $route) {
      if ($route->match($url, $method, $params)) {
        
        // a controller must be specified
        if (!isset($params['controller'])) {
          $this->logger()->error("No controller specified for route #".$route->position());
          continue;
        }
        
        // and the class must exist
        $class = $this->controller_class($params['controller']);
        if (!class_exists($class)) {
          $this->logger()->error("No such controller class \"$class\"");
          continue;
        }
        
        // ok, we're good to go
        $parameters = $params;
        return new $class();
      }
    }
    
    // no match
    return $this->error_route('404', 'Not Found', $method, $parameters);
  }
  
  /**
   * The opposite of controller_for, determines the URL for a given set
   * of parameters. If no URL can be constructed from the parameters,
   * this method throws an exception.
   *
   * @param array $parameters  The parameters to generate a URL from
   * @param string $method     The request method to use
   *
   * @return string
   */
  public function url_for($parameters, $method = 'get') {
    if (!isset($parameters['controller']))
      throw new Exception("url_for requires a controller parameter to be specified");
    
    foreach ($this->routes as $route) {
      $url = $route->url_for($parameters, $method);

      if ($url !== false)
        return $url;
    }
    
    $pretty = array(); foreach ($parameters as $n=>$v) { $pretty[] = "$n=$v"; }
    throw new Exception("No URL could be constructed for the parameters " . implode(", ", $pretty));
  }

  /**
   * Add a route
   *
   * @param CriticalI_Routing_Route $route The route to add
   */
  public function add_route($route) {
    $route->set_position(count($this->routes) + 1);
    $this->routes[] = $route;
  }
  
  /**
   * Returns the controller to be used for a given error status code. If
   * no controller can be found for the request, it forcibly exits.
   *
   * @param int    $code        The error code
   * @param string $message     The error message associated with the code
   * @param string $method      The request method
   * @param array &$parameters  Output array for any parameters specified in the URL
   *
   * @return Controller_Base
   */
  public function error_route($code, $message, $method, &$parameters) {
    header("HTTP/1.1 $code $message");
    
    $params = $parameters;
    
    // this is much like normal routing
    foreach ($this->routes as $route) {
      if ($route->match("/$code", $method, $params)) {
        
        // a controller must be specified
        if (!isset($params['controller'])) {
          $this->logger()->error("No controller specified for route #".$route->position());
          continue;
        }
        
        // and the class must exist
        $class = $this->controller_class($params['controller']);
        if (!class_exists($class)) {
          $this->logger()->error("No such controller class \"$class\"");
          continue;
        }
        
        // ok, we're good to go
        $parameters = $params;
        return new $class();
      }
    }

    // however, no match forces us to exit
    exit();
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
  
  /**
   * Loads configured routing information.
   */
  protected function add_configured_routes() {
    global $ROOT_DIR;
    
    // we'll need a builder
    $builder = new Controller_Routing_Builder($this);
    
    // start with any legacy routes
    $this->add_legacy_configured_routes($builder);
    
    // load the routes file if we have one
    if (file_exists("$ROOT_DIR/config/routes.php")) {
      $route = $builder;
      include_once("routes.php");
    } else {
      $this->add_legacy_default_routes($builder);
    }
  }
  
  /**
   * Convert a class name to a controller parameter value
   *
   * @param string $class  The class name to convert
   *
   * @return string
   */
  protected function controller_name($class) {
    if (substr($class, -10) == 'Controller')
      $class = substr($class, 0, -10);
    return Support_Inflector::underscore(str_replace('_', '/', $class));
  }
  
  /**
   * Convert a controller parameter value to a class name
   *
   * @param string $name  The controller name to convert
   *
   * @return string
   */
  protected function controller_class($name) {
    return str_replace('/', '_', Support_Inflector::camelize($name)) . 'Controller';
  }

  /**
   * Return this class's logger
   *
   * @return Logger
   */
  protected function logger() {
    if (!$this->logger)
      $this->logger = Support_Resources::logger('Routing');
    return $this->logger;
  }

  /**
   * Add any route information specified through legacy configuration
   * properties
   */
  protected function add_legacy_configured_routes($builder) {
    // routes/default => root
    $class = Cfg::get('routes/default');
    if ($class)
      $builder->root(array('controller'=>$this->controller_name($class)));
    
    // routes/404 => /404
    $class = Cfg::get('routes/404');
    if ($class)
      $builder->match('/404', array('controller'=>$this->controller_name($class)));
  }
  
  /**
   * Add the default routes that were previously assumed
   */
  protected function add_legacy_default_routes($builder) {
    $builder->match('/:controller/:action/:id');
    $builder->match('/:controller/:action');
    $builder->match('/:controller');
  }
  
}

?>