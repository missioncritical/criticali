<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Access to application-level configuration information.
 *
 * @copyright Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
 */

/**
 * Thrown if a required configuration value has not been specified
 */
class MissingRequiredConfigurationError extends Exception {
  /**
   * Constructor
   *
   * @param string $key  The missing configuation key
   */
  public function __construct($key) {
    parent::__construct("Missing required configuration property \"$key\".");

    $this->key = $key;
  }

  public $key;
}

/**
 * A class for accessing application-level configuration information.
 *
 * This class is a wrapper around an array of configuration information.
 * It obtains the array by calling Support_Resources::config_data().  The
 * default implementation simply uses a global array named APP_CONFIG
 * which it obtains by including the file config.php (which can be
 * located anywhere in the include path). In addition to providing some
 * basic conveniences over direct array access, part of this class's
 * purpose is to provide a simple layer of abstraction around the storage
 * of the configuration values.  More advanced applications could replace
 * the provider in Resources with one  that retrieves configuration
 * information from an XML file (or other formatted text file) or a
 * database (or even some combination of the two.
 *
 * This class allows configuration properties to be divided into
 * sections. Sections are separated from the property name using a
 * forward slash character (/).  Sections may be nested up to
 * arbitrary depths, but an enclosing section is not required.  The
 * forward slash character can therefore not be used in any property
 * name.  Sections are implemented in the global array as a nested
 * array.
 *
 * An example config file (using the default provider):
 * <code>
 *   $APP_CONFIG['ApplicationName'] = 'My App';
 *   $APP_CONFIG['SessionKey']      = 'MYAPPSESS';
 *   $APP_CONFIG['SessionExpires']  = 120;
 *
 *   $APP_CONFIG['Database']        = array(
 *               'Host'             => 'localhost',
 *               'User'             => 'fred',
 *               'Password'         => 'secret');
 *
 *   $APP_CONFIG['RemoteFTP']       = array(
 *               'Host'             => '192.168.0.5',
 *               'User'             => 'www',
 *               'Password',        => '*****');
 * </code>
 * 
 * Of course, the config file is just a PHP file, so any valid PHP
 * code can be used to build the structure.
 *
 * Examples, of accessing the configuration information:
 * <code>
 *   Cfg::get('ApplicationName');           // => 'My App'
 *   Cfg::get('MOTD');                      // => NULL
 *   Cfg::get('BackgroundColor', 0xFFFFFF); // 0xFFFFFF
 *   Cfg::get('SessionExpires');            // => 120
 *   Cfg::get('Database/Host');             // => 'localhost'
 *   Cfg::get('RemoteFTP/Host');            // => '192.168.0.5'
 *
 *   Cfg::get_required('Database/User');     // => 'fred'
 *   Cfg::get_required('Database/Schema');   // throws MissingRequiredConfigurationError
 * </code>
 */
class Cfg {
  protected static $data = null;
  
  /**
   * Returns the  value of the  given key in  the  configuration.  If
   * they key does  not exist, any  default value provided is returned
   * instead (or NULL if none is provided).
   *
   * @param string $key      The name of the key to retrieve
   * @param string $default  The default value to return if not key exists (optional)
   *
   * @return mixed
   */
  public static function get($key, $default = NULL) {
    $value = $default;
    self::find_key($key, $value);

    return $value;
  }

  /**
   * Returns the value of the given key in the configuration.  If
   * they key does not exist, MissingRequiredConfigurationError is
   * thrown instead.
   *
   * @param string $key      The name of the key to retrieve
   *
   * @return mixed
   */
  public static function get_required($key) {
    $value;
    if (!self::find_key($key, $value))
      throw new MissingRequiredConfigurationError($key);

    return $value;
  }

  /**
   * Test for the existence of a key in the configuration.
   *
   * @param string $key     The name of the key to test
   *
   * @return bool
   */
  public static function exists($key) {
    $value;
    return self::find_key($key, $value);
  }

  /**
   * Locate the named key in the configuration.
   *
   * @param string $key      The name of the key to find
   * @param string &$value   Output parameter for the key's value if found
   *
   * @return bool  Returns true if the key was found, false otherwise
   */
  protected static function find_key($key, &$value) {
    if (is_null(self::$data))
      self::$data =& Support_Resources::config_data();
    $config =& self::$data;
    $keys = explode('/', $key);
    foreach ($keys as $test) {
      if ( (!is_array($config)) || (!isset($config[$test])) )
        return false;
      $config =& $config[$test];
    }

    $value = $config;
    return true;
  }

  /**
   * Constructor may not be called
   */
  private function __construct() {
    throw new Exception("Instantiation of class Cfg is prohibited.");
  }
}

?>
