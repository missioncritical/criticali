<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package activerecord */

/**
 * Implements the has_many association
 */
class ActiveRecord_Association_HasMany extends ActiveRecord_Association {
  
  /**
   * List of valid options for creation
   */
  public static $creation_options = array('class_name'=>1, 'foreign_key'=>1, 'primary_key'=>1, 'order'=>1);
  
  protected $primary_key;
  protected $order;
  
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
    
    $defaults = array('class_name'=>Support_Inflector::singularize(Support_Inflector::camelize($name)),
                      'foreign_key'=>Support_Inflector::underscore(get_class($record))."_id",
                      'primary_key'=>'id');
    if (!is_array($options)) $options = array();
    $options = array_merge($defaults, $options);
    
    $this->class_name = $options['class_name'];
    $this->foreign_key = $options['foreign_key'];
    $this->primary_key = $options['primary_key'];
    $this->order = isset($options['order']) ? $options['order'] : null;
    
    $single_name = Support_Inflector::singularize($this->name);
    
    // create additional methods
    $proxy->add_method_proxy($this->name, array($this, 'get_associates'));
    $proxy->add_method_proxy('set_'.$this->name, array($this, 'set_associates'));
    $record->add_event_listener('after_create', array($this, 'after_create'));
    $record->add_event_listener('after_save', array($this, 'after_save'));
    $proxy->add_method_proxy("{$single_name}_ids", array($this, 'get_associate_ids'));
    $proxy->add_method_proxy("set_{$single_name}_ids", array($this, 'set_associate_ids'));
  }
  
  /**
   * Return the primary key name for this association
   * @return string
   */
  public function primary_key() { return $this->primary_key; }

  /**
   * Accessor method for the associated objects
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param boolean $force_reload If true, forces reloading of the object even if it's cached
   * @return ActiveRecord_Association_Collection The collection of associates
   */
  public function get_associates($record, $proxy, $force_reload = false) {
    if ( ($force_reload || (!$proxy->has_cached_attribute($this->name))) ) {
      $proxy->write_cached_attribute( $this->name,
        new ActiveRecord_Association_Collection($record, $proxy, $this));
    }
    return $proxy->read_cached_attribute($this->name);
  }

  /**
   * Called by the collection object to load the actual associated objects.
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @return array The associated objects
   */
  public function &load_associates($record, $proxy) {
    $id = $this->primary_key;
    $klass = $this->class_name;
    $obj = new $klass();
    $options = array('conditions'=>array($this->foreign_key.'=?', $record->$id));
    if ($this->order)
      $options['order'] = $this->order;
    $return =& $obj->find_all($options);
    return $return;
  }

  /**
   * Called by the collection object to count the associated objects without loading them.
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @return array The associated objects
   */
  public function count_associates($record, $proxy) {
    $id = $this->primary_key;
    $klass = $this->class_name;
    $obj = new $klass();
    $options = array('conditions'=>array($this->foreign_key.'=?', $record->$id));
    if ($this->order)
      $options['order'] = $this->order;
    return $obj->count($options);
  }

  /**
   * Mutator method for the entire collection of associated objects
   *
   * @param ActiveRecord_Base  $record  The instance of the class the association is being set on
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param array $values The new objects to associate with our record
   * @param boolean $skipKeyUpdates This flag exists for include operations assigning associated objects for caching.  When this flag is true, the normal foreign key assignment and update steps are skipped and the object is merely placed in the cache.
   */
  public function set_associates($record, $proxy, $values, $skipKeyUpdates = false) {
    if (!is_array($values))
      throw new ActiveRecord_AssociationTypeMismatch('array', $values);
    foreach ($values as $value) {
      if (!($value instanceof $this->class_name))
        throw new ActiveRecord_AssociationTypeMismatch($this->class_name, $value);
    }
      
    if (!$skipKeyUpdates) {
      $id = $this->primary_key;
      $key = $this->foreign_key;
      $name = $this->name;
      $klass = $this->class_name;
      $obj = new $klass();
    
      // update any old associates in the database
      if (!$record->new_record()) {
        $excludes = array();
        foreach ($values as $val) {
          if (!$val->new_record()) $excludes[] = $obj->connection()->quote($val->id);
        }
        if ($excludes)
          $cond = array("$key=? AND ".$obj->primary_key()." NOT IN (".implode(',', $excludes).")",
            $record->$id);
        else
          $cond = array("$key=?", $record->$id);
        $obj->update_all("$key=NULL", $cond);
      }
      
      // update any associates in memory
      if ($proxy->has_cached_attribute($this->name)) {
        $mem = $proxy->read_cached_attribute($this->name);
        foreach ($mem as $memItem) { $memItem->$key = null; }
      }

      // now assign the new ones
      $updates = array();
      foreach ($values as $value) {
        $value->$key = $record->$id;
        if ((!$value->new_record()) && (!$record->new_record()))
          $updates[] = $obj->connection()->quote($value->id);
      }
      if ($updates)
        $obj->update_all(array("$key=?", $record->$id), $obj->primary_key()." IN (".
          implode(',', $updates).")");
    }
    
    $proxy->write_cached_attribute($this->name,
      new ActiveRecord_Association_Collection($record, $proxy, $this, $values));
  }
  
  /**
   * Add an associated object to this object's collection.  This method
   * handles database updates and foreign keys.  It does not directly
   * affect the contents of an association collection object.
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param object $value The new object to associate with our record
   */
  public function add_associate($record, $proxy, $value) {
    if (!($value instanceof $this->class_name))
      throw new ActiveRecord_AssociationTypeMismatch($this->class_name, $value);
      
    $id = $this->primary_key;
    $key = $this->foreign_key;

    // set the foreign key
    if ($value->new_record() || $record->new_record())
      $value->$key = $record->$id;
    else
      $value->update_attribute($key, $record->id);
  }

  /**
   * Remove an associated object from this object's collection.  This method
   * handles database updates and foreign keys.  It does not directly
   * affect the contents of an association collection object.
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being removed from
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param object $value The object to remove from our collection
   */
  public function remove_associate($record, $proxy, $value) {
    if (!($value instanceof $this->class_name))
      throw new ActiveRecord_AssociationTypeMismatch($this->class_name, $value);
      
    $id = $this->primary_key;
    $key = $this->foreign_key;
    
    // update the old associate
    if ($value->new_record())
      $value->$key = null;
    else
      $value->update_attribute($key, null);
  }
  
  /**
   * Similar to ActiveRecord_Base::find_all(), but limited to items
   * within this collection.
   *
   * @param ActiveRecord_Base  $record  The record object this operation is being performed on
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param array $options The options for the find operation
   *
   * @return array  The list of objects found
   */
  public function find_all($record, $proxy, $options) {
    if (is_null($options)) $options = array();
    $id = $this->primary_key;
    $klass = $this->class_name;
    $obj = new $klass();
    
    if (isset($options['conditions'])) {
      if (is_array($options['conditions'])) {
        $cond = array_shift($options['conditions']);
        array_unshift($options['conditions'], $record->id);
        array_unshift($options['conditions'], $this->foreign_key."=? AND ($cond)");
      } else {
        $options['conditions'] = array($this->foreign_key."=? AND ($options[conditions])", $record->$id);
      }
    } else {
      $options['conditions'] = array($this->foreign_key.'=?', $record->$id);
    }
    if ((!isset($options['order'])) && $this->order)
      $options['order'] = $this->order;
      
    return $obj->find_all($options);
  }
  
  /**
   * Accessor method for the associated object ids
   *
   * @param ActiveRecord_Base  $record  An instance of the class the association is being added to
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @return array The list of associated ids
   */
  public function get_associate_ids($record, $proxy) {
    $name = $this->name;
    $items = $record->$name;
    $ids = array();
    foreach ($items as $item) { if (!$item->new_record) $ids[] = $item->id; }
    return $ids;
  }

  /**
   * Mutator method for the entire collection of associated objects by ids
   *
   * @param ActiveRecord_Base  $record  The instance of the class the association is being set on
   * @param ActiveRecord_Proxy $proxy   A proxy for the class to allow limited access to protected methods
   * @param array $values The new object ids to associate with our record
   */
  public function set_associate_ids($record, $proxy, $values) {
    if (!is_array($values))
      throw new ActiveRecord_AssociationTypeMismatch('array', $values);
      
    // get the objects
    $klass = $this->class_name;
    $obj = new $klass();
    $associates = $obj->find($values);
    
    // assign them
    $this->set_associates($record, $proxy, $associates);
  }

  /**
   * Check the associates to make sure they have been saved
   */
  public function after_create($record) {
    $name = $this->name;
    $key = $this->foreign_key;
    $id = $this->primary_key;
    $associates = $record->$name;
    foreach ($associates as $associate) {
      $associate->$key = $record->$id;
      $associate->save();
    }
  }

  /**
   * Check the associates to make sure they have been saved
   */
  public function after_save($record) {
    $name = $this->name;
    $associates = $record->$name;
    foreach ($associates as $associate) {
      if ($associate->new_record()) $associate->save();
    }
  }

  /**
   * Build a new associated object from an associative array of
   * attributes and assign it to the object.  This method handles
   * database updates and foreign keys.  It does not directly affect the
   * contents of an association collection object.
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
    
    return $obj;
  }

  /**
   * Create and save a new associated object from an associative array of
   * attributes and assign it to the object.  This method handles
   * database updates and foreign keys.  It does not directly affect the
   * contents of an association collection object.
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
    $options = array('conditions'=>"$key IN(".implode(',', array_keys($ids)).')');
    if ($this->order)
      $options['order'] = $this->order;
    $associates = $aKlass->find_all($options);
    $ids = array();
    foreach ($associates as $obj) {
      if (!isset($ids[$obj->$key])) $ids[$obj->$key] = array();
      $ids[$obj->$key][] = $obj;
    }
    
    // assign them
    foreach ($results as $result) {
      if (isset($ids[$result->$id])) $result->$setAttr($ids[$result->$id], true);
    }
  }

}

?>