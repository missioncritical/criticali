<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * An ActiveRecord implementation for PHP 5 based on the far-superior
 * Ruby on Rails implemenation.
 *
 * @package activerecord
 */

/**
 * Base class for ActiveRecord exceptions
 */
class ActiveRecord_Error extends Exception {
  /**
   * Constructor
   */
  public function __construct($message) {
    parent::__construct($message);
  }
}

/**
 * Thrown if a required record was not found
 */
class ActiveRecord_NotFoundError extends ActiveRecord_Error {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct("Record not found");
  }
}

/**
 * Thrown if an invalid option key is supplied
 */
class ActiveRecord_UnknownOptionError extends ActiveRecord_Error {
  /**
   * Constructor
   */
  public function __construct($optionName) {
    parent::__construct("Unrecognized option \"$optionName\"");
  }
}

/**
 * Thrown if a required subclass could not be found
 */
class ActiveRecord_SubclassNotFound extends ActiveRecord_Error {
  /**
   * Constructor
   */
  public function __construct($class, $self = '', $inheritCol = '') {
    parent::__construct("The single-table inheritance mechanism failed to locate the subclass \"$class\". " .
                        "This exception is thrown because the column \"$inheritCol\" is reserved for storing " .
                        "the class in case of inheritance. Please rename the column if you didn't intend it to " .
                        "be used for storing the inheritance class, set the inheritance column to a different " .
                        "name using set_inheritance_column, or overwrite the inheritance_column method to " .
                        "return a different value.");
  }
}

/**
 * Thrown if an attempt is made to access a non-existent property
 */
class ActiveRecord_UnknownPropertyError extends ActiveRecord_Error {
  /**
   * Constructor
   */
  public function __construct($propertyName) {
    parent::__construct("No such property: \"$propertyName\"");
    $this->propertyName = $propertyName;
  }

  public $propertyName;
}

/**
 * Thrown if an attempt is made to access a non-existent method
 */
class ActiveRecord_UnknownMethodError extends ActiveRecord_Error {
  /**
   * Constructor
   */
  public function __construct($methodName) {
    parent::__construct("No such method: \"$methodName\"");
    $this->methodName = $methodName;
  }

  public $methodName;
}

/**
 * Thrown if a reference is made to a non-existent association
 */
class ActiveRecord_UnknownAssociationError extends ActiveRecord_Error {
  /**
   * Constructor
   */
  public function __construct($assocName) {
    parent::__construct("No such association: \"$assocName\"");
    $this->associationName = $assocName;
  }

  public $associationName;
}

/**
 * Thrown if a call is make with an incorrect number of arguments
 */
class ActiveRecord_IncorrectArgumentCountError extends ActiveRecord_Error {
  /**
   * Constructor
   */
  public function __construct($methodName, $expectedCount, $receivedCount) {
    parent::__construct("Incorrect number of arguments passed to $methodName.  Expected $expectedCount but received $receivedCount.");
    $this->methodName            = $methodName;
    $this->expectedArgumentCount = $expectedCount;
    $this->receivedArgumentCount = $receivedCount;
  }

  public $methodName;
  public $expectedArgumentCount;
  public $receivedArgumentCount;
}

/**
 * Thrown if a record could not be successfully saved to the database.
 */
class ActiveRecord_NotSaved extends ActiveRecord_Error {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct("Could not save record.");
  }
}

/**
 * Thrown if an attempt is made to save a read-only record
 */
class ActiveRecord_ReadOnlyRecord extends ActiveRecord_Error {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct("Cannot save a read-only record.");
  }
}

/**
 * Thrown if a record could not be saved to the database due to validation.
 */
class ActiveRecord_Invalid extends ActiveRecord_Error {
  /**
   * Constructor
   *
   * @param ActiveRecord $record  The invalid object
   */
  public function __construct($record) {
    parent::__construct("Validation failed: ".implode(", ", $record->errors()->full_messages()));
    $this->record = $record;
  }

  public $record;
}

/**
 * Thrown when an attempt is made to assign an invalid type to an association
 */
class ActiveRecord_AssociationTypeMismatch extends ActiveRecord_Error {
  /**
   * Constructor
   *
   * @param string $expected  The expected type
   * @param mixed $received The received object
   */
  public function __construct($expected, $received) {
    parent::__construct("Type mismatch.  Cannot assign ".get_class($received)." in place of $expected.");
  }
}

require_once('ActiveRecord/Validation.php');

/**
 * ActiveRecord_MetaInfo is a structure to hold meta information for
 * ActiveRecord classes
 */
class ActiveRecord_MetaInfo {
  public $parent;
  public $connection;
  public $class_name;
  public $table_name;
  public $table_name_prefix;
  public $table_name_suffix;
  public $primary_key;
  public $inheritance_column;
  public $columns;
  public $columns_hash;
  public $column_names;
  public $content_columns;
  public $protected_attributes;
  public $accessible_attributes;
  public $sequence_name;
  public $event_listeners;
  public $validations;
  public $proxied_methods;
  public $associations;

  /**
   * Return the value of a property.  If the value is not set on this
   * instance, it will call get() on any parent object specified.
   *
   * @param string $name   The name of the property to retrieve.
   * @param mixed  $default The default value to return if the property is not set (defaults to NULL)
   *
   * @return mixed  The property value.
   */
  public function get($name, $default = NULL) {
    if (isset($this->$name))
      return $this->$name;
    elseif ($this->parent)
      return $this->parent->get($name, $default);
    else
      return $default;
  }
  
  /**
   * Return the named association from the collection.  If no such
   * association exists on this instance, it will call get_association()
   * on any parent object specified.
   *
   * @param string $name  The name of the association to retieve
   *
   * @return ActiveRecord_Association  The association or null if not found
   */
  public function get_association($name) {
    if (isset($this->associations) && isset($this->associations[$name]))
      return $this->associations[$name];
    elseif ($this->parent)
      return $this->parent->get_association($name);
    else
      return null;
  }
}

/**
 * An ActiveRecord implementation for PHP 5.
 *
 * Designed to eventually offer as much of the functionality available
 * in Ruby on Rails's ActiveRecord implementation as is feasible, this
 * is a base class for building data models capable of storing and
 * retrieving themselves from permanent storage (a database) and whose
 * attributes are largely driven by the database schema as opposed to
 * being defined in the class.  That's a long-winded way of saying
 * ActiveRecord is a fast way to create objects that mirror a database
 * table.
 *
 * Take, for example, a table created with the following SQL:
 * <code>
 *   CREATE TABLE users (
 *     id            INTEGER      NOT NULL PRIMARY_KEY AUTOINCREMENT,
 *     username      VARCHAR(255) NOT NULL UNIQUE,
 *     full_name     VARCHAR(255)     NULL,
 *     password_hash VARCHAR(255) NOT NULL,
 *     disabled      TINYINT(1)   NOT NULL,
 *     last_login    DATETIME         NULL
 *   );
 * </code>
 *
 * To create an active record class linked to this table, all you need
 * to write is:
 * <code>
 *   class User extends ActiveRecord_Base {
 *   }
 * </code>
 *
 * That's it.  Your User class (note <i>User</i> is singular while
 * the table <i>users</i> is plural) now automatically has properties
 * named id, username, full_name, password_hash, disabled, and
 * last_login.  It also inherits convenient finder and save methods
 * from the base ActiveRecord class and includes conveniences for
 * easily updating an object with values submitted from a web form.
 *
 * For those already familiar with the Rails implemenation, it's worth
 * nothing several significant differences between it and this
 * implementation.  Most notably, methods wich apply to the table or
 * class as a whole are not implemented as static methods.  This is
 * due to a number of limitations in PHP.  Specifically, PHP does not
 * provide any visibility about the scope in which it was called to
 * static method.  For example, let's say a static method <i>foo</i>
 * is defined on a class <i>A</i> and a class <i>B</i> inherits from
 * class <i>A</i> but does not definie it's own method <i>foo</i>.
 * Within method <i>foo</i> you have no way of knowing whether it was
 * invoked by calling <i>A::foo()</i> or <i>B::foo</i>, and that
 * distinction is critical for any ActiveRecord implementation.  For
 * related reasons, it would also not possible for class <i>B</i> to
 * override other static methods defined by <i>A</i> and have <i>A</i>
 * call those methods.  That is necessary if derived classes want to
 * provide any class-specific behavior at the class or table level.
 * Finally, PHP does not have facilities for true class-level metadata
 * or behavior (there's no <i>Class</i> class that every object has an
 * instance of, only a class name).  At the time this code was being
 * developed, PHP announced the availability of "late static bindings"
 * beginning in version 5.3.0.  While that may address some of the
 * issues, it's still somewhat unclear how easy it would be for a base
 * class to understand the calling scope when being called in the
 * context of a derived class, and the desire to support as many 5.x
 * versions of PHP as possible ruled that out as a viable alternative.
 * So, in a nutshell, you'll need an instance of a class before doing
 * any operations at all.
 *
 * <b>Example:</b>
 * <code>
 *   $user = new User();
 *   $fred = $user->find_first(array('conditions'=>"username='fred'")); // find the record with username fred
 * </code>
 *
 * The other significant deviation from the approach used by rails is
 * in the area of class initialization.  Ruby allows more than just
 * method and property definitions within the body of a class or
 * module.  It's possible to invoke methods and they in turn can
 * affect the definition of your class or module.  Of course, PHP
 * doesn't have any kind of facility like that, which means we need
 * another way to indicate things like use a different table name or
 * primary key for this class.  For this implementation, we introduce
 * a method named <i>init_class</i> which is invoked only once during
 * a script's lifecycle when the class should first be initialized.
 * Other ActiveRecord implementations have used different approaches,
 * including using attributes to indicate this information.  We felt
 * this approach provided for the most readable and easily documented
 * code and also gives the most flexibility by allowing the execution
 * of arbitrary code at startup.
 *
 * For clarification, here's an example of defining a UserProfile
 * class which maps to a table named users (instead of user_profiles,
 * as would otherwise be assumed):
 * <code>
 *   class UserProfile {
 *     protected function init_class() {
 *       $this->set_table_name('users');
 *     }
 *   }
 * </code>
 *
 * A more subtle, but important, difference from Rails is the handling
 * of derived classes.  Like Rails, this class implements single table
 * inheritance.  Let's say we have two classes:
 * <code>
 *   class User extends ActiveRecord_Base {
 *   }
 *
 *   class AdminUser extends User {
 *   }
 * </code>
 *
 * Here, as in Rails, instances of both classes would be stored on a
 * table named <i>user</i>.  Which class each record was an instance
 * of would be distinguished through the use of a type column, which
 * would have the value 'User' for User instances and 'AdminUser' for
 * AdminUser instances.  Things get different, however, when
 * additional abstract classes find their way into the mix.  Take, for
 * example:
 * <code>
 *   abstract class MyActiveRecord extends ActiveRecord_Base {
 *     protected function init_class() {
 *       $this->set_table_name_prefix('myapp_');
 *     }
 *   }
 *
 *   class User extends MyActiveRecord {
 *   }
 *
 *   class AdminUser extends User {
 *   }
 * </code>
 *
 * In this case, both User and AdminUser objects would be stored in a
 * table named <i>myapp_users</i>, not <i>myapp_my_active_record</i>.
 * That's because this implementation ignores abstract base classes
 * for the pupose of table naming.  As shown here, that can be
 * conveniently used to set policy which is inherited by derived
 * classes.  In Ruby this same effect can be achieved by redefining
 * portions of the ActiveRecord::Base class, but, of course, that's
 * much less easily achieved (if at all) in PHP.
 *
 * The final important convention to note in this class is the naming
 * convention used for mutator and accessor methods.  The get method
 * for a property named <i>foo</i> will have the signature
 * <i>foo()</i>.  The set method for a property named <i>foo</i> will
 * have the signature <i>set_foo()</i>.  This is helpful not only for
 * tracking down the mutators and accessors for your favorite
 * properties, but also for defining custom methods for accessing or
 * setting properties created from database columns.  Let's say, for
 * example, I want to always return the last_login property for my
 * <i>User</i> object as an instance of a custom objected named
 * <i>MyDateTime</i>, and likewise I want to always accept an object
 * of the same type when setting the property.  I can easily set that
 * up like so:
 * <code>
 *   class User extends ActiveRecord_Base {
 *     public function last_login() {
 *       return new MyDateTime($this->read_attribute('last_login'));
 *     }
 *
 *     public function set_last_login($myDateTimeObj) {
 *       $this->write_attribute('last_login', $myDateTimeObj->to_db_value());
 *     }
 *   }
 * </code>
 *
 * Note the use of the read_attribute and write_attribute methods here
 * to gain access to the underlying object properties.  Even though
 * only these two methods have been declared, they will also be used
 * anytime the corresponding public properties are accessed.  Take for
 * example these lines of code:
 * <code>
 *   $oldLoginTime = $user->last_login;
 *   $user->last_login = new MyDateTime('2007-04-01 0:00:00');
 * </code>
 *
 * The first line will actually invoke the last_login() method, and
 * the second will invoke the set_last_login() method.  That's because
 * the base ActiveRecord class actually maps all access to
 * non-existent properties to the corresponding accessor or mutator
 * method.  The byproduct of this behavior is that many of the methods
 * defined on the ActiveRecord class can also be accessed as though
 * they were public properties.
 *
 * <b>Events</b>
 * 
 * ActiveRecord supports the following event lifecycle:
 * 
 * - (.) save
 * - (.) is_valid
 * - (1) <b>before_validation</b>
 * - (2) <b>before_validation_on_create / before_validation_on_update</b>
 * - (.) validate
 * - (.) validate_on_create / validate_on_update
 * - (3) <b>after_validation</b>
 * - (4) <b>after_validation_on_create / after_validation_on_update</b>
 * - (5) <b>before_save</b>
 * - (6) <b>before_create / before_update</b>
 * - (.) create_record / update_record
 * - (7) <b>after_create / after_update</b>
 * - (8) <b>after_save</b>
 *
 * And:
 *
 * - (1) before_destroy
 * - (.) destroy
 * - (2) after_destroy
 *
 * Listeners may be added for any of the above fourteen events using a
 * java-like add_event_listener method or using the convenience
 * methods of the same name.
 *
 * <b>Example:</b>
 * <code>
 *   class User extends ActiveRecord_Base {
 *     protected function custom_hook() {
 *       // do some custom work before validation
 *     }
 *
 *     protected function init_class() {
 *       $this->before_validation('custom_hook');
 *
 *       // nearly equivalent to:
 *       $this->add_event_listener('before_validation', array($this, 'custom_hook'));
 *     }
 *   }
 * </code>
 */
