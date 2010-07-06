<?php

/**
 * A validation for the foreign key field on a belongs_to relationship
 */
class ActiveRecord_Association_BelongsToKeyValidation extends ActiveRecord_Validation {
  protected $msg;
  protected $foreign_key;
  protected $class_name;
  
  /**
   * Constructor
   *
   * @param string $foreign_key The name of the foreign key field
   * @param string $class_name  The name of the associated class
   * @param string $msg         Error message to use
   * @param int    $type        The validation type (ON_SAVE, ON_CREATE, or ON_UPDATE)
   * @param string $condition   Optional method to call for determining whether to run the validation or not
   */
  public function __construct($foreign_key, $class_name, $msg = false, $type = ActiveRecord_Validation::ON_SAVE, $condition = false) {
    parent::__construct($type, $condition);
    $this->foreign_key = $foreign_key;
    $this->class_name = $class_name;
    $this->msg = $msg;
  }
  
  /**
   * Validate the object provided.  If the object is invalid, it is
   * the responsibility of this method to add any errors appropriate
   * to the object's error collection.
   *
   * @param ActiveRecord_Base $obj  The object to validate
   */
  public function validate($obj) {
    if (!$this->should_validate($obj)) return;

    $attr = $this->foreign_key;
    $klass = $this->class_name;
    
    if (!empty($obj->$attr)) {
      $associated = new $klass();
      if (!$associated->exists($obj->$attr))
        $obj->errors()->add($attr, $this->msg);
    }
  }
}

/**
 * Implements the belongs_to association
 */
class ActiveRecord_Association_BelongsTo extends ActiveRecord_Association {
  
  /**
   * List of valid options for creation
   */
  public static $creation_options = array('class_name'=>1, 'foreign_key'=>1, 'validate_key'=>1);
  
  /**
   * Constructor
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param string             $name    The association name
   * @param array              $options Options for the association
   */
  public function __construct($record, $proxy, $name, $options) {
    $this->name = $name;
    
    $defaults = array('class_name'=>Support_Inflector::camelize($name),
                      'foreign_key'=>"${name}_id",
                      'validate_key'=>true);
    if (!is_array($options)) $options = array();
    $options = array_merge($defaults, $options);
    
    $this->class_name = $options['class_name'];
    $this->foreign_key = $options['foreign_key'];
    
    // create additional methods
    $proxy->add_method_proxy('set_'.$this->foreign_key, array($this, 'set_foreign_key'));
    $proxy->add_method_proxy($this->name, array($this, 'get_associate'));
    $proxy->add_method_proxy('set_'.$this->name, array($this, 'set_associate'));
    $record->add_event_listener('before_save', array($this, 'before_save'));
    $proxy->add_method_proxy('build_'.$this->name, array($this, 'build_associate'));
    $proxy->add_method_proxy('create_'.$this->name, array($this, 'create_associate'));
    
    // add valition
    if ($options['validate_key'])
      $proxy->add_validation(new ActiveRecord_Association_BelongsToKeyValidation($this->foreign_key,
        $this->class_name));
  }
  
  /**
   * Mutator method for the foreign key.  This is redefined to invalidate any cached object.
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param mixed $value The new key value
   */
  public function set_foreign_key($record, $proxy, $value) {
    $oldValue = $proxy->read_attribute($this->foreign_key);
    if ($oldValue !== $value) {
      $proxy->write_attribute($this->foreign_key, $value);
      $proxy->write_cached_attribute($this->name, null);
    }
  }

  /**
   * Accessor method for the associated object
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param boolean $force_reload If true, forces reloading of the object even if it's cached
   * @return ActiveRecord_Base The associated object or null
   */
  public function get_associate($record, $proxy, $force_reload = false) {
    if ( $force_reload || (!$proxy->has_cached_attribute($this->name)) ) {
      $key = $this->foreign_key;
      $klass = $this->class_name;
      $obj = new $klass();
      $proxy->write_cached_attribute( $this->name,
        $obj->find_first(array('conditions'=>array($obj->primary_key.'=?', $record->$key))) );
    }
    return $proxy->read_cached_attribute($this->name);
  }

  /**
   * Mutator method for the associated object
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param object $value The new object to associate with our record
   */
  public function set_associate($record, $proxy, $value) {
    if (!($value instanceof $this->class_name))
      throw new ActiveRecord_AssociationTypeMismatch($this->class_name, $value);
    
    $key = $this->foreign_key;
    if ($record->$key != $value->id())
      $record->$key = $value->id();
    $proxy->write_cached_attribute($this->name, $value);
  }
  
  /**
   * Check the associate to make sure it has been saved
   */
  public function before_save($record) {
    $name = $this->name;
    $key = $this->foreign_key;
    $associate = $record->$name;
    if ($associate && $associate->new_record()) {
      $associate->save_or_fail();
      $record->$key = $associate->id();
    }
  }

  /**
   * Build a new associated object from an associative array of
   * attributes and assign it to the object
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param array $attributes The attributes for the new object to associate with our record
   * @return ActiveRecord_Base
   */
  public function build_associate($record, $proxy, $attributes) {
    $klass = $this->class_name;
    $key = $this->foreign_key;
    
    $obj = new $klass($attributes);
    
    $record->$key = $obj->id();
    $proxy->write_cached_attribute($this->name, $obj);
    
    return $obj;
  }

  /**
   * Create and save a new associated object from an associative array of
   * attributes and assign it to the object
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param array $attributes The attributes for the new object to associate with our record
   * @return ActiveRecord_Base
   */
  public function create_associate($record, $proxy, $attributes) {
    $klass = $this->class_name;
    $key = $this->foreign_key;
    
    $obj = new $klass($attributes);
    $obj->save();
    
    $record->$key = $obj->id();
    $proxy->write_cached_attribute($this->name, $obj);
    
    return $obj;
  }

  /**
   * Implements the 'include' behavior for a find operation
   *
   * @param array $results The result set to process
   */
  public function do_include(&$results) {
    $key = $this->foreign_key;
    $klass = $this->class_name;
    $attr = $this->name;

    $aKlass = new $klass();
    
    // assemble the list of ids
    $ids = array();
    foreach ($results as $result) {
      if ($result->$key) $ids[$aKlass->connection()->quote($result->$key)] = 1;
    }
    
    // fetch them
    $associates = $aKlass->find_all(array('conditions'=>$aKlass->primary_key().
      ' IN('.implode(',', array_keys($ids)).')'));
    $ids = array();
    foreach ($associates as $obj) {
      $ids[$obj->id()] = $obj;
    }
    
    // assign them
    foreach ($results as $result) {
      if (isset($ids[$result->$key])) $result->$attr = $ids[$result->$key];
    }
  }

}

?>