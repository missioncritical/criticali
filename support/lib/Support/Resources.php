<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Thrown when an attempt is made to access a resource from an
 * unknown/unregistered provider.
 */
class Support_UnknownResourceProviderError extends Exception {
  public function __construct($resourceName, $providerName) {
    parent::__construct("Unknown provider \"$providerName\" for $resourceName resource.");
  }
}

/**
 * Thrown when an attempt is made to register a provider that does not
 * implement the correct interface.
 */
class Support_InvalidResourceProviderError extends Exception {
  public function __construct($resourceName, $providerName, $provider, $interfaceName) {
    parent::__construct("Invalid provider \"$providerName\" (".get_class($provider).") for $resourceName resource.  Provider must implement $interfaceName.");
  }
}

/**
 * Factory class for accessing system and application-wide resources
 */
class Support_Resources {
  protected static $instance;
  
  protected $providers;

  /**
   * Constructor -- Direct instantiation is not allowed
   */
  protected function __construct() {
    $this->providers = array();
    
    $this->register_provider('logger', 'php', 'Support_Resources_LoggerProvider',
      new Support_Resources_DefaultLoggerProvider(), true);
    $this->register_provider('config data', 'php', 'Support_Resources_ConfigProvider',
      new Support_Resources_DefaultConfigProvider(), true);
    $this->register_provider('template engine', 'php', 'Support_Resources_TemplateProvider',
      new Support_Resources_DefaultTemplateProvider(), true);
    $this->register_provider('db connection', 'pdo', 'Support_Resources_DBProvider',
      new Support_Resources_DefaultDBProvider(), true);
  }

  /**
   * Get an instance of the template engine
   *
   * @param string $provider The name of the registered provider to return the instance from (uses the default provider if not specified)
   * @return object
   */
  static public function template_engine($provider = null) {
    $impl = self::instance();
    return $impl->get_resource('template engine', $provider);
  }
  
  /**
   * Get a logger instance
   *
   * @param string $name  The name of the object to retrieve a logger for (typically the class name)
   * @param string $provider The name of the registered provider to return the instance from (uses the default provider if not specified)
   * @return object
   */
  static public function logger($name, $provider = null) {
    $impl = self::instance();
    return $impl->get_resource('logger', $provider, $name);
  }
  
  /**
   * Get the array of application configuration data
   *
   * @param string $provider The name of the registered provider to return the instance from (uses the default provider if not specified)
   * @return array
   */
  static public function config_data($provider = null) {
    $impl = self::instance();
    return $impl->get_resource('config data', $provider);
  }
  
  /**
   * Get a database connection
   *
   * @param boolean $writer True if the connection must be writable (default is true)
   * @param boolean $unique True if the connection must be unshared (default is false)
   * @param string $provider The name of the registered provider to return the instance from (uses the default provider if not specified)
   * @return array
   */
  static public function db_connection($writer = true, $unique = false, $provider = null) {
    $impl = self::instance();
    return $impl->get_resource('db connection', $provider, $writer, $unique);
  }

  /**
   * Register a template engine provider
   *
   * @param Support_Resources_TemplateProvider $tpl  The provider instance
   * @param string $name  The provider name to register as
   * @param boolean $asDefault Whether to make this provider the new default or not (default is false)
   */
  static public function register_template_engine($tpl, $providerName, $asDefault = false) {
    $impl = self::instance();
    $impl->register_provider('template engine', $providerName, 'Support_Resources_TemplateProvider',
      $tpl, $asDefault);
  }

  /**
   * Register a logger provider
   *
   * @param Support_Resources_LoggerProvider $logger  The provider instance
   * @param string $name  The provider name to register as
   * @param boolean $asDefault Whether to make this provider the new default or not (default is false)
   */
  static public function register_logger($logger, $providerName, $asDefault = false) {
    $impl = self::instance();
    $impl->register_provider('logger', $providerName, 'Support_Resources_LoggerProvider',
      $logger, $asDefault);
  }
  
