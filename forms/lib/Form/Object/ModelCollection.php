<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Represents an associated collection of models in a form
 */
class Form_Object_ModelCollection extends Form_Object_Model {
  
  /**
   * Constructor
   *
   * Options are:
   *  - <b>class_name:</b> The name of the model class (inferred from this object's name)
   *  - <b>include_fields:</b> If true (the default) fields are automatically included from the model
   *
   * @param string $form       The containing form
   * @param string $name       The name of this object
   * @param array  $options    The array of options
   */
  public function __construct($form, $name, $options) {
    parent::__construct($form, $name, array('include_fields'=>false));
    
    $defaults = array('class_name'=>Support_Inflector::camelize(Support_Inflector::singularize($name)),
                      'include_fields'=>true);
    $options = array_merge($defaults, $options);
    
    $this->class_name = $options['class_name'];
    
    if ($options['include_fields'])
      $this->include_default_fields();
  }
  
  /**
   * Assign this object's value to another object
   * @param mixed &$object The object to assign the value to
   */
  public function assign_to(&$object) {
    $attr = $this->name;
    
    $existing = $this->collection_by_id($object, $attr);
    $newCollection = array();
    
    foreach ($this->value as $item) {
      $klass = $this->class_name;
      $id = @$item['id'];
      
      if ($id)
        $objItem = isset($existing[$id]) ? $existing[$id] : $this->model_instance()->find($id);
      else
        $objItem = new $klass();
      
      foreach ($this->objects as $child) {
        $child->set_value(@$item[$child->name()], true);
        $child->assign_to($objItem);
      }
      
      $newCollection[] = $objItem;
    }
    
    if (is_array($object))
      $object[$attr] = $newCollection;
    else
      $object->$attr = $newCollection;
  }

  /**
   * Load an object's named collection and return the items as an
   * associative array indexed by id.
   *
   * @param mixed &$object The object to obtain the collection from
   * @param string $collectionName the name of the collection to retrieve
   */
  protected function collection_by_id(&$object, $collectionName) {
    if (is_array($object))
      $collection = isset($object[$collectionName]) ? $object[$collectionName] : array();
    else
      $collection = $object->$collectionName;
    
    $index = array();
    
    foreach ($collection as $item) {
      if (is_array($item))
        $index[@$item['id']] = $item;
      elseif (method_exists($item, 'id'))
        $index[$item->id()] = $item;
      else
        $index[$item->id] = $item;
    }
    
    return $index;
  }
  
}
