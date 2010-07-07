<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The collection of associates in a has many association.  In addition
 * to the documented methods, the collection object may be treated as an
 * array (iterated over, accessed with square brackets, etc.).
 */
class ActiveRecord_Association_Collection implements IteratorAggregate, ArrayAccess, Countable {

  protected $record;
  protected $proxy;
  protected $association;
  protected $associates;
  protected $count;
  
  /**
   * Constructor
   *
   * @param ActiveRecord_Base $record  The record we're associated with
   * @param ActiveRecord_Proxy $proxy  The proxy for the records protected methods
   * @param ActiveRecord_Assocation $association The association we represent a collection of
   * @param array $associates Optionally allows the initial collection contents to be specified
   */
  public function __construct($record, $proxy, $association, $associates = null) {
    $this->record = $record;
    $this->proxy = $proxy;
    $this->association = $association;
    if (is_null($associates)) {
      $this->associates = null;
      $this->count = null;
    } else {
      $this->associates = $associates;
      $this->count = count($this->associates);
    }
  }
  
  /**
   * Returns the array of associate objects
   *
   * @return array
   */
  protected function &associates() {
    if (is_null($this->associates)) {
      $this->associates = &$this->association->load_associates($this->record, $this->proxy);
      $this->count = count($this->associates);
    }
    return $this->associates;
  }
  
  /**
   * Returns the number of associated objects in the collection
   *
   * @return int
   */
  public function size() {
    if (is_null($this->count)) {
      if (is_null($this->associates))
        $this->count = $this->association->count_associates($this->record, $this->proxy);
      else
        $this->count = count($this->associates);
    }
    return $this->count;
  }
  
  /**
   * Returns the number of associated objects in the collection (same as size())
   *
   * @return int
   */
  public function count() { return $this->size(); }

  /**
   * Returns the number of associated objects in the collection (same as size())
   *
   * @return int
   */
  public function length() { return $this->size(); }

  /**
   * Returns an iterator for this collections objects
   *
   * @return ArrayIterator
   */
  public function getIterator() {
    return new ArrayIterator($this->associates());
  }
  
  /**
   * Test for the existence of a given offset
   *
   * @param int $offset  The offset to test
   * @return boolean
   */
  public function offsetExists($offset) {
    if (!is_numeric($offset)) return false;
    return (($offset >= 0) && ($offset < $this->size()));
  }
  
  /**
   * Get the associate at a given offset
   *
   * @param int $offset  The offset to get
   * @return boolean
   */
  public function offsetGet($offset) {
    $objects =& $this->associates();
    return $objects[$offset];
  }
  
  /**
   * Set the associate at a given offset
   *
   * @param int $offset  The offset to set the associate at
   * @param ActiveRecord_Base $value  The associate to set
   */
  public function offsetSet($offset, $value) {
    $objects =& $this->associates();
    if ((!is_null($offset)) && ($offset !== '') && ($offset >= 0) && ($offset < count($objects))) {
      if (isset($objects[$offset]))
        $this->association->remove_associate($this->record, $this->proxy, $objects[$offset]);
      $this->association->add_associate($this->record, $this->proxy, $value);
      $objects[$offset] = $value;
    } else {
      $this->association->add_associate($this->record, $this->proxy, $value);
      $objects[] = $value;
    }
    $this->count = count($objects);
  }
  
  /**
   * Unset the associate at a given offset
   *
   * @param int $offset  The offset to unset
   */
  public function offsetUnset($offset) {
    $objects =& $this->associates();
    if ($offset >= 0 && $offset < count($objects)) {
      if (isset($objects[$offset]))
        $this->association->remove_associate($this->record, $this->proxy, $objects[$offset]);
      unset($objects[$offset]);
    }
    $this->count = count($objects);
  }
  
  /**
   * Clear all associates from the collection
   */
  public function clear() {
    $objects =& $this->associates();
    foreach ($objects as $associate) {
      $this->association->remove_associate($this->record, $this->proxy, $associate);
    }
    $this->associates = array();
    $this->count = 0;
  }
  
  /**
   * Test the collection to see if it is empty
   *
   * @return boolean True if the collection is empty, false otherwise
   */
  public function is_empty() {
    return ($this->size() === 0);
  }

  /**
   * Similar to ActiveRecord_Base::find(), but limited to items within
   * this collection.
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
    foreach ($ids as $id) {
      if (strlen($list) > 0) $list .= ',';
      $list .= $this->record->connection()->quote($id);
    }
    $objs = $this->association->find_all($this->record, $this->proxy,
      array('conditions'=>$this->association->primary_key()." IN($list)"));

    if (count($objs) != count($ids))
      throw new ActiveRecord_NotFoundError();

    if (count($objs) == 1)
      return $objs[0];
    else
      return $objs;
  }

  /**
   * Similar to ActiveRecord_Base::find_first(), but limited to items
   * within this collection.
   *
   * @param array $options The options for the find operation
   *
   * @return mixed  The first object found or NULL
   */
  public function find_first($options) {
    $options['limit'] = 1;
    $results = $this->association->find_all($this->record, $this->proxy, $options);
    if (!$results)
      return NULL;
    else
      return $results[0];
  }

  /**
   * Similar to ActiveRecord_Base::find_all(), but limited to items
   * within this collection.
   *
   * @param array $options The options for the find operation
   *
   * @return array  The list of objects found
   */
  public function find_all($options) {
    return $this->association->find_all($this->record, $this->proxy, $options);
  }

  /**
   * Similar to ActiveRecord_Base::exists(), but limited to items
   * within this collection.
   *
   * @param mixed $test  The id or conditions to test for
   *
   * @return bool
   */
  public function exists($test) {
    $test = is_array($test) ? $test : array($this->association->primary_key()."=?", $test);
    try {
      $obj = $this->find_first(array('conditions'=>$test));
      return ($obj != NULL);
    } catch (ActiveRecord_Error $e) {
      return false;
    }
  }

  /**
   * Remove the associate from the collection
   *
   * @param ActiveRecord_Base $associate  The associate to remove
   */
  public function delete($associate) {
    $this->association->remove_associate($this->record, $this->proxy, $associate);
    if (!is_null($this->associates)) {
      if (($idx = array_search($associate, $this->associates)) !== false)
        unset($this->associates[$idx]);
      $this->count = count($this->associates);
    } else {
      $this->count = null;
    }
  }
  
  /**
   * Construct a new associate from a set of attributes and add it to the collection.
   *
   * @param array $attributes  The attributes for the new associate
   */
  public function build($attributes) {
    $associate = $this->association->build_associate($this->record, $this->proxy, $attributes);
    $objects =& $this->associates();
    $objects[] = $associate;
    $this->count = count($objects);
    return $associate;
  }

  /**
   * Construct a new associate and save it (validation permitting) from a
   * set of attributes and add it to the collection.
   *
   * @param array $attributes  The attributes for the new associate
   */
  public function create($attributes) {
    $objects =& $this->associates();
    $associate = $this->association->create_associate($this->record, $this->proxy, $attributes);
    $objects[] = $associate;
    $this->count = count($objects);
    return $associate;
  }

}

?>