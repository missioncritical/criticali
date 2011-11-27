<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

/**
 * Class for event listener information
 */
class Controller_Base_EventListener {
  public $event;
  public $callback;
  public $only;
  public $except;
  
  public function __construct($event, $callback, $only, $except) {
    $this->event    = $event;
    $this->callback = $callback;
    $this->only     = ((!$only) || is_array($only)) ? $only : array($only);
    $this->except   = ((!$except) || is_array($except)) ? $except : array($except);
  }
  
  public function can_call($action) {
    if ($this->only) {
      return (array_search($action, $this->only) !== false);
    } elseif ($this->except) {
      return (array_search($action, $this->except) === false);
    } else {
      return true;
    }
  }
}

/**
 * Controller_Base is an abstract base class for implementing controllers.
 *
 * Controllers are expected to provide one or more public methods which
 * require no arguments to be passed. All such public methods are
 * considered actions of the controller (unless the method name has been
 * passed to hide_action()). Actions are automatically invoked to service
 * a request depending on the request URL and the configured routing in
 * the application.
 *
 * For example, with default routing in place, a request to the URL
 * "/hello" would invoke the index method of this controller:
 * <code>
 *   class HelloController extends Controller_Base {
 * 
 *     public function index() {
 *     }
 *
 *   }
 * </code>
 *
 * Unless the action explicitly renders a response, the render_action()
 * method is called automatically when the method returns. By default,
 * this would expect a view template to exist at "views/hello/index.tpl".
 * That might look something like:
 * <pre>
 *   <html>
 *     <head><title>Hello!</title></head>
 *     <body>
 *       <p>Hello!</p>
 *     </body>
 *   </html>
 * </pre>
 *
 * Variables may be passed to views through the use of instance
 * variables. All public instances variables of your controller become
 * variables of the same name in the view. So if we did this in our controller:
 * <code>
 *   class HelloController extends Controller_Base {
 * 
 *     public function index() {
 *       $this->name = 'George';
 *     }
 *
 *   }
 * </code>
 *
 * The view file could then make use of the variable, like so:
 * <pre>
 *   <html>
 *     <head><title>Hello!</title></head>
 *     <body>
 *       <p>Hello, <?php echo htmlspecialchars($name); ?>!</p>
 *     </body>
 *   </html>
 * </pre>
 *
 * Note that undeclared instance variables will be treated as public
 * variables. You can prevent instance variables from being available to
 * the view by specifically declaring them either protected or private:
 * <code>
 *   class HelloController extends Controller_Base {
 *
 *     private $start_time;
 * 
 *     public function index() {
 *       $this->start_time = date('Y-m-d H:i:s');
 *       $this->name = 'George';
 *     }
 *
 *   }
 * </code>
 *
 * So that in the view this would not output any date or time information
 * in the second paragraph:
 * <pre>
 *   <html>
 *     <head><title>Hello!</title></head>
 *     <body>
 *       <p>Hello, <?php echo htmlspecialchars($name); ?>!</p>
 *       <p>Request started at: <?php echo $start_time; ?></p>
 *     </body>
 *   </html>
 * </pre>
 */
abstract class Controller_Base {

  protected $layout = NULL;
  protected $event_listeners = array();
  protected $action = NULL;
  protected $flash = NULL;
  protected $rendered = false;
  protected $logger = NULL;
  protected $routing = null;
  protected $runtime_variables = array();

  protected $hidden_actions = array('__construct'=>1,
                                    'action'=>1,
                                    'controller_name'=>1,
                                    'flash'=>1,
                                    'handle_request'=>1,
                                    'layout'=>1,
                                    'rendered'=>1);

  /**
   * Constructor
   */
  public function __construct() {
  }

  /**
   * Accessor for this controller's action name
   *
   * @return string
   */
  public function action() {
    return $this->action;
  }

  /**
   * This method sets the controller's action
   *
   * @param string $action  The new action value
   */
  public function set_action($action) {
    $this->action = $action;
  }

  /**
   * Accessor for this controller's layout name
   *
   * @return string
   */
  public function layout() {
    return (is_null($this->layout) ? 'application' : $this->layout);
  }

  /**
   * This method sets the path to the layout to be used.
   *
   * A value of 'application' will expect a corresponding file named
   * 'views/layouts/application.tpl'.
   *
   * @param string $layout  The new layout to use
   */
  public function set_layout($layout) {
    $this->layout = $layout;
  }