abstract class ActiveRecord_Base {

  /*======================================================================
   * Properties
   *====================================================================*/

  /**
   * Class meta information
   */
  protected $metaInf;
  /**
   * Cached logger
   */
  protected $logger;
  /**
   * Attributes
   */
  protected $attributes;
  /**
   * Indicates whether this is a new record or not
   */
  protected $new_record;
  /**
   * Indicates whether this record is read-only or not
   */
  protected $readonly;
  /**
   * Any errors that apply to this object
   */
  protected $errors;
  /**
   * A collection of cached attribute values
   */
  protected $cached_attributes;




  /*======================================================================
   * Public methods (that apply to the class)
   *====================================================================*/



  /**
   * Find one or more records with the specified id(s).  This
   * function accepts one or more ids as it's argument.  If an array
   * is passed, it is assumed to be a list of ids.
   *
   * Returns the record or records with the given ids.  In the case of
   * a single id, a single object is returned.  In the case of
   * multiple ids, an array of objects is returned.  The count of
   * objects found must match the count of ids or an exception is
   * thrown.
   *
   * <b>Example:</b>
   * <code>
   *   $student      = new Student();
   *   $oneStudent   = $student->find(5);
   *   $studentArray = $student->find(5, 30, 47);
   * </code>
   *
   * @return mixed  The object or list of objects.
   */
  public function find() {
    $ids = array();
    $args = func_get_args();
    foreach ($args as $arg) {
      if (is_array($arg))
        $ids = array_merge($ids, $arg);
      else
        $ids[] = $arg;
    }

    $list = '';
    foreach ($ids as $id) { if (strlen($list) > 0) $list .= ','; $list .= $this->connection()->quote($id); }
    $objs = $this->find_by_sql("SELECT * FROM ".$this->table_name()." WHERE ".$this->primary_key()." IN($list)");

    if (count($objs) != count($ids))
      throw new ActiveRecord_NotFoundError();

    if (count($objs) == 1)
      return $objs[0];
    else
      return $objs;
  }

  /**
   * Retrieve the first record matching the provided criteria.
   *
   * Options are:
   *  - <b>conditions:</b> A SQL fragment for use in the WHERE clause.  For example, "archived=1" or array("username=?", $username).
   *  - <b>order:</b>      A SQL fragment for use in the ORDER BY clause such as "name, created_at DESC".
   *  - <b>limit:</b>      An integer limiting the number of rows to return.
   *  - <b>offset:</b>     An integer specifying the offset from which fetching begins (1 indicates the first row).
   *  - <b>joins:</b>      A SQL fragment to specify additional joins needed for the conditions.
   *  - <b>from:</b>       The table or view name to select from.  The default name is provided by the table_name method, but this allows a temporary override.
   *  - <b>readonly:</b>   Return the records as read-only
   *  - <b>include:</b>    The name of an association or an array of association names to eagerly load with the record.
   *
   * Returns the first object matching the criteria.  If not matches
   * are found, returns NULL.
   *
   * <b>Example:</b>
   * <code>
   *   $student    = new Student();
   *   $firstSally = $student->find_first('conditions'=>"first_name='Sally'");
   *   $bySSN      = $student->find_first('conditions'=>array('ssn=?', $ssn));
   *   $topRanked  = $student->find_first('order'=>'gpa DESC');
   * </code>
   *
   * @param array $options  An associative array of options.  Possible
   *                        keys are explained above.
   *
   * @return mixed  The first object found or NULL
   */
  public function find_first($options = array()) {
    $options['limit'] = 1;
    $results = $this->find_all($options);
    if (!$results)
      return NULL;
    else
      return $results[0];
  }

  /**
   * Retrieve all records matching the provided criteria.
   *
   * Options are:
   *  - <b>conditions:</b> A SQL fragment for use in the WHERE clause.  For example, "archived=1" or array("username=?", $username).
   *  - <b>order:</b>      A SQL fragment for use in the ORDER BY clause such as "name, created_at DESC".
   *  - <b>limit:</b>      An integer limiting the number of rows to return.
   *  - <b>offset:</b>     An integer specifying the offset from which fetching begins (1 indicates the first row).
   *  - <b>joins:</b>      A SQL fragment to specify additional joins needed for the conditions.
   *  - <b>from:</b>       The table or view name to select from.  The default name is provided by the table_name method, but this allows a temporary override.
   *  - <b>readonly:</b>   Return the records as read-only
   *  - <b>include:</b>    The name of an association or an array of association names to eagerly load with the records.
   *
   * Returns an array containing all matching records found.  If no
   * matches are found, an empty array is returned.
   *
   * <b>Example:</b>
   * <code>
   *   $student   = new Student();
   *   $deansList = $student->find_all('conditions'=>'gpa >= 3.8');
   * </code>
   *
   * @param array $options  An associative array of options.  Possible
   *                        keys are explained above.
   *
   * @return array  The list of objects found
   */
  public function find_all($options = array()) {
    $this->validate_find_options($options);

    $sql = "SELECT * FROM " . (isset($options['from']) ? $options['from'] : $this->table_name()) . " ";
    $this->add_joins($sql, $options);
    $this->add_conditions($sql, $options);
    $this->add_order($sql, $options);
    $this->add_limit($sql, $options);

    $records = $this->find_by_sql($sql);
    
    if (isset($options['include']))
      $this->process_includes($options['include'], $records);

    if (isset($options['readonly']) && $options['readonly']) {
      foreach ($records as $record) { $record->set_readonly(true); }
    }

    return $records;
  }

  /**
   * Retrieve records by a raw SQL statement instead of through the
   * normal find helper methods.
   *
   * Returns an array containing all matching records found.  If no
   * matches are found, an empty array is returned.
   *
   * <b>Example:</b>
   * <code>
   *   $student = new Student();
   *   $smiths  = $student->find_by_sql("SELECT * from students WHERE last_name='Smith'");
   * </code>
   *
   * @param string $sql  The complete SQL statement to run
   *
   * @return array  The list of object found
   */
  public function find_by_sql($sql) {
    $sql = $this->connection()->sanitizeSQL($sql);
    $result = NULL;

    try {

      $start = microtime(true);
      $result = $this->connection()->selectAll($sql, PDO::FETCH_ASSOC);
      $end = microtime(true);
      $elapsed = $end - $start;
      $count = count($result);
      $this->logger()->debug("Load ".get_class($this)." ($count row".($count == 1 ? '' : 's')." in $elapsed sec): $sql");

    } catch (Exception $e) {
      $this->logger()->debug("ERROR executing SQL ($sql) ".get_class($e).": ".$e->getMessage());
      throw $e;
    }

    $objs = array();
    foreach($result as $record) {
      $objs[] = $this->instantiate($record);
    }

    return $objs;
  }

  /**
   * Accepts an id or set of conditions and returns true if a
   * matching record exists, otherwise it returns false.  If the test
   * parameter is an array, it is assumed to contain conditions in the
   * same format used with the 'conditions' option for find.  Anything
   * else is assumed to be an id.
   *
   * <b>Example:</b>
   * <code>
   *   $student = new Student();
   *
   *   if ($student->exists(25)) { /* student with id 25 exists {@*} }
   *   if ($student->exists(array('last_name=?', 'Smith')) { /* there is a student Smith {@*} }
   * </code>
   *
   * @param mixed $test  The id or conditions to test for
   *
   * @return bool
   */
  public function exists($test) {
    $test = is_array($test) ? $test : array($this->primary_key()."=?", $test);
    try {
      $obj = $this->find_first(array('conditions'=>$test));
      return ($obj != NULL);
    } catch (ActiveRecord_Error $e) {
      return false;
    }
  }

  /**
   * Create and save (validation permitting) an object.  The newly
   * created object is returned.  If validation fails, the unsaved
   * object is still returned.
   *
   * <b>Example:</b>
   * <code>
   *   $student = new Student();
   *
   *   $emptyStudent = $student->create();
   *
   *   $sallyMae = $student->create(array('first_name' => 'Sally Mae',
   *                                      'last_name'  => 'Jones',
   *                                      'ssn'        => '123-45-6789',
   *                                      'gpa'        => 3.9));
   * </code>
   *
   * @param array $attrs  Optional associative array of attributes for the new object.
   *
   * @return object
   */
  public function create($attrs = false) {
    $klass = get_class($this);
    $obj = new $klass($attrs);
    $obj->save();

    return $obj;
  }

  /**
   * Like create, but calls save_or_fail, so if validation fails, an
   * exception is thrown.
   *
   * @param array $attrs  Optional associative array of attributes for the new object.
   *
   * @return object
   */
  public function create_or_fail($attrs = false) {
    $klass = get_class($this);
    $obj = new $klass($attrs);
    $obj->save_or_fail();

    return $obj;
  }

  /**
   * Find an object by id and update the attributes specified in an
   * associative array.  The object is automatically saved (validation
   * permitting) and is then returned.
   *
   * Multiple objects may updated at once by passing an array of ids
   * and an array of attribute arrays.  In that case, an array of
   * updated objects is returned.
   *
   * <b>Example:</b>
   * <code>
   *   $student = new Student();
   *   $student->update(25, array('first_name'=>'Fred', 'last_name'=>'Smith', 'major'=>'Computer Science'));
   *   $student->update(array(5, 10), array(array('first_name'=>'David'), array('first_name'=>'Sarah')));
   * </code>
   *
   * @param mixed $id    The id or list of ids
   * @param array $attrs The array of attributes (or array of arrays)
   *
   * @return mixed
   */
  public function update($id, $attrs) {
    // multi-update
    if (is_array($id)) {
      $objs = array();
      $attr = reset($attrs);
      foreach ($id as $myId) {
        $objs[] = $this->update($myId, $attr);
        $attr = next($attrs);
      }

      return $objs;

    // normal
    } else {
      $obj = $this->find($id);
      $obj->set_attributes($attrs);
      return $obj;
    }
  }

  /**
   * Deletes the record with the given id (or records with the given
   * ids).  The record is not instantiated first, so no callbacks are
   * triggered.  As with find, this method accepts one or more
   * arguments.  Any arrays which are passed are assumed to be an
   * array of ids.
   *
   * @return int  The count of deleted objects.
   */
  public function delete() {
    $ids = array();
    $args = func_get_args();
    foreach ($args as $arg) {
      if (is_array($arg))
        $ids = array_merge($ids, $arg);
      else
        $ids[] = $arg;
    }

    $list = '';
    foreach ($ids as $id) { if (strlen($list) > 0) $list .= ','; $list .= $this->connection()->quote($id); }
    return $this->delete_all($this->primary_key()." IN($list)");
  }

  /**
   * Updates a set of records given a SQL fragment for use inside a
   * SET clause and an optional set of conditions.  As with the
   * conditions option for a find* method, the conditions argument is
   * a SQL fragment suitable for use in a where clause.  Either
   * parameter may be a string or an array with the first element
   * containing the SQL fragment and the remaining containing parameters.
   *
   * Returns a count of records that were updated.
   *
   * <b>Examples:</b>
   * <code>
   *  $article = new Article();
   *  $article->update_all("published=0, archived=1, archived_on='2008-03-20'");
   *  $article->update_all("published=0, archived=1, archived_on='2008-03-20'", "archive_after<='2008-03-20'");
   *  $article->update_all(array("published=0, archived=1, archived_on=?", $today), array("archive_after<=? AND category=?", $today, $archiveCategory));
   * </code>
   *
   * @param string $updates    The SQL for the SET clause
   * @param mixed  $conditions Any optional conditions
   *
   * @return int
   */
  public function update_all($updates, $conditions = false) {
    $sql = "UPDATE ".$this->table_name()." SET ".$this->connection()->sanitizeSQL($updates);
    if ($conditions)
      $this->add_conditions($sql, array('conditions'=>$conditions));
    $count = false;

    try {

      $start = microtime(true);
      $count = $this->connection()->exec($sql);
      $end = microtime(true);
      $elapsed = $end - $start;
      $this->logger()->debug("Update ".get_class($this)." ($count row".($count == 1 ? '' : 's')." in $elapsed sec): $sql");

    } catch (Exception $e) {
      $this->logger()->debug("ERROR executing SQL ($sql) ".get_class($e).": ".$e->getMessage());
      throw $e;
    }

    return $count;
  }