  /**
   * Register a config data provider
   *
   * @param Support_Resources_ConfigProvider $cfg  The provider instance
   * @param string $name  The provider name to register as
   * @param boolean $asDefault Whether to make this provider the new default or not (default is false)
   */
  static public function register_config_data($cfg, $providerName, $asDefault = false) {
    $impl = self::instance();
    $impl->register_provider('config data', $providerName, 'Support_Resources_ConfigProvider',
      $cfg, $asDefault);
  }

  /**
   * Register a db connection provider
   *
   * @param Support_Resources_ConfigProvider $db  The provider instance
   * @param string $name  The provider name to register as
   * @param boolean $asDefault Whether to make this provider the new default or not (default is false)
   */
  static public function register_db_connection($db, $providerName, $asDefault = false) {
    $impl = self::instance();
    $impl->register_provider('db connection', $providerName, 'Support_Resources_DBProvider',
      $db, $asDefault);
  }


  /**
   * Set the default template engine provider
   *
   * @param string $name  The name of the provider to set as the default
   */
  static public function set_default_template_engine($providerName) {
    $impl = self::instance();
    $impl->set_default_provider('template engine', $providerName);
  }
  
  /**
   * Set the default logger provider
   *
   * @param string $name  The name of the provider to set as the default
   */
  static public function set_default_logger($providerName) {
    $impl = self::instance();
    $impl->set_default_provider('logger', $providerName);
  }

  /**
   * Set the default config data provider
   *
   * @param string $name  The name of the provider to set as the default
   */
  static public function set_default_config_data($providerName) {
    $impl = self::instance();
    $impl->set_default_provider('config data', $providerName);
  }

  /**
   * Set the default db connection provider
   *
   * @param string $name  The name of the provider to set as the default
   */
  static public function set_default_db_connection($providerName) {
    $impl = self::instance();
    $impl->set_default_provider('db connection', $providerName);
  }

  /**
   * Returns the shared instance.  Used only internally.
   */
  static protected function instance() {
    if (!self::$instance)
      self::$instance = new Support_Resources();
    return self::$instance;
  }
  
  /**
   * Generic implementation of getting a resource from a provider
   */
  protected function get_resource() {
    $args = func_get_args();
    if (count($args) < 2)
      throw new Exception("Missing required parameters resource type and provider");
    $resource = array_shift($args);
    $provider = array_shift($args);
    if (empty($provider)) $provider = '_default_';
    
    if (!isset($this->providers[$resource]))
      throw new Exception("Unknown resource \"$resource\"");
    if (!isset($this->providers[$resource][$provider]))
      throw new Support_UnknownResourceProviderError($resource, $provider);
    
    $providerImpl = $this->providers[$resource][$provider];
    $klass = new ReflectionObject($providerImpl);
    $meth = $klass->getMethod('get');
    if (count($args) < $meth->getNumberOfRequiredParameters())
      throw new Exception("Incorrect number of parameters supplied.  Expecting ".
        $meth->getNumberOfRequiredParameters().", but received ".count($args).".");
    
    return $meth->invokeArgs($providerImpl, $args);
  }
  
  /**
   * Generic implementation of registering a resource provider
   *
   * @param string $resource  The name of the resource
   * @param string $provider  The name of this provider
   * @param string $interface The name of the interface the implementor must implement
   * @param object $implementor The implementor/provider instance
   * @param boolean $asDefault Whether or not to register this provider as the default
   */
  protected function register_provider($resource, $provider, $interface, $implementor, $asDefault) {
    if (!($implementor instanceof $interface))
      throw new Support_InvalidResourceProviderError($resource, $provider, $implementor, $interface);
    
    if (!isset($this->providers[$resource]))
      $this->providers[$resource] = array();
      
    $this->providers[$resource][$provider] = $implementor;
    if ($asDefault)
      $this->providers[$resource]['_default_'] = $implementor;
  }

  /**
   * Generic implementation of setting a default resource provider
   *
   * @param string $resource  The name of the resource
   * @param string $provider  The name of the provider to set as the default
   */
  protected function set_default_provider($resource, $provider) {
    if (!isset($this->providers[$resource]))
      throw new Exception("Unknown resource \"$resource\"");
    if (!isset($this->providers[$resource][$provider]))
      throw new Support_UnknownResourceProviderError($resource, $provider);
    $this->providers[$resource]['_default_'] = $this->providers[$resource][$provider];
  }

}

?>