  /**
   * Accessor for this controller's rendered flag
   *
   * @return bool
   */
  public function rendered() {
    return $this->rendered;
  }

  /**
   * This method sets the controller's rendered flag
   *
   * @param bool $rendered  The new rendered value
   */
  public function set_rendered($rendered) {
    $this->rendered = $rendered;
  }
  
  /**
   * Set the routing instance currently in use
   *
   * @param Controller_Routing $routing The routing instance to set
   */
  public function set_routing($routing) {
    $this->routing = $routing;
  }

  /**
   * Returns the name of the controller.  For ExampleController this
   * returns 'example'.
   *
   * @return string
   */
  public function controller_name() {
    $klass = get_class($this);
    if (substr($klass, -10) == 'Controller')
      $klass = substr($klass, 0, -10);
    return Support_Inflector::underscore(str_replace('_', '/', $klass));
  }

  /**
   * Set a flash message
   *
   * @param string $key  The key for the message
   * @param string $msg  The message to store
   */
  public function set_flash($key, $msg) {
    if (!$this->flash)
      $this->flash = array();
    if (!isset($_SESSION['Flash']))
      $_SESSION['Flash'] = array();

    $this->flash[$key] = $msg;
    $_SESSION['Flash'][$key] = $msg;
  }

  /**
   * Return a flash message
   *
   * @param string $key  The key of the message
   *
   * @return mixed  The message or false
   */
  public function flash($key) {
    if (!$this->flash)
      $this->flash = array();
    if (!isset($_SESSION['Flash']))
      $_SESSION['Flash'] = array();

    if (isset($_SESSION['Flash'][$key])) {
      $this->flash[$key] = $_SESSION['Flash'][$key];
      unset($_SESSION['Flash'][$key]);
    }

    return (isset($this->flash[$key]) ? $this->flash[$key] : false);
  }
  
  /**
   * Return the URL for a given set of parameters.
   * 
   * For example:
   * <code>
   *   $this->url_for(array('controller'=>'session', 'action'=>'login'));
   * </code>
   *
   * Will return "/session/login" if you have configured a route like:
   * <code>
   *   $route->match('/:controller/:action');
   * </code>
   *
   * If a URL cannot be determined, an exception is thrown.
   *
   * @param array $parameters The parameters to build the URL for
   * @param string $method The HTTP method to produce the route for (default is "GET")
   *
   * @return string
   */
  public function url_for($parameters, $method = 'get') {
    if (!$this->routing)
      throw new Exception("url_for requires a routing class to be in use");
    
    // normalize the controller
    if (!isset($parameters['controller']))
      $parameters['controller'] = $this->controller_name();
    elseif (isset($parameters['controller']) && ($parameters['controller'] instanceof Controller_Base))
      $parameters['controller'] = $parameters['controller']->controller_name();
    
    // normalize the action
    if ((!isset($parameters['action'])) && $this->action() != 'index')
      $parameters['action'] = $this->action();
    
    // normalize id
    if (isset($parameters['id']) && is_object($parameters['id']) &&
      method_exists($parameters['id'], 'id'))
      $parameters['id'] = $parameters['id']->id();
    
    // find the route
    return $this->routing->url_for($parameters, $method);
  }

  /** 
   * Process an incoming HTTP request
   */
  public function handle_request() {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'index';

    if (method_exists($this, $action)) {
      $meth = new ReflectionMethod(get_class($this), $action);
      if ($meth->isPublic() && ($meth->getNumberOfRequiredParameters() == 0) &&
          $this->allowed_action($action)) {
            
        $start = microtime(true);
        $this->logger()->info("Processing ".get_class($this)."#$action (for $_SERVER[REMOTE_ADDR])");

        $this->set_action($action);
        $this->set_rendered(false);

        try {
          if (!$this->fire_event('before_filter', true))
            return;

          $meth->invoke($this);
        } catch ( Exception $e ) {
          $this->on_exception($e);
        }

        if (!$this->rendered())
          $this->render_action();

        $this->fire_event('after_filter');
        
        $end = microtime(true);
        $elapsed = $end - $start;
        $this->logger()->info("Completed in $elapsed sec [$_SERVER[REQUEST_URI] for $_SERVER[REMOTE_ADDR]]\n");
        
        return;
      }
    }
    
    $this->logger()->error("Unknown action \"$action\" in controller ".get_class($this));

    $this->not_found();
  }