  /**
   * Destroys the objects for a set of records that match the given
   * conditions.  As with the conditions option for a find* method,
   * the conditions argument is a SQL fragment suitable for use in a
   * where clause.  The conditions parameter may be a string or an
   * array with the first elementing containing the SQL fragment and
   * the remaining containing parameters.
   *
   * <b>Examples:</b>
   * <code>
   *  $article = new Article();
   *  $article->destroy_all("archived=1 AND archived_on<='2000-01-01'");
   *  $article->destroy_all(array("archived=1 AND archived_on<=?", $exipireDate));
   * </code>
   *
   * @param mixed  $conditions Any conditions
   */
  public function destroy_all($conditions = false) {
    $options = $conditions ? array('conditions'=>$conditions) : array();
    $all = $this->find_all($options);
    foreach ($all as $obj) {
      $obj->destroy();
    }
  }

  /**
   * Destroys all records that match the given conditions.  The
   * records are not instantiated first, and, as such, no callbacks
   * are triggered.  As with the conditions option for a find* method,
   * the conditions argument is a SQL fragment suitable for use in a
   * where clause.  The conditions parameter may be a string or an
   * array with the first elementing containing the SQL fragment and
   * the remaining containing parameters.
   *
   * Returns a count of records that were deleted.
   *
   * <b>Examples:</b>
   * <code>
   *  $article = new Article();
   *  $article->delete_all("archived=1 AND archived_on<='2000-01-01'");
   *  $article->delete_all(array("archived=1 AND archived_on<=?", $exipireDate));
   * </code>
   *
   * @param mixed  $conditions Any conditions
   *
   * @return int
   */
  public function delete_all($conditions = false) {
    $sql = "DELETE FROM ".$this->table_name()." ";
    if ($conditions)
      $this->add_conditions($sql, array('conditions'=>$conditions));
    $count = false;

    try {

      $start = microtime(true);
      $count = $this->connection()->exec($sql);
      $end = microtime(true);
      $elapsed = $end - $start;
      $this->logger()->debug("Delete all ".get_class($this)." ($count row".($count == 1 ? '' : 's')." in $elapsed sec): $sql");

    } catch (Exception $e) {
      $this->logger()->debug("ERROR executing SQL ($sql) ".get_class($e).": ".$e->getMessage());
      throw $e;
    }

    return $count;
  }

  /**
   * Returns a count of records matching the provided criteria.
   * Accepted options are the same as for find_all().
   *
   * <b>Example</b>
   * <code>
   *   $person = new Person();
   *   $person->count(array('conditions'=>"last_name='Smith'"));
   * </code>
   *
   * @param string $sql  The complete SQL statement to run
   *
   * @return int
   */
  public function count($options = array()) {
    $this->validate_find_options($options);

    $sql = "SELECT COUNT(*) FROM " . (isset($options['from']) ? $options['from'] : $this->table_name()) . " ";
    $this->add_joins($sql, $options);
    $this->add_conditions($sql, $options);
    $this->add_order($sql, $options);
    $this->add_limit($sql, $options);

    return $this->count_by_sql($sql);
  }

  /**
   * Returns the result of an SQL statement that should only include
   * a COUNT(*) -- or equivalent -- in the SELECT clause.
   *
   * <b>Example</b>
   * <code>
   *   $person = new Person();
   *   $person->count_by_sql("SELECT COUNT(*) FROM people WHERE last_name='Smith'");
   * </code>
   *
   * @param string $sql  The complete SQL statement to run
   *
   * @return int
   */
  public function count_by_sql($sql) {
    $sql = $this->connection()->sanitizeSQL($sql);
    $count = 0;

    try {

      $start = microtime(true);
      $count = $this->connection()->selectValue($sql);
      $end = microtime(true);
      $elapsed = $end - $start;
      $this->logger()->debug("Count ".get_class($this)." ($count in $elapsed sec): $sql");

    } catch (Exception $e) {
      $this->logger()->debug("ERROR executing SQL ($sql) ".get_class($e).": ".$e->getMessage());
      throw $e;
    }

    return $count ? $count : 0; // normalize; DBs like MSSQL return NULL if no rows match
  }

  /**
   * Increments the specified counter for the given id by one.
   *
   * This example increments the hits field for the Document with id 204:
   * <code>
   *   $doc = new Document();
   *   $doc->increment_counter('hits', 204);
   * </code>
   *
   * @param string $counterName  The name of the counter to increment
   * @param string $id           The id of the record to increment it for
   *
   * @return int  The number of records updated (0 or 1)
   */
  public function increment_counter($counterName, $id) {
    return $this->update_all("$counterName = $counterName + 1", array($this->primary_key()."=?", $id));
  }

  /**
   * Similar to increment_counter, but decrements the specified counter instead.
   *
   * This example decrements the in_stock field for the Product with id 204:
   * <code>
   *   $product = new Product();
   *   $product->decrement_counter('in_stock', 204);
   * </code>
   *
   * @param string $counterName  The name of the counter to decrement
   * @param string $id           The id of the record to decrement it for
   *
   * @return int  The number of records updated (0 or 1)
   */
  public function decrement_counter($counterName, $id) {
    return $this->update_all("$counterName = $counterName - 1", array($this->primary_key()."=?", $id));
  }

  /**
   * This is invoked automatically by ActiveRecord_InfoMgr when the
   * class-level meta information should be loaded.
   *
   * @return ActiveRecord_MetaInfo
   */
  public function load_meta_info() {
    $thisKlassName = get_class($this);
    $this->metaInf = new ActiveRecord_MetaInfo();
    $this->metaInf->class_name = $thisKlassName;

    if (!$this->is_first_concrete_descendent()) {
      $mom = get_parent_class($this);
      $momObj = new $mom();
      $this->metaInf->parent = ActiveRecord_InfoMgr::meta_info($momObj);
    }

    // only call init_class for the class in which it was delcared or you get duplicate information
    $meth = new ReflectionMethod($thisKlassName, 'init_class');
    $klass = $meth->getDeclaringClass();
    if ($klass->getName() == $thisKlassName)
      $this->init_class();

    return $this->metaInf;
  }

  /**
   * Return the database connection for this class
   *
   * @return PDO
   */
  public function connection() {
    $conn = $this->get_meta_info()->get('connection');
    if (!$conn) {
      $conn = Support_Resources::db_connection(true, false, 'activerecord');
      $this->set_connection($conn);
    }

    return $conn;
  }

  /**
   * Sets the database connection for this class
   *
   * @param PDO $conn  The connection to use
   */
  public function set_connection($conn) {
    $this->get_meta_info()->connection = $conn;
  }

  /**
   * Set the field name used as this class's primary key.
   *
   * @param string $name  The primary key field name
   */
  public function set_primary_key($name) {
    $this->get_meta_info()->primary_key = $name;
  }

  /**
   * Return the name of this class's primary key.  Default is 'id'.
   *
   * @return string
   */
  public function primary_key() {
    $info = $this->get_meta_info();
    $key = $info->get('primary_key');
    if (!$key)
      $key = $info->primary_key = 'id';
    return $key;
  }

  /**
   * Set the name of this class's table.
   *
   * @param string $name  The table name
   */
  public function set_table_name($name) {
    $this->get_meta_info()->table_name = $name;
  }

  /**
   * Return the name of this class's table.  This function may be
   * overridden by derived classes.  By default it infers the name of
   * the table by converting the mixed case name of the class to an
   * underscore format and pluralizing the name.
   *
   * @return string
   */
  public function table_name() {
    $info = $this->get_meta_info();
    $name = $info->get('table_name');
    if (!$name)
      $name = $info->table_name = $this->table_name_prefix() . Support_Inflector::tableize($this->base_class_name()) . $this->table_name_suffix();
    return $name;
  }

  /**
   * Return any prefix to add to the table name
   *
   * @return string
   */
  public function table_name_prefix() {
    return $this->get_meta_info()->get('table_name_prefix', '');
  }

  /**
   * Set the prefix to add to the table name
   *
   * @param string $prefix  The prefix to use
   *
   * @return string
   */
  public function set_table_name_prefix($prefix) {
    $this->get_meta_info()->table_name_prefix = $prefix;
  }

  /**
   * Return any suffix to add to the table name
   *
   * @return string
   */
  public function table_name_suffix() {
    return $this->get_meta_info()->get('table_name_suffix', '');
  }

  /**
   * Set the suffix to add to the table name
   *
   * @param string $suffix  The suffix to use
   *
   * @return string
   */
  public function set_table_name_suffix($suffix) {
    $this->get_meta_info()->table_name_suffix = $suffix;
  }

  /**
   * Return the name of the column used for single table inheritance.
   *
   * @return string
   */
  public function inheritance_column() {
    $info = $this->get_meta_info();
    $col = $info->get('inheritance_column');
    if (!$col)
      $col = $info->inheritance_column = 'type';
    return $col;
  }

  /**
   * Set the name of the column used for single table inheritance.
   *
   * @param string $name  The column name to use
   */
  public function set_inheritance_column($name) {
    $this->get_meta_info()->inheritance_column = $name;
  }

  /**
   * Return an array of ActiveRecord_Column objects for the table
   * associated with this class.
   *
   * @return array
   */
  public function columns() {
    $info = $this->get_meta_info();
    if (!$info->columns) {
      $start = microtime(true);
      $info->columns = $this->connection()->columns($this->table_name());
      $end = microtime(true);
      $elapsed = $end - $start;
      $count = count($info->columns);
      $this->logger()->debug("Columns ".get_class($this)." ($count column".($count == 1 ? '' : 's')." in $elapsed sec)");
    }

    return $info->columns;
  }

  /**
   * Returns an associative array of column objects keyed by column
   * name for the table associated with this class.
   *
   * @return array
   */
  public function columns_hash() {
    $info = $this->get_meta_info();
    if (!$info->columns_hash) {
      $info->columns_hash = array();
      foreach ($this->columns() as $col) $info->columns_hash[$col->name()] = $col;
    }

    return $info->columns_hash;
  }

  /**
   * Returns an array of column names as strings.
   *
   * @return array
   */
  public function column_names() {
    $info = $this->get_meta_info();
    if (!$info->column_names) {
      $info->column_names = array();
      foreach ($this->columns() as $col) $info->column_names[] = $col->name();
    }

    return $info->column_names;
  }

  /**
   * Returns an array of column objects suitable for direct editing
   * by a user.  The primary key; the inheritance column; all columns
   * ending in _id or _count; and any columns named created_at,
   * created_on, updated_at, updated_on, or lock version are omitted
   * from the returned list.
   *
   * @return array
   */
  public function content_columns() {
    $info = $this->get_meta_info();
    if (!$info->content_columns) {
      $info->content_columns = array();
      foreach ($this->columns() as $col) {
        if ( (!$col->primary()) && ($col->name() != $this->inheritance_column()) &&
             (!preg_match('/^(?:updated_(?:on|at)|created_(?:on|at)|lock_version)$|(?:_id|_count)$/', $col->name())) )
          $info->content_columns[] = $col;
      }
    }

    return $info->content_columns;
  }

  /**
   * Returns the column object for the named attribute
   *
   * @param string $name  The attribute name
   *
   * @return ActiveRecord_Column
   */
  public function column_for_attribute($name) {
    $cols = $this->columns_hash();
    return isset($cols[$name]) ? $cols[$name] : NULL;
  }

  /**
   * Accepts the names of one or more attributes which are protected
   * from mass assignment in this class (assignment through the
   * constructor, create method, set_attributes method, etc.).
   */
  public function attr_protected() {
    $info = $this->get_meta_info();
    if (!$info->protected_attributes) $info->protected_attributes = array();

    $args = func_get_args();
    foreach ($args as $arg) {
      if (is_array($arg))
        $info->protetected_attributes = array_merge($info->protected_attributes, $arg);
      else
        $info->protected_attributes[] = $arg;
    }

    // always keep the array in sorted order for quick merges and comparisons
    sort($info->protected_attributes);
  }

  /**
   * Return a list of all attributes which have been protected from
   * mass assignment (the list is always maintained in sorted order).
   *
   * @return array
   */
  public function protected_attributes() {
    // we inherit protected attributes from any parent
    $myProtected = $this->get_meta_info()->protected_attributes;
    $parentProtected = $this->send_parent('protected_attributes', NULL, array());

    if (!$myProtected)
      return $parentProtected;
    if (!$parentProtected)
      return $myProtected;

    // both have attributes, so merge them
    return Support_ArrayHelper::merge_sorted($myProtected, $parentProtected);
  }

  /**
   * Accepts the names of one or more attributes which are allowed to
   * be assigned through mass assignment in this class (assignment
   * through the constructor, create method, set_attributes method,
   * etc.).  If this method is used, only those attributes specified
   * as accessible are allowed to be assigned through mass assignment.
   */
  public function attr_accessible() {
    $info = $this->get_meta_info();
    if (!$info->accessible_attributes) $info->accessible_attributes = array();

    $args = func_get_args();
    foreach ($args as $arg) {
      if (is_array($arg))
        $info->accessible_attributes = array_merge($info->accessible_attributes, $arg);
      else
        $info->accessible_attributes[] = $arg;
    }

    // always keep the array in sorted order for quick merges and comparisons
    sort($info->accessible_attributes);
  }

  /**
   * Return a list of all attributes which have been made accessible
   * to mass assignment by passing them to attr_accessible (the list
   * is always maintained in sorted order).
   *
   * @return array
   */
  public function accessible_attributes() {
    // we inherit accessible attributes from any parent
    $myAccessible = $this->get_meta_info()->accessible_attributes;
    $parentAccessible = $this->send_parent('accessible_attributes', NULL, array());

    if (!$myAccessible)
      return $parentAccessible;
    if (!$parentAccessible)
      return $myAccessible;

    // both have attributes, so merge them
    return Sort_ArrayHelper::merge_sorted($myAccessible, $parentAccessible);
  }

