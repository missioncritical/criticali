<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * form_block Smarty block
 *
 * Parameters:
 *  - <b>form:</b>    The form object to use (required)
 *  - <b>name:</b>    The name of the variable for storing the builder attribute
 *  - <b>builder:</b> The name of the form builder to use
 *  - <b>url_for:</b> If provided, used as the form action after passing to url_for. If a string is passed instead of an associative array, it is assumed to be encoded as URL parameters (e.g. "controller=foo&action=bar"
 *  - <b>form_tag:</b> If explicitly set to false, a form tag is not output
 *  - All other parameters are output as attributes of the form tag
 *
 * Encloses a block area for working with a form's builder
 *
 * @param array   $options The function options
 * @param string  $content The block content
 * @param Smarty  $smarty  The Smarty instance
 * @param boolean &$repeat The repeat flag
 *
 * @return string
 */
function smarty_block_form_block($options, $content, &$smarty, &$repeat) {
  
  if (!isset($options['form'])) {
    $smarty->trigger_error("Missing required parameter \"form\" in block form_block");
    $repeat = false;
    return;
  }

  $form = $options['form'];

  if ($repeat) {
    // opening tag: set the variables needed for the contained block
    $name = isset($options['name']) ? $options['name'] : 'builder';
    $builder = isset($options['builder']) ? $options['builder'] : 'default';
    
    $form->set_value($smarty->get_template_vars());
    $builder = Form_Builder::instance($builder, $form);
    
    $smarty->assign($name, $builder);
    
    return;
  }
  
  // otherwise, this is the closing tag

  unset($options['form']);
  unset($options['builder']);
  
  if (isset($options['form_tag']) && (!$options['form_tag']))
    return $content; // no further processing needed
  
  if (isset($options['url_for'])) {
    $params = $options['url_for'];

    if (!is_array($params)) {
      $items = array();
      foreach (explode('&', $params) as $pair) {
        list($k, $v) = explode('=', $pair);
        $items[urldecode($k)] = urldecode($v);
      }
      $params = $items;
    }
      
    unset($options['url_for']);
    
    $controller = $smarty->get_template_vars('controller');
    if (!$controller) {
      $smarty->trigger_error("No controller set, but url_for provided in form_block");
      return;
    }
    $options['action'] = $controller->url_for($params);
  }
  
  if (!isset($options['method']))
    $options['method'] = 'post';
  
  if ($form->object_count() == 1) {
    $obj = reset($form->objects());
    
    if ($obj instanceof Form_Object_Model) {
      $value = $obj->value();
      if (!$value->new_record())
        $content = '<input type="hidden" name="id" value="'.htmlspecialchars($value->id).'" />' . $content;
    }
  }
  
  
  return Support_TagHelper::content_tag('form', $content, $options);
}