  /*++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
   * Protected Functions
   *++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/


  /**
   * Return a 404 not found message back.
   */
  protected function not_found() {
    header('HTTP/1.1 404 File Not Found');
    print "<html><head><title>Not Found</title></head>"
      . "<body><h1>The requested document could not be found.</h1>"
      . "</body></html>\n";
    $this->set_rendered(true);
  }
  
  /**
   * Accepts the names of one or more public methods which are
   * protected from invocation as an action.
   */
  protected function hide_action() {
    $args = func_get_args();
    foreach ($args as $arg) {
      if (is_array($arg)) {
        foreach ($arg as $realArg) {
          $this->hidden_actions[$realArg] = 1;
        }
      } else {
        $this->hidden_actions[$arg] = 1;
      }
    }
  }

  /**
   * Return a list of all public methods which have been protected
   * from use as an action.
   *
   * @return array
   */
  protected function hidden_actions() {
    return $this->hidden_actions;
  }
  
  /**
   * Determine if an method can allowably be invoked as an action
   *
   * @param string $actionName  The action to test
   *
   * @return boolean
   */
  protected function allowed_action($actionName) {
    return isset($this->hidden_actions[$actionName]) ? false : true;
  }

  /**
   * Output a redirect header to the browser
   *
   * The $url parameter may be specified as a string, or passed as an
   * array. If an array it provided, it is passed to the url_for() method
   * to produce a URL for the redirect.
   *
   * @param mixed $url  URL or fragment to redirect to
   */
  protected function redirect_to($url) {
    if (is_array($url)) $url = $this->url_for($url);
    
    $this->logger()->info("Redirect to $url");
    Support_Util::redirect($url, false);
    $this->set_rendered(true);
  }

  /**
   * Render the template for the action
   *
   * Options are:
   *  - <b>action:</b>    Override the current action template to use
   *  - <b>layout:</b>    The layout to use, or false for none
   *
   * @param array $options    Any rendering options
   */
  protected function render_action($options = array()) {
    list($tpl, $layout) = $this->prepare_for_render($options);
    
    $tpl->display($layout, $this->template_cache_id(), $this->template_compile_id());
    $this->set_rendered(true);
  }
  
  /**
   * Render the template as a string.
   * 
   * @see render_action()
   * @param array $options    Any rendering options
   */
  protected function render_to_string($options = array()) {
    list($tpl, $layout) = $this->prepare_for_render($options);
    
    return $tpl->fetch($layout, $this->template_cache_id(), $this->template_compile_id());
  }
  
  /**
   * Deliver a multipart/alternative email
   * 
   * Additional options:
   *  - <b>headers:</b>     Associative array of headers
   *  - <b>attachments:</b> An array of Support_Mail_Attachment objects
   *
   * @see render_action()
   * @param string $recipients A single recipient or an array of recipients
   * @param string $subject    Email subject
   * @param array $options    Any rendering options
   */
  protected function deliver_mail($recipients, $subject, $options = array()) {
    $options = array_merge(array('layout'=>false), $options);
    
    if (Cfg::exists('mail/from'))
      $options['headers'] = array_merge(array('From'=>Cfg::get('mail/from')), (isset($options['headers']) ? $options['headers'] : array()));
      
    $mail = new Support_Mail_Msg($recipients, $subject, isset($options['headers']) ? $options['headers'] : NULL);
    
    $act = isset($options['action']) ? $options['action'] : $this->action();
    $tpl = Support_Resources::template_engine();
    
    if (file_exists($tpl->template_dir . '/' . $this->controller_name() . "/$act.html.tpl")) {
      $options['action'] = "$act.html";
      $mail->set_html_body($this->render_to_string($options));
    }
    
    if (file_exists($tpl->template_dir . '/' . $this->controller_name() . "/$act.text.tpl")) {
      $options['action'] = "$act.text";
      $mail->set_text_body($this->render_to_string($options));
    }

    if ( isset($options['attachments']) ) {
      foreach ( $options['attachments'] as $attachment ) {
        $mail->add_attachment($attachment);
      }
    }

    $mail->send();
  }
  