  /**
   * Set the name of this class's sequence.
   *
   * @param string $name  The sequence name
   */
  public function set_sequence_name($name) {
    $this->get_meta_info()->sequence_name = $name;
  }

  /**
   * Return the name of this class's sequence.  This function may be
   * overridden by derived classes.  The default name is actually
   * determined by providing the table name and primary key name to
   * the connection.  It then builds an appropriate sequence name.
   *
   * @return string
   */
  public function sequence_name() {
    $info = $this->get_meta_info();
    $name = $info->get('sequence_name');
    if (!$name)
      $name = $this->connection()->defaultSequenceName($this->table_name(), $this->primary_key());
    return $name;
  }

  /**
   * Turn a table name back into a class name.  This follows the
   * reverse rules of the table_name method.  So, for example,
   * "my_objects" becomes "MyObject".
   *
   * @param string $name  The table name to convert (defaults to this class's table).
   *
   * @return string
   */
  public function class_name($name = NULL) {
    $name = is_null($name) ? $this->table_name() : $name;
    $prefix = $this->table_prefix();
    $suffix = $this->table_suffix();

    // strip any prefix or suffix
    $name = strlen($suffix) > 0 ? substr($name, strlen($prefix), 0 - strlen($suffix)) : substr($name, strlen($prefix));

    return Support_Inflector::singularize(Support_Inflector::camelize($name));
  }

  /**
   * Determine whether the table associated with this class exists or
   * not.
   *
   * @return bool
   */
  public function table_exists() {
    $tables = $this->connection()->tables();
    return in_array($this->table_name(), $tables);
  }

  /**
   * Add an event listener to this <i>class</i>.
   *
   * @param string $eventName  Name of the event to bind the listener to
   * @param callback $function Modified PHP style callback: a function
   *                           name, an array containing an object an a
   *                           method name on that object, or an array
   *                           containing NULL and a method name on
   *                           this class meaning to always invoke the
   *                           method for the current instance.
   */
  public function add_event_listener($eventName, $function) {
    if (!isset($this->get_meta_info()->event_listeners))
      $this->get_meta_info()->event_listeners = array();
    if (!isset($this->get_meta_info()->event_listeners[$eventName]))
      $this->get_meta_info()->event_listeners[$eventName] = array();
    $this->get_meta_info()->event_listeners[$eventName][] = $function;
  }

  /**
   * Remove an event listener from this <i>class</i>.
   *
   * @param string $eventName  Name of the event the listener is bound to
   * @param callback $function The callback currently bound
   *
   * @return bool Returns true if the listener was found and removed
   */
  public function remove_event_listener($eventName, $function) {
    if (!isset($this->get_meta_info()->event_listeners))
      return false;
    if (!isset($this->get_meta_info()->event_listeners[$eventName]))
      return false;

    $pos = array_search($function, $this->get_meta_info()->event_listeners[$eventName]);
    if ($pos === false)
      return false;

    array_splice($this->get_meta_info()->event_listeners[$eventName], $pos, 1);
    return true;
  }

