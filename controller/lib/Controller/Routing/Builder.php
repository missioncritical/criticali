<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

/**
 * Controller_Routing_Builder is used as a convenient way to add routes to
 * the system.
 * 
 * <b>Building Routes</b>
 *
 * There are three primary methods the builder exposes for adding routes:
 * <var>match</var>, <var>regex</var>, and <var>root</var>. Most routes can be
 * added using the match method. As a simple example, take the default
 * routes (assuming <var>$route</var> is an instance of
 * Controller_Routing_Builder):
 * <code>
 *   $route->match("/:controller/:action/:id"); // matches URLs like "/user/edit/5"
 *   $route->match("/:controller/:action");     // matches URLs without an id component
 *   $route->match("/:controller", array('action'=>'index'));
 *                                              // uses 'index' for URLs without an action value
 * </code>
 *
 * The regex method allows an entire regular expression to be evaluated
 * against the URL:
 * <code>
 *   // sends any URL with the words "i don't know" to the slime action of
 *   // the error controller:
 *
 *   $route->regex("/\\bi don't know\\b/i",
 *     array('controller'=>'error, 'action'=>'slime'));
 * </code>
 *
 * Lastly, root allows you to connect a route to the root URL (/):
 * <code>
 *   $route->root(array('controller'=>'home'));
 * </code>
 *
 * <b>Parameters</b>
 *
 * There are two ways to set parameters as part of a route. With the
 * match method, any parameter name may be prefixed with a colon (:)
 * within the URL to indicate it is to be pulled from that location:
 * <code>
 *   $route->match('/password_reset/:token', array('controller'=>'session',
 *     'action'=>'password_reset'));
 *
 *   // the URL "/password_reset/68656c6c6f20776f726c64" would produce the parameters:
 *   // array('controller'=>'session', 'action'=>'password_reset', 'token'=>'68656c6c6f20776f726c64')
 * </code>
 * 
 * Additionally, all of the builder methods accept an array of default
 * parameter values as the first argument following any path matching
 * information:
 * <code>
 *   $route->match('/article/:id', array('controller'=>'articles',
 *     'action'=>'show', 'category'=>'all'));
 *
 *   // the URL "/article/20110824.3" would produce the parameters:
 *   // array('controller'=>'articles', 'action'=>'show', 'id'=>'20110824.3', 'category'=>'all')
 * </code>
 *
 * <b>Constraints</b>
 *
 * All of the builder methods also accept a second array argument which
 * can be used to impose further matching conditions. One type of matching
 * constraint is to limit the HTTP method used to access the route:
 * <code>
 *   $route->match('/articles', array('controller'=>'articles',
 *     'action'=>'create'), array('method'=>'put'));
 *   // PUT /articles would match the route
 *   // GET /articles would not
 *
 *   $route->match('/article/:id', array('controller=>'articles',
 *     'action'=>'edit'), array('method'=>array('get', 'post')));
 *   // GET /article/497 would match the route
 *   // POST /article/497 would match the route
 *   // PUT /article/497 would not match
 * </code>
 *
 * The second type of constraint requires a string or regular expression to
 * match a parameter value (the parameter name is provided as the key):
 * <code>
 *  $route->match('/article/:id', array('controller'=>'articles',
 *    'action'=>'show'), array('method'=>'get', id=>"/\\A[H|S]R\\d+\\z/"));
 *  // GET /article/HR1842 would match the route
 *  // GET /article/1842 would not
 *  // POST /article/HR1842 would also not match
 *
 *  $route->match('/article/:id', array('controller'=>'articles',
 *    'action'=>'show'), array('format'=>'html'));
 *  // GET /article/1842?format=html would match
 *  // GET /article/1842?format=pdf would not match
 *  // GET /article/1842 would not match
 * </code>
 */
class Controller_Routing_Builder {
  
  protected $routing;

  /**
   * Constructor
   *
   * @param Controller_Routing $routing The routing object that constructed routes are added to
   */
  public function __construct($routing) {
    $this->routing = $routing;
  }
  
  /**
   * The match method adds a route based on matching a URL string with
   * optional embedded parameters.
   *
   * See the class documentation for more details and examples.
   *
   * @param string $path The URL string to match
   * @param array $defaults Any default parameter values to include as part of the route
   * @param array $constraints Any constraints to include as part of the route
   */
  public function match($path, $defaults = null, $constraints = null) {
    // clean the URL for parsing
    $path = preg_replace("/\\A\\/+/", '', $path);
    $path = preg_replace("/\\/+\\z/", '', $path);
    
    $head = null;
    $tail = null;
    
    // build the segments
    foreach (explode('/', $path) as $portion) {
      if ($portion == ':controller')
        $segment = new Controller_Routing_ControllerSegment();
      elseif (strpos($portion, ':') !== false)
        $segment = new Controller_Routing_DynamicSegment($portion);
      else
        $segment = new Controller_Routing_StaticSegment($portion);
      
      // add to the list
      if (is_null($head)) $head = $segment;
      if (!is_null($tail)) $tail->set_next($segment);
      $tail = $segment;
    }
    
    // create the route
    $route = new Controller_Routing_Route(null, $head, $constraints, $defaults);
    
    $this->routing->add_route($route);
  }

  /**
   * The regex method adds a route based on regular expression to be
   * evaluated against the complete URL.
   *
   * See the class documentation for more details and examples.
   *
   * @param string $regex The regular expression to use
   * @param array $defaults Any default parameter values to include as part of the route
   * @param array $constraints Any constraints to include as part of the route
   */
  public function regex($regex, $defaults = null, $constraints = null) {
    // create the single segment
    $segment = new Controller_Routing_RegExSegment($regex);
    
    // create the route
    $route = new Controller_Routing_Route(null, $segment, $constraints, $defaults);
    
    // add it
    $this->routing->add_route($route);
  }
  
  /**
   * The root method adds a route that matches the site root URL.
   *
   * See the class documentation for more details and examples.
   *
   * @param array $defaults Any default parameter values to include as part of the route
   * @param array $constraints Any constraints to include as part of the route
   */
  public function root($defaults = null, $constraints = null) {
    // create the single segment
    $segment = new Controller_Routing_StaticSegment('');
    
    // create the route
    $route = new Controller_Routing_Route(null, $segment, $constraints, $defaults);
    
    // add it
    $this->routing->add_route($route);
  }
}

?>