  /**
   * Prepare template and layout for rendering.
   * 
   * @see render_action()
   * @param array $options    Any additional rendering options
   * @return array            Array of array($prepared_template, $layout_name)
   */
  protected function prepare_for_render($options) {
    $tpl = Support_Resources::template_engine();

    $tpl->assign('controller', $this);

    $this->assign_template_vars($tpl);
    
    $layout = "layouts/" . $this->layout() . ".tpl";
    $act = isset($options['action']) ? $options['action'] : $this->action();

    if ((!isset($options['layout'])) || ($options['layout'] !== false))
      $tpl->assign('content', $this->controller_name()."/${act}.tpl");

    if (isset($options['layout'])) {
      if ($options['layout'] === false) {
        $layout = $this->controller_name() . "/${act}.tpl";
      } else {
        $layout = "layouts/${options['layout']}.tpl";
      }
    }
    
    $this->set_template_defaults($tpl);

    return array($tpl, $layout);
  }
  
  /**
   * Assigns all public properties of the class as local variables in the
   * template
   *
   * @param object $tpl  The template to assign variables on
   */
  protected function assign_template_vars($tpl) {
    // all public properties are imported as variables

    // virtual ones
    foreach ($this->runtime_variables as $name=>$value) {
      $tpl->assign($name, $value);
    }
    
    // and real ones
    $ref = new ReflectionObject($this);
    foreach ($ref->getProperties() as $prop) {
      if ($prop->isPublic()) {
        $name = $prop->getName();
        $tpl->assign($name, $this->$name);
      }
    }
  }
  
  /**
   * Set various template variables if they have not been set explicitly.
   * 
   * @param $template The template object
   */
  protected function set_template_defaults ( $template ) {
    $page = $template->get_template_vars('page_code');
    if ( ! $page ) {
      $page = $this->controller_name() . '_' . $this->action();
      $template->assign('page_code', $page);
    }
    
    if ( ! $template->get_template_vars('controller_name') ) {
      $template->assign('controller_name', $this->controller_name());
    }
    
    if ( ! $template->get_template_vars('controller_action') ) {
      $template->assign('controller_action', $this->action());
    }
  }
  
  /**
   * Returns the current template cache ID, in case you want to vary the cached
   * output based on some condition.
   * 
   * @return string
   */
  protected function template_cache_id () {
    return null;
  }
  
  /**
   * Returns the current template compile ID, in case this controller changes
   * the template directory.
   * 
   * @return string
   */
  protected function template_compile_id () {
    return null;
  }

  /**
   * Register a method on this class to be called before any request is
   * processed.  If the method that is called returns false, all
   * processing of the request is stopped.
   *
   * Options for the filter are:
   *  - <b>only:</b>  Only apply the filter for the specified actions (string or array of action names)
   *  - <b>except:</b> Apply the filter for all actions except the specified ones (string or array of action names)
   *
   * @param string $name  The name of the method to register.
   * @param array  $options The list of options for the filter
   */
  protected function before_filter($name, $options = null) {
    $this->add_event_listener('before_filter', array($this, $name), $options);
  }

  /**
   * Register a method on this class to be called before any request is
   * processed.
   *
   * Options for the filter are:
   *  - <b>only:</b>  Only apply the filter for the specified actions (string or array of action names)
   *  - <b>except:</b> Apply the filter for all actions except the specified ones (string or array of action names)
   *
   * @param string $name  The name of the method to register.
   * @param array  $options The list of options for the filter
   */
  protected function after_filter($name, $options = null) {
    $this->add_event_listener('after_filter', array($this, $name), $options);
  }

  /**
   * If HTTPS is not the current protocol, outputs a redirect header
   * to this URL using HTTPS and returns false, otherwise returns
   * true.  Convenient for use as a before filter on pages requiring
   * HTTPS.
   */
  protected function require_https() {
    if ( (!isset($_SERVER['HTTPS'])) || ($_SERVER['HTTPS'] != 'on') ) {
      $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

      $this->redirect_to($url);
      return false;
    }

    return true;
  }