  /**
   * Register a method on this class to be called before any save
   * operation is performed.
   *
   * @param string $name  The name of the method to register.
   */
  public function before_save($name) {
    $this->add_event_listener('before_save', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called before a save
   * operation is performed for a new record.
   *
   * @param string $name  The name of the method to register.
   */
  public function before_create($name) {
    $this->add_event_listener('before_create', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called before a save
   * operation is performed for a record being updated (non-new
   * record).
   *
   * @param string $name  The name of the method to register.
   */
  public function before_update($name) {
    $this->add_event_listener('before_update', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called after a save
   * operation is performed for a new record.
   *
   * @param string $name  The name of the method to register.
   */
  public function after_create($name) {
    $this->add_event_listener('after_create', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called after a save
   * operation is performed for a record being updated (non-new
   * record).
   *
   * @param string $name  The name of the method to register.
   */
  public function after_update($name) {
    $this->add_event_listener('after_update', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called after any save
   * operation is performed.
   *
   * @param string $name  The name of the method to register.
   */
  public function after_save($name) {
    $this->add_event_listener('after_save', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called before any validation
   * operation is performed.
   *
   * @param string $name  The name of the method to register.
   */
  public function before_validation($name) {
    $this->add_event_listener('before_validation', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called before a validation
   * operation is performed for a new record.
   *
   * @param string $name  The name of the method to register.
   */
  public function before_validation_on_create($name) {
    $this->add_event_listener('before_validation_on_create', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called before a validation
   * operation is performed for a record being updated (non-new
   * record).
   *
   * @param string $name  The name of the method to register.
   */
  public function before_validation_on_update($name) {
    $this->add_event_listener('before_validation_on_update', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called after a validation
   * operation is performed for a new record.
   *
   * @param string $name  The name of the method to register.
   */
  public function after_validation_on_create($name) {
    $this->add_event_listener('after_validation_on_create', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called after a validation
   * operation is performed for a record being updated (non-new
   * record).
   *
   * @param string $name  The name of the method to register.
   */
  public function after_validation_on_update($name) {
    $this->add_event_listener('after_validation_on_update', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called after any validation
   * operation is performed.
   *
   * @param string $name  The name of the method to register.
   */
  public function after_validation($name) {
    $this->add_event_listener('after_validation', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called before the record is
   * destroyed.
   *
   * @param string $name  The name of the method to register.
   */
  public function before_destroy($name) {
    $this->add_event_listener('before_destroy', array(NULL, $name));
  }

  /**
   * Register a method on this class to be called after the record is
   * destroyed.
   *
   * @param string $name  The name of the method to register.
   */
  public function after_destroy($name) {
    $this->add_event_listener('after_destroy', array(NULL, $name));
  }

  /**
   * Add one or more validations for fields which have a confirmation
   * field that must contain an identical value.
   *
   * <b>Example</b>
   * <code>
   *   // model:
   *   class User extends ActiveRecord_Base {
   *     protected function init_class() {
   *       $this->validates_confirmation_of('password');
   *     }
   *   }
   *
   *   // HTML form would contain something like:
   *   // Password:         <input type="password" name="user[password]"><br>
   *   // Confirm Password: <input type="password" name="user[password_confirmation]">
   * </code>
   *
   * Options include:
   *  - <b>message:</b>  Custom error message
   *  - <b>on:</b>       May be 'save', 'create', or 'update'; default is 'save'
   *  - <b>if:</b>       Method name to call for determining if the validation should be run or not (method must accept no arguments and return true or false)
   *
   * @param mixed $attributes  Name of the attribute to validate or an array of attributes to validate
   * @param array $options     Any options for the validation
   */
  public function validates_confirmation_of($attributes, $options = array()) {
    $attributes = is_array($attributes) ? $attributes : array($attributes);

    $inf = $this->get_meta_info();
    if (!$inf->validations) $inf->validations = array();
    $inf->validations[] = new ActiveRecord_ConfirmationValidation($attributes,
                                                       isset($options['message']) ? $options['message'] : false,
                                                       isset($options['on']) ? $this->validation_name_to_type($options['on']) : ActiveRecord_Validation::ON_SAVE,
                                                       isset($options['if']) ? $options['if'] : false);
  }

  /**
   * Add one or more validations for fields which may not be empty.
   *
   * <b>Example</b>
   * <code>
   *   // model:
   *   class User extends ActiveRecord_Base {
   *     protected function init_class() {
   *       $this->validates_presence_of(array('first_name', 'last_name'));
   *       $this->validates_presence_of('email', array('message'=>'cannot be blank or contain only zeros or spaces.'));
   *     }
   *   }
   * </code>
   *
   * Options include:
   *  - <b>message:</b>  Custom error message
   *  - <b>on:</b>       May be 'save', 'create', or 'update'; default is 'save'
   *  - <b>if:</b>       Method name to call for determining if the validation should be run or not (method must accept no arguments and return true or false)
   *
   * @param mixed $attributes  Name of the attribute to validate or an array of attributes to validate
   * @param array $options     Any options for the validation
   */
  public function validates_presence_of($attributes, $options = array()) {
    $attributes = is_array($attributes) ? $attributes : array($attributes);

    $inf = $this->get_meta_info();
    if (!$inf->validations) $inf->validations = array();
    $inf->validations[] = new ActiveRecord_PresentValidation($attributes,
                                                  isset($options['message']) ? $options['message'] : false,
                                                  isset($options['on']) ? $this->validation_name_to_type($options['on']) : ActiveRecord_Validation::ON_SAVE,
                                                  isset($options['if']) ? $options['if'] : false);
  }

  /**
   * Add one or more validations for fields lengths.  Length refers
   * to the number of characters in the field (string length).
   *
   * <b>Example</b>
   * <code>
   *   // model:
   *   class User extends ActiveRecord_Base {
   *     protected function init_class() {
   *       $this->validates_length_of(array('first_name', 'last_name'), array('maximum'=>25));
   *       $this->validates_length_of('password', array('minimum'=>8, 'maximum'=>14, 'message'=>'must be between 8 and 14 characters'));
   *     }
   *   }
   * </code>
   *
   * Options include:
   *  - <b>minimum:</b>    Minimum allowed length
   *  - <b>maximum:</b>    Maximum allowed length
   *  - <b>is:</b>         Exact allowed length
   *  - <b>allow_null:</b> If true, validation is skipped when the attribute's value is null
   *  - <b>too_short:</b>  Custom error message when the value is less than the minimum
   *  - <b>too_long:</b>   Custom error message when the value is greater than the maximum
   *  - <b>message:</b>    Custom error message to use in all cases
   *  - <b>on:</b>         May be 'save', 'create', or 'update'; default is 'save'
   *  - <b>if:</b>         Method name to call for determining if the validation should be run or not (method must accept no arguments and return true or false)
   *
   * @param mixed $attributes  Name of the attribute to validate or an array of attributes to validate
   * @param array $options     Any options for the validation
   */
  public function validates_length_of($attributes, $options = array()) {
    $attributes = is_array($attributes) ? $attributes : array($attributes);

    $inf = $this->get_meta_info();
    if (!$inf->validations) $inf->validations = array();
    $inf->validations[] = new ActiveRecord_BoundsValidation($attributes,
                                                 isset($options['is']) ? $options['is'] : ($options['minimum'] ? $options['minimum'] : 0),
                                                 isset($options['is']) ? $options['is'] : ($options['maximum'] ? $options['maximum'] : 0),
                                                 isset($options['message']) ? $options['message'] : (@$options['too_short'] ? $options['too_short'] : false),
                                                 isset($options['message']) ? $options['message'] : (@$options['too_long'] ? $options['too_long'] : false),
                                                 isset($options['allow_null']) ? $options['allow_null'] : false,
                                                 isset($options['on']) ? $this->validation_name_to_type($options['on']) : ActiveRecord_Validation::ON_SAVE,
                                                 isset($options['if']) ? $options['if'] : false);
  }

  /**
   * Add one or more validations for fields which must be unique
   * accross records.
   *
   * <b>Example</b>
   * <code>
   *   // model:
   *   class User extends ActiveRecord_Base {
   *     protected function init_class() {
   *       $this->validates_uniqueness_of('username');
   *       // or, if users are only unique by some larger grouping, like a domain:
   *       $this->validates_uniqueness_of('username', array('scope'=>'domain_id'));
   *     }
   *   }
   * </code>
   *
   * Options include:
   *  - <b>scope:</b>          An attribute name or array of names used to limit the scope of the uniqueness constraint
   *  - <b>case_sensitive:</b> When false, uniqueness is explicitly made case insensitive, otherwise it is dependent on the DB.
   *  - <b>allow_null:</b>     If true, validation is skipped when the attribute's value is null
   *  - <b>message:</b>        Custom error message
   *  - <b>on:</b>             May be 'save', 'create', or 'update'; default is 'save'
   *  - <b>if:</b>             Method name to call for determining if the validation should be run or not (method must accept no arguments and return true or false)
   *
   * @param mixed $attributes  Name of the attribute to validate or an array of attributes to validate
   * @param array $options     Any options for the validation
   */
  public function validates_uniqueness_of($attributes, $options = array()) {
    $attributes = is_array($attributes) ? $attributes : array($attributes);

    $inf = $this->get_meta_info();
    if (!$inf->validations) $inf->validations = array();
    $inf->validations[] = new ActiveRecord_UniqueValidation($attributes,
                                                 (isset($options['case_sensitive']) && (!$options['case_sensitive'])) ? true : false,
                                                 isset($options['scope']) ? (is_array($options['scope']) ? $options['scope'] : array($options['scope'])) : false,
                                                 isset($options['message']) ? $options['message'] : false,
                                                 isset($options['allow_null']) ? $options['allow_null'] : false,
                                                 isset($options['on']) ? $this->validation_name_to_type($options['on']) : ActiveRecord_Validation::ON_SAVE,
                                                 isset($options['if']) ? $options['if'] : false,
                                                 get_class($this));
  }

  /**
   * Add a validation for the format of a field.  The format is
   * validated using a perl-compatible regular expression.
   *
   * <b>Example</b>
   * <code>
   *   // model:
   *   class Student extends ActiveRecord_Base {
   *     protected function init_class() {
   *       $this->validates_format_of('ssn', '/^\d\d\d-\d\d-\d\d\d\d$/');
   *       $this->validates_format_of(array('home_phone', 'work_phone', 'mobile_phone'), '/^(?:\d{3}[ -]?){2}\d{4}(?: *x *\d+)$/', array('message'=>'must be entered as 000-000-0000 x000 where x000 is an optional extension', 'allow_null'=>true));
   *     }
   *   }
   * </code>
   *
   * Options include:
   *  - <b>allow_null:</b> If true, validation is skipped when the attribute's value is null
   *  - <b>message:</b>    Custom error message
   *  - <b>on:</b>         May be 'save', 'create', or 'update'; default is 'save'
   *  - <b>if:</b>         Method name to call for determining if the validation should be run or not (method must accept no arguments and return true or false)
   *
   * @param mixed  $attributes  Name of the attribute to validate or an array of attributes to validate
   * @param string $pattern     Regular expression to use for validation
   * @param array  $options     Any options for the validation
   */
  public function validates_format_of($attributes, $pattern, $options = array()) {
    $attributes = is_array($attributes) ? $attributes : array($attributes);

    $inf = $this->get_meta_info();
    if (!$inf->validations) $inf->validations = array();
    $inf->validations[] = new ActiveRecord_RegExValidation($attributes,
                                                $pattern,
                                                isset($options['message']) ? $options['message'] : false,
                                                isset($options['allow_null']) ? $options['allow_null'] : false,
                                                isset($options['on']) ? $this->validation_name_to_type($options['on']) : ActiveRecord_Validation::ON_SAVE,
                                                isset($options['if']) ? $options['if'] : false);
  }

  /**
   * Add a validation for a field that is only allowed to have a
   * value in a given list.
   *
   * <b>Example</b>
   * <code>
   *   // model:
   *   class Student extends ActiveRecord_Base {
   *     protected function init_class() {
   *       $this->validates_inclusion_of('degree_type', array('undergraduate', 'graduate', 'doctorate'));
   *     }
   *   }
   * </code>
   *
   * Options include:
   *  - <b>allow_null:</b> If true, validation is skipped when the attribute's value is null
   *  - <b>message:</b>    Custom error message
   *  - <b>on:</b>         May be 'save', 'create', or 'update'; default is 'save'
   *  - <b>if:</b>         Method name to call for determining if the validation should be run or not (method must accept no arguments and return true or false)
   *
   * @param mixed $attributes  Name of the attribute to validate or an array of attributes to validate
   * @param array $validValues List of allowable values
   * @param array $options     Any options for the validation
   */
  public function validates_inclusion_of($attributes, $validValues, $options = array()) {
    $attributes = is_array($attributes) ? $attributes : array($attributes);

    $inf = $this->get_meta_info();
    if (!$inf->validations) $inf->validations = array();
    $inf->validations[] = new ActiveRecord_SetValidation($attributes,
                                              $validValues,
                                              true,
                                              isset($options['message']) ? $options['message'] : false,
                                              isset($options['allow_null']) ? $options['allow_null'] : false,
                                              isset($options['on']) ? $this->validation_name_to_type($options['on']) : ActiveRecord_Validation::ON_SAVE,
                                              isset($options['if']) ? $options['if'] : false);
  }

  /**
   * Add a validation for a field that is allowed to have any value
   * not in a given list.
   *
   * <b>Example</b>
   * <code>
   *   // model:
   *   class User extends ActiveRecord_Base {
   *     protected function init_class() {
   *       $this->validates_exclusion_of('username', array('admin', 'root', 'superuser'), array('message'=>'may not be a reserved name'));
   *     }
   *   }
   * </code>
   *
   * Options include:
   *  - <b>allow_null:</b> If true, validation is skipped when the attribute's value is null
   *  - <b>message:</b>    Custom error message
   *  - <b>on:</b>         May be 'save', 'create', or 'update'; default is 'save'
   *  - <b>if:</b>         Method name to call for determining if the validation should be run or not (method must accept no arguments and return true or false)
   *
   * @param mixed $attributes    Name of the attribute to validate or an array of attributes to validate
   * @param array $invalidValues List of disallowed values
   * @param array $options       Any options for the validation
   */
  public function validates_exclusion_of($attributes, $invalidValues, $options = array()) {
    $attributes = is_array($attributes) ? $attributes : array($attributes);

    $inf = $this->get_meta_info();
    if (!$inf->validations) $inf->validations = array();
    $inf->validations[] = new ActiveRecord_SetValidation($attributes,
                                              $invalidValues,
                                              false,
                                              isset($options['message']) ? $options['message'] : false,
                                              isset($options['allow_null']) ? $options['allow_null'] : false,
                                              isset($options['on']) ? $this->validation_name_to_type($options['on']) : ActiveRecord_Validation::ON_SAVE,
                                              isset($options['if']) ? $options['if'] : false);
  }

  /**
   * Add a validation for a field that must be numeric.
   *
   * <b>Example</b>
   * <code>
   *   // model:
   *   class BankAccount extends ActiveRecord_Base {
   *     protected function init_class() {
   *       $this->validates_numericality_of('balance');
   *       $this->validates_numericality_of('pin', array('only_integer'=>true));
   *     }
   *   }
   * </code>
   *
   * Options include:
   *  - <b>only_integer:</b> If true, only an integer value is allowed (by default reals/floats are allowed)
   *  - <b>allow_null:</b>   If true, validation is skipped when the attribute's value is null
   *  - <b>message:</b>      Custom error message
   *  - <b>on:</b>           May be 'save', 'create', or 'update'; default is 'save'
   *  - <b>if:</b>           Method name to call for determining if the validation should be run or not (method must accept no arguments and return true or false)
   *
   * @param mixed $attributes    Name of the attribute to validate or an array of attributes to validate
   * @param array $options       Any options for the validation
   */
  public function validates_numericality_of($attributes, $options = array()) {
    $attributes = is_array($attributes) ? $attributes : array($attributes);

    $inf = $this->get_meta_info();
    if (!$inf->validations) $inf->validations = array();
    $inf->validations[] = new ActiveRecord_NumericValidation($attributes,
                                                  isset($options['only_integer']) ? $options['only_integer'] : false,
                                                  isset($options['message']) ? $options['message'] : false,
                                                  isset($options['allow_null']) ? $options['allow_null'] : false,
                                                  isset($options['on']) ? $this->validation_name_to_type($options['on']) : ActiveRecord_Validation::ON_SAVE,
                                                  isset($options['if']) ? $options['if'] : false);
  }
  
  /**
   * Specifies a one to one association with another class where the
   * foreign key resides in this class.
   *
   * For example:
   * <code>
   *   class Book extends ActiveRecord_Base {
   *     public function init_class() {
   *       $this->belongs_to('author');
   *     }
   *   }
   *
   *   class Author extends ActiveRecord_Base {
   *   }
   * </code>
   *
   * Implies a table structure like:
   * <code>
   *   CREATE TABLE books (
   *     id            INTEGER      NOT NULL PRIMARY_KEY AUTOINCREMENT,
   *     author_id     INTEGER          NULL,
   *     ...
   *   );
   *
   *   CREATE TABLE authors (
   *     id            INTEGER      NOT NULL PRIMARY_KEY AUTOINCREMENT,
   *     ...
   *   );
   * </code>
   *
   * A belongs_to association adds four methods to the class:
   *  - <b>association($force_reload = false):</b>         Return the associated object
   *  - <b>set_association($value):</b>                    Set the associated object
   *  - <b>build_assocation($attributes = array()):</b>    Create a new associated object from a set of attriubtes
   *  - <b>create_association($attributes = array()):</b>  Create and save a new associated object from a set of attributes
   *
   * Options for the association are:
   *  - <b>class_name:</b>   Set the name of the associated class, the default is inferred from the association name ('user' is assumed to come from a class named 'User').
   *  - <b>foreign_key:</b>  Set the foreign key name, the default is to append <tt>_id</tt> to the association name.
   *  - <b>validate_key:</b> Whether or not to add a validation for the foreign key field, the default is to add one (true).
   *
   * Note that assigning an object via the set_association method will
   * not automatically cause it to be saved, nor will modifications to it
   * be saved automatically when this object is saved.  It must be
   * explicitly saved on its own.  The only exception to this is for newly
   * created records.  If you assign a newly created (unsaved) associate
   * object using this object's set_association method, the associate
   * object will be saved when you save this object, since that is
   * required in order to correctly populate the foreign key.
   *
   * @param string $assocation_name  The name of the association
   * @param array  $options          Any options for the assocation
   */
  public function belongs_to($association_name, $options = array()) {
    if (!is_array($options)) $options = array();
    $this->validate_options($options, ActiveRecord_Association_BelongsTo::$creation_options);
    
    $inf = $this->get_meta_info();
    $proxy = new ActiveRecord_Proxy();
    $proxy->initialize($this, $this->attributes, $this->cached_attributes, $inf);
    $assoc = new ActiveRecord_Association_BelongsTo($this, $proxy, $association_name, $options);
    
    if (!$inf->associations) $inf->associations = array();
    $inf->associations[$association_name] = $assoc;
  }

  /**
   * Specifies a one to one association with another class where the
   * foreign key resides in the other class.
   *
   * For example:
   * <code>
   *   class Book extends ActiveRecord_Base {
   *   }
   *
   *   class Author extends ActiveRecord_Base {
   *     public function init_class() {
   *       $this->has_one('book');
   *     }
   *   }
   * </code>
   *
   * Implies a table structure like:
   * <code>
   *   CREATE TABLE books (
   *     id            INTEGER      NOT NULL PRIMARY_KEY AUTOINCREMENT,
   *     author_id     INTEGER          NULL,
   *     ...
   *   );
   *
   *   CREATE TABLE authors (
   *     id            INTEGER      NOT NULL PRIMARY_KEY AUTOINCREMENT,
   *     ...
   *   );
   * </code>
   *
   * A has_one association adds four methods to the class:
   *  - <b>association($force_reload = false):</b>         Return the associated object
   *  - <b>set_association($value):</b>                    Set the associated object
   *  - <b>build_assocation($attributes = array()):</b>    Create a new associated object from a set of attriubtes
   *  - <b>create_association($attributes = array()):</b>  Create and save a new associated object from a set of attributes
   *
   * Options for the association are:
   *  - <b>class_name:</b>   Set the name of the associated class, the default is inferred from the association name ('user' is assumed to come from a class named 'User').
   *  - <b>foreign_key:</b>  Set the foreign key name, the default is to append <tt>_id</tt> to the association name.
   *  - <b>primary_key:</b> The name of the key field on this class, defaults to id() (whatever the table primary key is set to).
   *
   * Note that assigning an object via the set_association method will
   * both cause it to be saved with the new foreign key value and will
   * cause any previously associated object to be saved with a null value
   * for its foreign key (effectively unassigns it).  Special cases exist
   * for both newly created associate objects and when assigning
   * associates to a newly created instance of this class.  If you assign
   * an unsaved object to this class using the set_association method, it
   * will only be saved if you call the save method on this class.
   * Similarly, if you assign another object to this class before saving
   * this class, the primary key value is not yet known, and so the
   * associated object will not be saved until you save the instance of
   * this class.
   *
   * @param string $assocation_name  The name of the association
   * @param array  $options          Any options for the assocation
   */
  public function has_one($association_name, $options = array()) {
    if (!is_array($options)) $options = array();
    $this->validate_options($options, ActiveRecord_Association_HasOne::$creation_options);
    
    $inf = $this->get_meta_info();
    $proxy = new ActiveRecord_Proxy();
    $proxy->initialize($this, $this->attributes, $this->cached_attributes, $inf);
    $assoc = new ActiveRecord_Association_HasOne($this, $proxy, $association_name, $options);
    
    if (!$inf->associations) $inf->associations = array();
    $inf->associations[$association_name] = $assoc;
  }
  
  /**
   * Specifies a one to many association with another class where the
   * foreign key resides in the other class.
   *
   * For example:
   * <code>
   *   class Menu extends ActiveRecord_Base {
   *     public function init_class() {
   *       $this->has_many('menu_items');
   *     }
   *   }
   *
   *   class MenuItem extends ActiveRecord_Base {
   *   }
   * </code>
   *
   * Implies a table structure like:
   * <code>
   *   CREATE TABLE menus (
   *     id            INTEGER      NOT NULL PRIMARY_KEY AUTOINCREMENT,
   *     ...
   *   );
   *
   *   CREATE TABLE menu_items (
   *     id            INTEGER      NOT NULL PRIMARY_KEY AUTOINCREMENT,
   *     menu_id       INTEGER          NULL,
   *     ...
   *   );
   * </code>
   *
   * A has_many association adds four methods to the class:
   *  - <b>association($force_reload = false):</b>         Return the associated objects as a collection
   *  - <b>set_association($collection):</b>               Set the collection of associated objects (takes an array as the argument)
   *  - <b>association_singular_ids:</b>                   Return an array of the associated object ids
   *  - <b>set_association_singular_ids($ids):</b>         Set the collection of associated objects by list of ids (takes an array of ids as the argument)
   *
   * The association() method returns an instance of
   * ActiveRecord_Association_Collection which also offers the following
   * methods:
   *  - <b>association[]</b> (array-based access and operations)
   *  - <b>association->build($attributes)</b>
   *  - <b>association->clear()</b>
   *  - <b>association->count()</b>
   *  - <b>association->create($attributes)</b>
   *  - <b>association->delete($associate)</b>
   *  - <b>association->exists($options)</b>
   *  - <b>association->find($id[, $id, ...])</b>
   *  - <b>association->find_all($options)</b>
   *  - <b>association->find_first($options)</b>
   *  - <b>association->is_empty()</b>
   *  - <b>association->length()</b>
   *  - <b>association->size()</b>
   *
   * Options for the association are:
   *  - <b>class_name:</b>   Set the name of the associated class, the default is inferred from the association name ('users' is assumed to come from a class named 'User').
   *  - <b>foreign_key:</b>  Set the foreign key name, the default is to append <tt>_id</tt> to the associated class name.
   *  - <b>primary_key:</b> The name of the key field on this class, defaults to id() (whatever the table primary key is set to).
   *  - <b>order:</b>       Ordering to return the associated objects in (similar to order option for find_all).
   *
   * Note that either adding an object to or or removing an object from
   * the collection will cause the object to be saved with an updated
   * foreign key value.  Special cases exist for both newly created
   * associate objects and adding associates to a newly created instance
   * of this class.  If you add an unsaved object to this class, it will
   * only be saved if you call the save method on this class.  Similarly,
   * if you add another object to this class before saving this class,
   * the primary key value is not yet known, and so the associated object
   * will not be saved until you save the instance of this class.
   *
   * @param string $assocation_name  The name of the association
   * @param array  $options          Any options for the assocation
   */
  public function has_many($association_name, $options = array()) {
    if (!is_array($options)) $options = array();
    $this->validate_options($options, ActiveRecord_Association_HasMany::$creation_options);
    
    $inf = $this->get_meta_info();
    $proxy = new ActiveRecord_Proxy();
    $proxy->initialize($this, $this->attributes, $this->cached_attributes, $inf);
    $assoc = new ActiveRecord_Association_HasMany($this, $proxy, $association_name, $options);
    
    if (!$inf->associations) $inf->associations = array();
    $inf->associations[$association_name] = $assoc;
  }
  






  /*======================================================================
   * Public methods (that apply to an instance)
   *====================================================================*/


  /**
   * Constructor
   *
   * Accepts an optional associative array of attribute names and
   * values as the initial values to set on the object.
   *
   * Note that although this behavior is not very clearly explained in
   * the PHP documentation, if derived classes do not specify a
   * constructor, they will inherit this one by default.
   *
   * @param array $attrs  Any initial attributes to use
   */
  public function __construct($attrs = false) {
    $this->attributes = $this->attributes_from_column_definition();
    $this->new_record = true;
    $this->readonly = false;
    $this->ensure_proper_type();
    if ($attrs)
      $this->set_attributes($attrs);
  }

  /**
   * Allows setting of multiple attributes at once by passing an
   * associative array.  The array keys are the attribute names and
   * the array values are the values to assign.
   *
   * @param array $attrs  The attributes to assign.
   */
  public function set_attributes($attrs) {
    if ((!is_array($attrs)) || (!$attrs))
      return;

    $attrs = $this->remove_attributes_protected_from_mass_assignment($attrs);
    foreach ($attrs as $attr=>$newValue) {
      $this->$attr = $newValue;
    }
  }

  /**
   * Returns this object's attributes as an associative array.
   *
   * @return array
   */
  public function attributes() {
    return $this->attributes;
  }

  /**
   * Allow writing of attributes and use of set methods as properties
   *
   * @param string $name  The property to set
   * @param mixed  $value The value to set
   */
  public function __set($name, $value) {
    // prefer a proxied set method
    if ($proxy = $this->proxy_method_for("set_$name")) {
      if ($proxy->required_parameter_count() == 1)
        return $proxy->invoke($this, $this->attributes, $this->cached_attributes, array($value));
    }
    
    // then try an actual set method on this class
    if (method_exists($this, "set_$name")) {
      $meth = new ReflectionMethod(get_class($this), "set_$name");
      if ($meth->isPublic() && ($meth->getNumberOfRequiredParameters() == 1))
        return $meth->invoke($this, $value);
    }

    // no dice, see if we have this attribute
    if (array_key_exists($name, $this->attributes))
      return $this->write_attribute($name, $value);

    // no such property
    throw new ActiveRecord_UnknownPropertyError($name);
  }

  /**
   * Allow reading of attributes and reader methods as properties
   *
   * @param string $name  The property to retrieve
   *
   * @return mixed
   */
  public function __get($name) {
    // prefer a proxied set method
    if ($proxy = $this->proxy_method_for($name)) {
      if ($proxy->required_parameter_count() == 0)
        return $proxy->invoke($this, $this->attributes, $this->cached_attributes, array());
    }

    // then try an actual reader method on this class
    if (method_exists($this, $name)) {
      $meth = new ReflectionMethod(get_class($this), $name);
      if ($meth->isPublic() && ($meth->getNumberOfRequiredParameters() == 0))
        return $meth->invoke($this);
    }

    // no dice, see if we have this attribute
    if (array_key_exists($name, $this->attributes))
      return $this->read_attribute($name);

    // last chance, see if the attribute is being accessed without type casting
    $typeCastSuffix = '_before_type_cast';
    $typeCastSuffixLen = strlen($typeCastSuffix);
    if ((strlen($name) > $typeCastSuffixLen) && (substr($name, 0 - $typeCastSuffixLen) == $typeCastSuffix)) {
      $attr = substr($name, 0, 0 - $typeCastSuffixLen);
      if (array_key_exists($attr, $this->attributes))
        return $this->read_attribute_before_type_cast($attr);
    }

    // no such property
    throw new ActiveRecord_UnknownPropertyError($name);
  }

  /**
   * Allow use of isset with record attributes as though they were
   * public properties.  Unlike other access to properties, this will
   * not defer to to existing reader methods, only actual record
   * properties can be tested.
   *
   * @param string $name  The property to test
   *
   * @return bool
   */
  public function __isset($name) {
    return isset($this->attributes[$name]);
  }

  /**
   * Allow use of unset with record attributes as though they were
   * public properties.  Unlike other access to properties, this will
   * not defer to to existing set methods, only actual record
   * properties can be unset.
   *
   * This has the effect of setting any existing property on the
   * object to NULL.
   *
   * @param string $name  The property to unset
   */
  public function __unset($name) {
    if (isset($this->attributes[$name]))
      $this->attributes[$name] = NULL;
  }

  /**
   * Allow dynamic use of accessor and mutator methods for column
   * values.  Accessors follow the format column_name() and mutators
   * follow the format set_column_name($arg).
   *
   * Also enables dynamic finder methods.  Dynamic finder methods come
   * in a variety of flavors:
   *  - <b>find_by_attribute(value):</b>               Find the first record whose property "attribute" matches value
   *  - <b>find_all_by_attribute(value):</b>           Find all records whose property "attribute" matches value
   *  - <b>find_or_initialize_by_attribute(value):</b> Like by_attribute, but will return a new instance with "attribute" initialized to value of no matches are found
   *  - <b>find_or_create_by_attribute(value):</b>     Like initialize_by_attribute, but also saves any new record (validation permitting) before returning it
   *
   * Multiple attributes may be specified by using _and_ as the
   * separator in the name.  All finder methods can also accept an
   * options array as an additional final argument.  All options
   * accepted by find_* methods are allowed.
   *
   * Example:
   * <code>
   *   $person = new Person();
   *
   *   // equivalent to $person->find_first(array("conditions"=>"ssn='123-45-6789'"));
   *   $person->find_by_ssn('123-45-6789');
   *
   *   // equivalent to $person->find_all(array("conditions"=>"last_name='Smith'));
   *   $person->find_all_by_last_name('Smith');
   *
   *   // equivalent to $person->find_first(array("conditions"=>"first_name='Suzy' AND last_name='Smith'"));
   *   $person->find_by_first_name_and_last_name('Suzy', 'Smith');
   *
   *   // equivalent to $person->find_all(array("conditions"=>"last_name='Smith'", "order"=>"first_name"));
   *   $person->find_all_by_last_name('Smith', array('order'=>'first_name'));
   *
   *   // eqivalent to $result = $person->find_first(array("conditions"=>"first_name='Suzy' AND last_name='Smith'"));
   *   //              $result = $result ? $result : $person->create(array('first_name'=>'Suzy', 'last_name'=>'Smith'));
   *   $result = $person->find_or_create_by_first_name_and_last_name('Suzy', 'Smith');
   * </code>
   *
   * @param string $name  The name of the method being called.
   * @param array  $args  The arguments being passed
   *
   * @return mixed
   */
  public function __call($name, $args) {
    // first preference: a proxied method
    if ($proxy = $this->proxy_method_for($name)) {
      if ( (count($args) < $proxy->required_parameter_count()) ||
           (count($args) > $proxy->maximum_parameter_count()) )
        throw new ActiveRecord_IncorrectArgumentCountError($name,
            $proxy->required_parameter_count(), count($args));
      return $proxy->invoke($this, $this->attributes, $this->cached_attributes, $args);
    }

    // second preference: accessor method
    if (array_key_exists($name, $this->attributes)) {
      // no args allowed
      if (count($args) != 0)
        throw new ActiveRecord_IncorrectArgumentCountError($name, 0, count($args));
      return $this->read_attribute($name);
    }

    // third preference: mutator method
    if ( (substr($name, 0, 4) == 'set_') && (array_key_exists(substr($name, 4), $this->attributes)) ) {
      // must have exactly one arg
      if (count($args) != 1)
        throw new ActiveRecord_IncorrectArgumentCountError($name, 1, count($args));
      return $this->write_attribute(substr($name, 4), $args[0]);
    }

    // fourth preference: finder method
    if (substr($name, 0, 5) == 'find_') {
      $allFlag = false;
      $initFlag = false;
      $saveFlag = false;

      $innerName = substr($name, 5);

      if (substr($innerName, 0, 7) == 'all_by_') {
        $allFlag = true;
        $innerName = substr($innerName, 7);
      } elseif (substr($innerName, 0, 3) == 'by_') {
        $innerName = substr($innerName, 3);
      } elseif (substr($innerName, 0, 17) == 'or_initialize_by_') {
        $initFlag = true;
        $innerName = substr($innerName, 17);
      } elseif (substr($innerName, 0, 12) == 'or_create_by_') {
        $initFlag = true;
        $saveFlag = true;
        $innerName = substr($innerName, 12);
      } else {
        throw new ActiveRecord_UnknownMethodError($name);
      }

      // create the options from our method name and arguments
      $options = array();
      $nvHash = $this->create_finder_attribute_hash($innerName, $args, $options);
      $options = $this->assemble_finder_options($nvHash, $options);

      // make the call
      $result = $allFlag ? $this->find_all($options) : $this->find_first($options);
      if ((!$result) && ($initFlag)) {
        $klass = get_class($this);
        $result = new $klass($nvHash);
        if ($saveFlag) $result->save();
      }

      return $result;
    }

    // default:
    throw new ActiveRecord_UnknownMethodError($name);
  }

  /**
   * Discard meta data and database connections not specific to this
   * instance prior to serialization
   */
  public function __sleep() {
    $this->metaInf = NULL;
    $this->logger = NULL;

    // would be nice if I could just call parent::__sleep(), but, of course, that would have involved actual thought by the folks at PHP
    $reflector = new ReflectionObject($this);
    $props = $reflector->getProperties();
    $varNames = array();
    foreach ($props as $prop) {
      if ($prop->isStatic() || ($prop->getName() == 'cached_attributes')) continue;
      $varNames[] = $prop->getName();
    }

    return $varNames;
  }

  /** 
   * The id method/property always accesses the primary key column,
   * even if the primary key is named something else.
   *
   * @return mixed
   */
  public function id() {
    return $this->read_attribute($this->primary_key());
  }

  /**
   * Access the value of the primary key column before the type cast
   *
   * @return mixed
   */
  public function id_before_type_cast() {
    return $this->read_attribute_before_type_cast($this->primary_key());
  }

  /**
   * Set the value of the primary key
   *
   * @param mixed $value  The value to set
   */
  public function set_id($value) {
    return $this->write_attribute($this->primary_key(), $value);
  }

  /**
   * Returns true if a corresponding record does not yet exist for
   * this object in the database (a new object which has not yet been
   * saved).
   *
   * @return bool
   */
  public function new_record() {
    return $this->new_record;
  }

  /**
   * Save the object to the database.  If no record yet exists for the
   * object, a new one is created.  Otherwise, the existing record is
   * updated.
   *
   * @param bool $validate Performs validations when true, otherwise skips them
   *
   * @return bool  True on success, false on failure
   */
  public function save($validate = true) {
    if ( ($validate && $this->is_valid()) || (!$validate) ) {
      return $this->create_or_update();
    }

    return false;
  }

  /**
   * Saves the object to the database.  If the save operation does not
   * succeed, a RecordNotSaved exception is thrown.
   */
  public function save_or_fail() {
    if (!$this->is_valid())
      throw new ActiveRecord_Invalid($this);

    if (!$this->create_or_update())
      throw new ActiveRecord_NotSaved();
  }

  /**
   * Delete the record from the database
   */
  public function destroy() {
    if ($this->new_record()) return;

    $sql = "DELETE FROM " . $this->table_name() . " WHERE " . $this->primary_key() . " = " .$this->connection()->quote($this->id());
    
    $this->fire_event('before_destroy');

    try {

      $start = microtime(true);
      $count = $this->connection()->exec($sql);
      $end = microtime(true);
      $elapsed = $end - $start;
      $this->logger()->debug("Delete ".get_class($this)." ($count row".($count == 1 ? '' : 's')." in $elapsed sec): $sql");

    } catch (Exception $e) {
      $this->logger()->debug("ERROR executing SQL ($sql) ".get_class($e).": ".$e->getMessage());
      throw $e;
    }

    $this->fire_event('after_destroy');
  }

  /**
   * Updates a single attribute on this object and then saves the object.
   *
   * @param string $name  The attribute name
   * @param mixed  $value The value for the attribute
   *
   * @return bool The success or failure indicator from the save operation
   */
  public function update_attribute($name, $value) {
    $this->$name = $value;
    return $this->save();
  }

  /**
   * Updates a list of attriubtes from an associative array and then
   * saves the object.
   *
   * @param array $attributes  An array whose keys indicate the
   *                           attributes to update and the values
   *                           indicate the new attribute values.
   *
   * @return bool The success or failure indicator from the save operation
   */
  public function update_attributes($attributes) {
    $this->set_attributes($attributes);
    return $this->save();
  }

  /**
   * Operates the same as update_attributes(), but calls save_or_fail,
   * so a RecordNotSaved exception is thrown if the save operation
   * does not succeed.
   *
   * @param array $attributes  An array whose keys indicate the
   *                           attributes to update and the values
   *                           indicate the new attribute values.
   */
  public function update_attributes_or_fail($attributes) {
    $this->set_attributes($attributes);
    return $this->save_or_fail();
  }

  /**
   * Adds one to the value of the named attribute.  If the attribute
   * is null, it is first initialized to zero before adding one.
   *
   * @param string $attribute  The name of the attribute to increment
   *
   * @return ActiveRecord_Base  A reference to self
   */
  public function increment($attribute) {
    if (!isset($this->$attribute)) $this->$attribute = 0;
    $this->$attribute += 1;
    return $this;
  }

  /**
   * Increments the named attribute and saves the object.
   *
   * @param string $attribute  The name of the attribute to increment
   *
   * @return bool The success or failure indicator from the save operation
   */
  public function increment_and_save($attribute) {
    return $this->increment($attribute)->update_attribute($attribute, $this->$attribute);
  }

  /**
   * Subtracts one from the value of the named attribute.  If the
   * attribute is null, it is first initialized to zero before
   * subtracting one.
   *
   * @param string $attribute  The name of the attribute to decrement
   *
   * @return ActiveRecord_Base  A reference to self
   */
  public function decrement($attribute) {
    if (!isset($this->$attribute)) $this->$attribute = 0;
    $this->$attribute -= 1;
    return $this;
  }

  /**
   * Decrements the named attribute and saves the object.
   *
   * @param string $attribute  The name of the attribute to decrement
   *
   * @return bool The success or failure indicator from the save operation
   */
  public function decrement_and_save($attribute) {
    return $this->decrement($attribute)->update_attribute($attribute, $this->$attribute);
  }

  /**
   * Sets an attribute with a true value to false and anything else to
   * true.
   *
   * @param string $attribute  The name of the attribute to toggle
   *
   * @return ActiveRecord_Base  A reference to self
   */
  public function toggle($attribute) {
    $this->$attribute = $this->$attribute ? false : true;
    return $this;
  }

  /**
   * Toggles the named attribute and saves the object.
   *
   * @param string $attribute  The name of the attribute to toggle
   *
   * @return bool The success or failure indicator from the save operation
   */
  public function toggle_and_save($attribute) {
    return $this->toggle($attribute)->update_attribute($attribute, $this->$attribute);
  }

  /**
   * Reloads the attributes for this object from the database.
   *
   * @return ActiveRecord_Base  A reference to self
   */
  public function reload() {
    $obj = $this->find($this->id());
    $this->attributes = array_merge($obj->attributes(), $this->attributes);
    return $this;
  }

  /**
   * Returns true if the named attribute exists for this object and
   * has a non-empty value (not null, false, 0, the empty string, or
   * any empty array).
   *
   * @param string $attribute  The name of the attribute to check
   *
   * @return bool
   */
  public function attribute_present($attribute) {
    if (array_key_exists($attribute, $this->attributes)) {
      $value = $this->read_attribute($attribute);
      return (!empty($value));
    }

    return false;
  }

  /**
   * Returns true if this object has the named attribute (even if it
   * is empty)
   *
   * @param string $attrName  The name of the attribute to check
   *
   * @return bool
   */
  public function has_attribute($attrName) {
    return array_key_exists($attrName, $this->attributes);
  }

  /**
   * Returns an array of names for the attributes available on this
   * object.  The returned list is sorted alphabetically.
   *
   * @return array
   */
  public function attribute_names() {
    $keys = array_keys($this->attributes);
    sort($keys);
    return $keys;
  }

  /**
   * Returns the setting of the read-only flag for this object
   *
   * @return bool
   */
  public function readonly() {
    return $this->readonly;
  }

  /**
   * Change the setting of the read-only flag for this object
   *
   * @param bool $flag  The value to set for the flag
   */
  public function set_readonly($flag) {
    $this->readonly = $flag;
  }

  /**
   * Return the errors collection for this object
   *
   * @return ActiveRecord_Errors
   */
  public function errors() {
    if (!$this->errors)
      $this->errors = new ActiveRecord_Errors($this);
    return $this->errors;
  }

  /**
   * Determine if this record is valid
   *
   * @return bool
   */
  public function is_valid() {
    $this->fire_event('before_validation');
    $this->fire_event($this->new_record() ? 'before_validation_on_create' : 'before_validation_on_update');
      
    $this->errors()->clear();

    $this->run_validations(ActiveRecord_Validation::ON_SAVE);
    $this->validate();

    if ($this->new_record()) {
      $this->run_validations(ActiveRecord_Validation::ON_CREATE);
      $this->validate_on_create();
    } else {
      $this->run_validations(ActiveRecord_Validation::ON_UPDATE);
      $this->validate_on_update();
    }
    
    $this->fire_event('after_validation');
    $this->fire_event($this->new_record() ? 'after_validation_on_create' : 'after_validation_on_update');

    return $this->errors()->is_empty();
  }









  /*======================================================================
   * Protected methods (that apply to the class)
   *====================================================================*/




  /**
   * Called to initialize class-specific information such as
   * associations, validations, etc.  Derived classes should implement
   * this method to specify these class-specific details.
   */
  protected function init_class() {
  }

  /**
   * Return this class's logger
   *
   * @return Logger
   */
  protected function logger() {
    if (!$this->logger)
      $this->logger = Support_Resources::logger('ActiveRecord');
    return $this->logger;
  }

  /**
   * Called internally to access the class-level meta information
   *
   * @return ActiveRecord_MetaInfo
   */
  protected function get_meta_info() {
    if (!$this->metaInf)
      $this->metaInf = ActiveRecord_InfoMgr::meta_info($this);
    return $this->metaInf;
  }

  /**
   * Create a new instance of an object from a record.  This handles
   * single table inheritance, allowing different types of objects to
   * be instantiated from the same table.
   *
   * @param array $record  An associative array of the record's values.
   *
   * @return object
   */
  protected function instantiate($record) {
    $self = get_class($this);
    $obj = NULL;

    if (isset($record[$this->inheritance_column()]) && ($record[$this->inheritance_column()] != $self)) {

      $class = $record[$this->inheritance_column()];
      if (!class_exists($class))
        throw new ActiveRecord_SubclassNotFound($class, $self, $this->inheritance_column());
      $obj = new $class();

    } else {
      $obj = new $self();
    }

    $obj->attributes = $record;
    $obj->new_record = false;
    return $obj;
  }

  /**
   * Add join fragment to a SQL statement
   *
   * @param string &$sql     The SQL statement being constructed (reference)
   * @param array  $options  The array of options provided
   */
  protected function add_joins(&$sql, $options) {
    if (isset($options['joins']))
      $sql .= " $options[joins] ";
  }

  /**
   * Add conditions fragment to a SQL statement
   *
   * @param string &$sql     The SQL statement being constructed (reference)
   * @param array  $options  The array of options provided
   */
  protected function add_conditions(&$sql, $options) {
    $segments = array();
    if (isset($options['conditions']))
      $segments[] = $this->connection()->sanitizeSQL($options['conditions']);
    if (!$this->is_first_concrete_descendent())
      $segments[] = $this->type_condition();

    if ($segments)
      $sql .= " WHERE (".implode(') AND (', $segments).") ";
  }

  /**
   * Add order by fragment to a SQL statement
   *
   * @param string &$sql     The SQL statement being constructed (reference)
   * @param array  $options  The array of options provided
   */
  protected function add_order(&$sql, $options) {
    if (isset($options['order']))
      $sql .= " ORDER BY $options[order] ";
  }

  /**
   * Handle include processing for a result set
   *
   * @param mixed $includes The name (or array of names) to include
   * @param array &$records The record set to perform the include for
   */
  protected function process_includes($includes, &$records) {
    $includes = is_array($includes) ? $includes : array($includes);
    $inf = $this->get_meta_info();
    foreach ($includes as $incl) {
      $assoc = $inf->get_association($incl);
      if (!$assoc) throw new ActiveRecord_UnknownAssociationError($incl);
      $assoc->do_include($records);
    }
  }
  
  /**
   * Determine if this class is the first concrete descendent from
   * ActiveRecord_Base.
   *
   * @return bool
   */
  protected function is_first_concrete_descendent() {
    $class = new ReflectionObject($this);
    $parent = $class->getParentClass();
    if ($parent && $parent->isAbstract())
      return true;
    else
      return false;
  }

  /**
   * Return condition SQL fragment for single table inheritance
   *
   * Note: this is considerably less robust than the Rails active
   * record implementation.  Rails includes subclasses, whereas this
   * does not.  Reason being that it is not only cumbersome to
   * determine descendents in PHP, but it would only ever be
   * practical to determine descendents which have already been
   * loaded/declared.  This is only a factor if you have more than
   * two levels of inheritance (in which case it would be a good
   * idea to override this).
   *
   * @return string
   */
  protected function type_condition() {
    return $this->table_name() . "." . $this->inheritance_column() . " = " . $this->connection()->quote(get_class($this));
  }

  /**
   * Add limit information to the SQL statement
   *
   * @param string &$sql     The SQL statement being constructed (reference)
   * @param array  $options  The array of options provided
   */
  protected function add_limit(&$sql, $options) {
    if (isset($options['limit']) || isset($options['offset']))
      $sql = $this->connection()->addLimit($sql,
                                           isset($options['limit']) ? $options['limit'] : -1,
                                           isset($options['offset']) ? $options['offset'] : 1);
  }

  /**
   * Validates the options provided to a find method
   */
  protected function validate_find_options($options) {
    $possible = array('conditions'=>1, 'order'=>1, 'limit'=>1, 'offset'=>1, 'joins'=>1, 'from'=>1, 'readonly'=>1, 'include'=>1);
    $this->validate_options($options, $possible);
  }
  
  /**
   * Validates a provided set of options against an allowed set
   */
  protected function validate_options(&$options, &$allowed) {
    foreach ($options as $key=>$opt) {
      if (!isset($allowed[$key]))
        throw new ActiveRecord_UnknownOptionError($key);
    }
  }

  /**
   * Return an associative array of attributes representing the
   * default values for the fields on this class's table.  The array
   * keys are the field names and the values are the default values.
   *
   * @return array
   */
  protected function attributes_from_column_definition() {
    $attrs = array();
    foreach ($this->columns() as $col) {
      if ($col->name() != $this->primary_key())
        $attrs[$col->name()] = $col->reverse_type_cast($col->default_value());
      else
        $attrs[$col->name()] = null;
    }
    return $attrs;
  }

  /**
   * Sets the inheritance column value for this object if needed.
   */
  protected function ensure_proper_type() {
    if (!$this->is_first_concrete_descendent())
      $this->write_attribute($this->inheritance_column(), get_class($this));
  }

  /**
   * Type cast a raw column value to a value suitable for use in PHP
   * code.  By default, this method just delegates to the column
   * definition, but it exists as a hook for derived classes to be
   * able to extend the column definition's behavior on an
   * application-wide basis.  For example, if you have a different
   * class you want to use to represent dates, or another data type
   * for enums in MySQL.  Note that this is not the correct place to
   * handle field-specific type casts for an individual field (for
   * example, if the 'foo' field for class 'Bar' is stored as a string
   * but has a wrapper class that is used in PHP code).  Overrides for
   * individual fields are best handled by defining accessor and
   * mutator (get/set) methods for those fields.
   *
   * @param mixed              $value The value to cast
   * @param ActiveRecordColumn $col   The column definition (if any) for the value
   */
  protected function type_cast($value, $col) {
    return $col ? $col->type_cast($value) : $value;
  }

  /**
   * Type cast a value from PHP code into a value suitable for use in
   * SQL.  By default, this method just delegates to the column
   * definition, but, like type_cast, it exists as a hook for derived
   * classes to be able to extend the column definition's behavior on
   * an application-wide basis.  For example, if you have a different
   * class you use to represent dates, or another data type for enums
   * in MySQL.  Note that this is not the correct place to handle
   * field-specific type casts for an individual field (for example,
   * if the 'foo' field for class 'Bar' is stored as a string but has
   * a wrapper class that is used in PHP code).  Overrides for
   * individual fields are best handled by defining accessor and
   * mutator (get/set) methods for those fields.
   *
   * @param mixed              $value The value to cast
   * @param ActiveRecordColumn $col   The column definition (if any) for the value
   */
  protected function reverse_type_cast($value, $col) {
    return $col ? $col->reverse_type_cast($value) : $value;
  }

  /** 
   * Traverses the list of this class's parents to return the oldest
   * ancestor which is not abstract (this is for single table
   * inheritance since inherited classes uses the base class's table.
   */
  protected function base_class_name() {
    $info = new ReflectionObject($this);
    $parentInfo = $info->getParentClass();
    while ( ($parentInfo) && (!$parentInfo->isAbstract()) ) {
      $info = $parentInfo;
      $parentInfo = $info->getParentClass();
    }

    return $info->getName();
  }

  /**
   * Invoke a named method on an blank instance of our parent class
   * if our parent is not abstract.
   *
   * @param string $method  The name of the method to invoke
   * @param array  $args    The arguments to pass
   * @param mixed  $default The default value to return if the parent class is abstract.
   */
  protected function send_parent($method, $args = NULL, $default = NULL) {
    if ($this->is_first_concrete_descendent())
      return $default;

    $args = is_null($args) ? array() : $args;
    $args = is_array($args) ? $args : array($args);

    $mom = get_parent_class($this);
    $momObj = new $mom();
    return call_user_func_array(array($momObj, $method), $args);
  }

  /**
   * Return a list of attributes which are never permitted in
   * mass-assignment.  The returned list must be sorted.  By default,
   * the returned list include the primary key and inheritance column.
   *
   * @return array
   */
  protected function attributes_protected_by_default() {
    $protected = array($this->primary_key(), $this->inheritance_column());
    if ($protected[0] != 'id') $protected[] = 'id';
    sort($protected);

    return $protected;
  }

  /**
   * Remove attributes protected from mass assignment from an
   * associative array
   *
   * @param array $attrs  The array to clean
   */
  protected function remove_attributes_protected_from_mass_assignment($attrs) {
    ksort($attrs);
    $onlyThese = $this->accessible_attributes();
    $notThese = $this->protected_attributes();

    if ($onlyThese) {
      $attrs = Support_ArrayHelper::kintersect_sorted($attrs, $onlyThese);
    } elseif ($notThese) {
      $attrs = Support_ArrayHelper::kexclude_sorted($attrs, $notThese);
    }

    $attrs = Support_ArrayHelper::kexclude_sorted($attrs, $this->attributes_protected_by_default());
    return $attrs;
  }

  /**
   * Extract attribute names from an "_and_" separated string and
   * construct an associative array using a corresponding array of
   * arguments.
   *
   * @param string $attributeNames  The string of attribute names
   * @param array  $args            The corresponding argument list
   * @param array  &$options        Output parameter for any additional options argument in $args
   *
   * @return array A hash with attribute names as keys and the corresponding values taken from $args
   */
  protected function create_finder_attribute_hash($attributeNames, $args, &$options) {
    $attrs = array();
    $names = explode('_and_', $attributeNames);

    // make sure our argument count matches up
    if ((count($names) != count($args)) && (count($names) + 1 != count($args)))
      throw new ActiveRecord_IncorrectArgumentCountError("find_by_$attributeNames", count($names), count($args));

    foreach ($names as $attr) {
      if (!array_key_exists($attr, $this->attributes))
        throw new ActiveRecord_UnknownPropertyError($attr);

      $attrs[$attr] = array_shift($args);
    }

    if (count($args) > 0)
      $options = array_shift($args);

    return $attrs;
  }

  /**
   * Create an options parameter for passing to a find_* method using
   * the output from create_finder_attribute_hash
   *
   * @param array $attrs   The hash returned by create_finder_attribute_hash
   * @param array $options Any additional options to include
   *
   * @return array
   */
  protected function assemble_finder_options($attrs, $options) {
    $conditions = array();
    $parameters = array();

    foreach ($attrs as $name=>$value) {
      $conditions[] = is_null($value) ? "$name IS ?" : "$name = ?";
      $parameters[] = $value;
    }

    array_unshift($parameters, implode(' AND ', $conditions));
    $options['conditions'] = $parameters;

    return $options;
  }
  
  /**
   * Return the proxy method for a given method name, or null if none declared.
   *
   * @param string $name  The method to search for
   * @return ActiveRecord_ProxyMethod
   */
  protected function proxy_method_for($name) {
    $inf = $this->get_meta_info();
    if ($inf->proxied_methods && isset($inf->proxied_methods[$name]))
      return $inf->proxied_methods[$name];
    
    $p = $inf->parent;
    while ($p) {
      if ($p->proxied_methods && isset($p->proxied_methods[$name]))
        return $p->proxied_methods[$name];
      $p = $p->parent;
    }
    
    return null;
  }
  
  /**
   * Define a proxy for a method name.
   *
   * A proxied method allows the addition of "virtual" methods to a class
   * which are actually invoked on another object.  This is useful for
   * features such as associations which need to define additional methods
   * dynamically.
   *
   * Methods calls made to the other object include two additional
   * parameters which are always the first two parameters passed.  They
   * are the ActiveRecord_Base instance the call is being invoked on and
   * an ActiveRecord_Proxy object which allows direct access to the
   * attributes for the object.
   *
   * Example:
   * <code>
   *   class MyProxy {
   *     public function full_name($record, $proxy) {
   *       return implode(' ', array($record->first_name, $record->last_name));
   *     }
   *   }
   *
   *   class Student extends ActiveRecord_Base {
   *     protected function init_class() {
   *       $this->add_method_proxy('full_name', array(new MyProxy(), 'full_name'));
   *     }
   *   }
   *
   *   $student = new Student();
   *   $student->full_name; // calls full_name method on MyProxy
   * </code>
   *
   * @param string $name       The name of the method to proxy
   * @param callback $callback The callback to invoke for the method
   */
  protected function add_method_proxy($name, $callback) {
    $inf = $this->get_meta_info();
    if (is_null($inf->proxied_methods)) $inf->proxied_methods = array();
    $inf->proxied_methods[$name] = new ActiveRecord_ProxyMethod($name, $callback);
  }





  /*======================================================================
   * Protected methods (that apply to an instance)
   *====================================================================*/



  /**
   * Perform validation checks applicable any time the record is
   * saved.  Use errors()->add($attribute, $message) to record any
   * errors.
   */
  protected function validate() {
  }

  /**
   * Perform validation checks applicable only before saving a new
   * record.  Use errors()->add($attribute, $message) to record any
   * errors.
   */
  protected function validate_on_create() {
  }

  /**
   * Perform validation checks applicable only before saving an
   * existing record.  Use errors()->add($attribute, $message) to
   * record any errors.
   */
  protected function validate_on_update() {
  }

  /**
   * Returns the value of an attribute type cast to the correct data type.
   *
   * @param string $name  The attribute name
   *
   * @return mixed
   */
  protected function read_attribute($name) {
    $value = isset($this->attributes[$name]) ? $this->attributes[$name] : NULL;
    $col = $this->column_for_attribute($name);
    return $this->type_cast($value, $col);
  }

  /**
   * Returns the value of an attribute without performing any type casting.
   *
   * @param string $name  The attribute name
   *
   * @return mixed
   */
  protected function read_attribute_before_type_cast($name) {
    return isset($this->attributes[$name]) ? $this->attributes[$name] : NULL;
  }

  /**
   * Set the value of a named attribute.  Empty strings for numeric fields are treated as NULL.
   *
   * @param string $name  The attribute name
   * @param mixed  $value The new value for the attribute
   */
  protected function write_attribute($name, $value) {
    $col = $this->column_for_attribute($name);
    $this->attributes[$name] = $this->reverse_type_cast($value, $col);
  }
  
  /**
   * Returns true if this object has a cached attribute with the provided
   * name.  See write_cached_attribute for more information on cached
   * attributes.
   *
   * @param string $name  The attribute name
   * @return boolean
   */
  protected function has_cached_attribute($name) {
    return is_null($this->cached_attributes) ? false : isset($this->cached_attributes[$name]);
  }
  
  /**
   * Return the value stored in the attribute cache for the given name.
   * If no value is in the cache, returns null.  Note that a null value
   * does not indicate the attribute has no value, only that no cached
   * value is present.  See write_cached_attribute for more information
   * on cached attributes.
   *
   * @param string $name  The attribute name
   * @return mixed
   */
  protected function read_cached_attribute($name) {
    if ( is_null($this->cached_attributes) && (!isset($this->cached_attributes[$name])) )
      return null;
    return $this->cached_attributes[$name];
  }
  
  /**
   * write_cached_attribute allows the object to store a temporary value
   * in an attribute cache.  Values placed in the cache are ignored when
   * saving or serializing the object.  The cache is intended as a place
   * to store attribute values that have been type cast when casting is
   * an expensive operation and for storing dummy attributes (such as a
   * password confirmation field) which are not persisted.  The cache is
   * never checked by the default attribute read mechanism.  It is the
   * responsibility of the object to implement any logic related to
   * retrieving cached values as the correct time.
   *
   * @param string $name  The attribute name
   * @param mixed  $value The attribute value to cache
   */
  protected function write_cached_attribute($name, $value) {
    if (is_null($this->cached_attributes))
      $this->cached_attributes = array();
    $this->cached_attributes[$name] = $value;
  }

  /**
   * Handles performing the correct save operation for the object.
   *
   * @return bool  Success or failure indicator
   */
  protected function create_or_update() {
    if ($this->readonly)
      throw new ActiveRecord_ReadOnlyRecord();

    $this->fire_event('before_save');
    $result = false;

    if ($this->new_record()) {
      $this->fire_event('before_create');
      $result = $this->create_record();
      $this->fire_event('after_create');
    } else {
      $this->fire_event('before_update');
      $result = $this->update_record();
      $this->fire_event('after_update');
    }

    $this->fire_event('after_save');

    return $result;
  }

  /**
   * Update the record associated with the object
   *
   * @return bool  Success or failure indicator
   */
  protected function update_record() {
    $sql = "UPDATE ".$this->table_name().
           " SET ".$this->attributes_for_set().
           " WHERE ".$this->primary_key()." = " . $this->connection()->quote($this->id());
    $count = false;

    try {

      $start = microtime(true);
      $count = $this->connection()->exec($sql);
      $end = microtime(true);
      $elapsed = $end - $start;
      $this->logger()->debug("Update ".get_class($this)." ($count row".($count == 1 ? '' : 's')." in $elapsed sec): $sql");

    } catch (Exception $e) {
      $this->logger()->debug("ERROR executing SQL ($sql) ".get_class($e).": ".$e->getMessage());
      throw $e;
    }

    // Note: testing for a count > 0 is not necessarily a good test
    // since MySQL will return a count of 0 if you try to update a row
    // with the values it already has (0 rows were "affected" by its
    // definition even though 1 row matched the given criteria).

    return ($count !== false);
  }

  /**
   * Create a new record in the database for this object
   *
   * @return bool  Success or failure indicator
   */
  protected function create_record() {

    if ( is_null($this->id()) && $this->connection()->prefetchPrimaryKey($this->table_name()) )
      $this->set_id($this->connection()->nextSequenceValue($this->sequence_name()));

    $sql = "INSERT INTO ".$this->table_name()." ".
           "(".$this->columns_for_insert().
           ") VALUES (".$this->values_for_insert().")";

    try {

      $start = microtime(true);
      $count = $this->connection()->exec($sql);
      $end = microtime(true);
      $elapsed = $end - $start;
      $this->logger()->debug("Insert ".get_class($this)." ($count row".($count == 1 ? '' : 's')." in $elapsed sec): $sql");

    } catch (Exception $e) {
      $this->logger()->debug("ERROR executing SQL ($sql) ".get_class($e).": ".$e->getMessage());
      throw $e;
    }

    if ( is_null($this->id()) && (!$this->connection()->prefetchPrimaryKey($this->table_name())) )
      $this->set_id($this->connection()->lastInsertId());

    $this->new_record = false;

    return true;
  }

  /**
   * Return the list of column names and attribute values for use in
   * the SET clause of an UPDATE statement.  The primary key column is
   * excluded.
   *
   * @return string
   */
  protected function attributes_for_set() {
    $pairs = array();
    $pk = $this->primary_key();
    foreach ($this->attributes as $col=>$val) {
      if ($col != $pk)
        $pairs[] = "$col = ".(is_null($val) ? 'NULL' : $this->connection()->quote($val));
    }

    return implode(', ', $pairs);
  }

  /**
   * Return the list of all attribute names prepared for use in an insert
   * statement
   *
   * @return string
   */
  protected function columns_for_insert() {
    return implode(', ', array_keys($this->attributes));
  }

  /**
   * Return the list of all attribute values prepared for use in an insert
   * statement
   *
   * @return string
   */
  protected function values_for_insert() {
    $values = array();
    foreach ($this->attributes as $val) {
      $values[] = is_null($val) ? 'NULL' : $this->connection()->quote($val);
    }

    return implode(', ', $values);
  }

  /**
   * Notifies any registered event listeners
   *
   * @param string $eventName  The event name to notify listeners for
   */
  protected function fire_event($eventName) {
    $info = $this->get_meta_info();
    while($info) {

      if (isset($info->event_listeners[$eventName])) {
        foreach ($info->event_listeners[$eventName] as $func) {
          if (is_array($func) && is_null($func[0])) {
            $meth = $func[1];
            $this->$meth();
          } else {
            call_user_func($func, $this);
          }
        }
      }

      $info = $info->parent;
    }
  }

  /**
   * Evaluate all validations of a given type associated with this
   * object
   *
   * @param int $type Validation type, as defined by ActiveRecordValidation
   */
  protected function run_validations($type) {
    $info = $this->get_meta_info();
    while ($info) {
      if ($info->validations) {
        foreach ($info->validations as $validation) {
          if ($validation->type == $type)
            $validation->validate($this);
        }
      }

      $info = $info->parent;
    }
  }

  /**
   * Convert a string name of a validation type to the class constant
   *
   * @param string $name  The name to convert
   *
   * @return int
   */
  protected function validation_name_to_type($name) {
    if ($name == 'save')
      return ActiveRecord_Validation::ON_SAVE;
    if ($name == 'create')
      return ActiveRecord_Validation::ON_CREATE;
    if ($name == 'update')
      return ActiveRecord_Validation::ON_UPDATE;

    throw new Exception("Unrecognized value for on: $name");
  }
  
  /**
   * Return a proxy object for this class.
   *
   * @return ActiveRecord_Proxy
   */
  protected function proxy() {
    $inf = $this->get_meta_info();
    $proxy = new ActiveRecord_Proxy();
    $proxy->initialize($this, $this->attributes, $this->cached_attributes, $inf);
    return $proxy;
  }

}

?>