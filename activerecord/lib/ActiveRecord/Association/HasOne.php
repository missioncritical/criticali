<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Implements the has_one association
 */
class ActiveRecord_Association_HasOne extends ActiveRecord_Association {
  
  /**
   * List of valid options for creation
   */
  public static $creation_options = array('class_name'=>1, 'foreign_key'=>1, 'primary_key'=>1);
  
  protected $primary_key;
  
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
                      'foreign_key'=>Support_Inflector::underscore(get_class($record))."_id",
                      'primary_key'=>'id');
    if (!is_array($options)) $options = array();
    $options = array_merge($defaults, $options);
    
    $this->class_name = $options['class_name'];
    $this->foreign_key = $options['foreign_key'];
    $this->primary_key = $options['primary_key'];
    
    // create additional methods
    $proxy->add_method_proxy($this->name, array($this, 'get_associate'));
    $proxy->add_method_proxy('set_'.$this->name, array($this, 'set_associate'));
    $record->add_event_listener('after_create', array($this, 'after_create'));
    $record->add_event_listener('after_save', array($this, 'after_save'));
    $proxy->add_method_proxy('build_'.$this->name, array($this, 'build_associate'));
    $proxy->add_method_proxy('create_'.$this->name, array($this, 'create_associate'));
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
    if ( ($force_reload || (!$proxy->has_cached_attribute($this->name))) &&
         (!$record->new_record()) ) {
      $id = $this->primary_key;
      $klass = $this->class_name;
      $obj = new $klass();
      $proxy->write_cached_attribute( $this->name,
        $obj->find_first(array('conditions'=>array($this->foreign_key.'=?', $record->$id))) );
    }
    return $proxy->read_cached_attribute($this->name);
  }

  /**
   * Mutator method for the associated object
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param object $value The new object to associate with our record
   * @param boolean $skipKeyUpdates This flag exists for include operations assigning associated objects for caching.  When this flag is true, the normal foreign key assignment and update steps are skipped and the object is merely placed in the cache.
   */
  public function set_associate($record, $proxy, $value, $skipKeyUpdates = false) {
    if (!($value instanceof $this->class_name))
      throw new ActiveRecord_AssociationTypeMismatch($this->class_name, $value);
      
    if (!$skipKeyUpdates) {
      $id = $this->primary_key;
      $key = $this->foreign_key;
      $name = $this->name;
    
      // update any old associate
      $oldAssoc = $record->$name;
      if ($oldAssoc) {
        if ($oldAssoc->new_record())
          $oldAssoc->$key = null;
        else
          $oldAssoc->update_attribute($key, null);
      }

      // now assign the new one
      if ($value->new_record() || $record->new_record())
        $value->$key = $record->$id;
      else
        $value->update_attribute($key, $record->id);
    }
      
    $proxy->write_cached_attribute($this->name, $value);
  }
  
  /**
   * Check the associate to make sure it has been saved
   */
  public function after_create($record) {
    $name = $this->name;
    $key = $this->foreign_key;
    $id = $this->primary_key;
    $associate = $record->$name;
    if ($associate) {
      $associate->$key = $record->$id;
      $associate->save();
    }
  }

  /**
   * Check the associate to make sure it has been saved
   */
  public function after_save($record) {
    $name = $this->name;
    $associate = $record->$name;
    if ($associate && $associate->new_record()) {
      $associate->save();
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
    $id = $this->primary_key;
    
    $attributes = is_array($attributes) ? $attributes : array();
    $attributes[$this->foreign_key] = $record->$id;
    
    $obj = new $klass($attributes);
    
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
    $id = $this->primary_key;
    
    $attributes = is_array($attributes) ? $attributes : array();
    $attributes[$this->foreign_key] = $record->$id;
    
    $obj = new $klass($attributes);
    $obj->save();
    
    $proxy->write_cached_attribute($this->name, $obj);
    
    return $obj;
  }

  /**
   * Implements the 'include' behavior for a find operation
   *
   * @param array $results The result set to process
   */
  public function do_include(&$results) {
    $id = $this->primary_key;
    $key = $this->foreign_key;
    $klass = $this->class_name;
    $setAttr = 'set_'.$this->name;

    $aKlass = new $klass();
    
    // assemble the list of ids
    $ids = array();
    foreach ($results as $result) {
      if ($result->$id) $ids[$aKlass->connection()->quote($result->$id)] = 1;
    }
    
    // fetch them
    $associates = $aKlass->find_all(array('conditions'=>"$key IN(".
      implode(',', array_keys($ids)).')'));
    $ids = array();
    foreach ($associates as $obj) {
      $ids[$obj->$key] = $obj;
    }
    
    // assign them
    foreach ($results as $result) {
      if (isset($ids[$result->$id])) $result->$setAttr($ids[$result->$id], true);
    }
  }

}

?>