  /**
   * Add an event listener to this class.
   *
   * Options for the listener are:
   *  - <b>only:</b>  Only invoke the listener for the specified actions (string or array of action names)
   *  - <b>except:</b> Invoke the listener for all actions except the specified ones (string or array of action names)
   *
   * @param string $eventName  Name of the event to bind the listener to
   * @param callback $function A callback function or method (will receive no arguments)
   * @param array  $options The list of options for the callback
   */
  protected function add_event_listener($eventName, $function, $options = null) {
    $options = is_array($options) ? $options : array();
    Support_Util::validate_options($options, array('only'=>1, 'except'=>1));

    if (isset($options['only']) && isset($options['except']))
      throw new Exception("Options \"only\" and \"except\" are mutually exclusive.");
    $only = isset($options['only']) ? $options['only'] : null;
    $except = isset($options['except']) ? $options['except'] : null;
    
    $listener = new Controller_Base_EventListener($eventName, $function, $only, $except);
    
    if (!isset($this->event_listeners))
      $this->event_listeners = array();
    if (!isset($this->event_listeners[$eventName]))
      $this->event_listeners[$eventName] = array();
    $this->event_listeners[$eventName][] = $listener;
  }

  /**
   * Remove an event listener from this class.
   *
   * @param string $eventName  Name of the event the listener is bound to
   * @param callback $function The callback currently bound
   *
   * @return bool Returns true if the listener was found and removed
   */
  protected function remove_event_listener($eventName, $function) {
    if (!isset($this->event_listeners))
      return false;
    if (!isset($this->event_listeners[$eventName]))
      return false;

    $pos = false;
    foreach ($this->event_listeners[$eventName] as $key=>$listener) {
      if ($listener->callback == $function) {
        $pos = $key;
        break;
      }
    }
    if ($pos === false)
      return false;

    array_splice($this->event_listeners[$eventName], $pos, 1);
    return true;
  }

  /**
   * Notifies any registered event listeners
   *
   * @param string $eventName  The event name to notify listeners for
   * @param bool   $vetoable   If true, any callback returning false ends the event chain
   *
   * @return bool  For vetoable events returns true if all listeners succeeded, false otherwise
   */
  protected function fire_event($eventName, $vetoable = false) {
    if (!isset($this->event_listeners[$eventName]))
      return true;

    $returnedTrue = true;

    foreach ($this->event_listeners[$eventName] as $listener) {
      if ($listener->can_call($this->action())) {
        $result = call_user_func($listener->callback);
        if ($result === false) {
          $returnedTrue = false;
          if ($vetoable) return false;
        }
      }
    }

    return $returnedTrue;
  }
  
  /**
   * Return a logger instance for the class
   */
  protected function logger() {
    if (!$this->logger) {
      $this->logger = Support_Resources::logger($this->controller_name());
    }
    
    return $this->logger;
  }

  /**
   * Handle an exception thrown in an action.
   * 
   * @param Exception $e
   */
  protected function on_exception($exception) {
    $msg = "Unhandled exception (".get_class($exception)."): ".
      $exception->getMessage()." / $exception";
    error_log($msg);
    $this->logger()->error($msg);

    $this->error = $exception->getMessage();
    $this->render_action(array('layout'=>'exception'));

    if ($to = Cfg::get('mail/exception_to')) {
      $msg = "Server: $_SERVER[SERVER_NAME]\n\n" .
        "Unhandled Exception (" . get_class($exception) . "): " .
        $exception->getMessage() . "\n\n" .
        "Backtrace:\n" . $exception->getTraceAsString() . "\n\n" .
        "Server Info: " . print_r($_SERVER, true) . "\n" .
        "Request Info: " . print_r($_REQUEST, true);
      mail($to, 'Unhandled Exception', $msg);
    }
  }
  
  /**
   * Invoked when an attempt is made to set an undeclared instance
   * variable
   *
   * @param string $name The name of the variable
   * @param mixed $value The value to set
   */
  public function __set($name, $value) {
    $this->runtime_variables[$name] = $value;
  }
  
  /**
   * Invoked when an attempt is made to retrieve the value of an
   * undeclared instance variable
   *
   * @param string $name The name of the variable
   *
   * @return mixed
   */
  public function __get($name) {
    return isset($this->runtime_variables[$name]) ? $this->runtime_variables[$name] : null;
  }

  /**
   * Invoked when an attempt is made to call isset or empty for an
   * undeclared instance variable
   *
   * @param string $name The name of the variable
   *
   * @return boolean
   */
  public function __isset($name) {
    return isset($this->runtime_variables[$name]);
  }

  /**
   * Invoked when an attempt is made to unset an undeclared instance
   * variable
   *
   * @param string $name The name of the variable
   */
  public function __unset($name) {
    unset($this->runtime_variables[$name]);
  }
}

?>
