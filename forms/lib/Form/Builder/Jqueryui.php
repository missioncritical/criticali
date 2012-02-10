<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * A form builder designed specifically to work with jQuery UI controls
 */
class Form_Builder_Jqueryui extends Form_Builder_Default {
  
  protected $current_model = null;
  
  protected $line_break_after_label;
  protected $wrap_textfield;
  protected $errors_after_control;
  
  /**
   * Constructor
   */
  public function __construct($form) {
    parent::__construct($form);
    
    $this->line_break_after_label = Cfg::get('jqueryui_builder/line_break_after_label', true);
    $this->wrap_textfield = Cfg::get('jqueryui_builder/wrap_textfield', false);
    $this->errors_after_control = Cfg::get('jqueryui_builder/errors_after_control', false);
  }

  /**
   * Return the HTML the given Form_Object from the form
   *
   * @param Form_Object $object The object to return the html for
   * @param array $name_path The name path to the object
   * @return string
   */
  public function html($object, $name_path) {
    if ($object instanceof Form_Object_Model)
      $this->current_model = $object->value();
    
    $html = parent::html($object, $name_path);
    
    if ($object instanceof Form_Object_Model)
      $this->current_model = null;

    return $html;
  }

  /**
   * Return the HTML label for the given Form_Object from the form
   *
   * @param Form_Object $object The object to return the label for
   * @param Form_Control $control The control object to use
   * @param array $name_path The name path to the field
   * @return string
   */
  public function label($object, $control, $name_path) {
    return Support_TagHelper::content_tag('label',
      htmlspecialchars($object->label()) .
        ($object->note() ?
          Support_TagHelper::content_tag('span', htmlspecialchars($object->note()), array('class'=>'note'))
          : ''
        ),
      $this->label_attributes($object, $control, $name_path) );
  }
  
  /**
   * Return the attributes to use with the HTML for a given Form_Object
   * from the form
   */
  public function label_attributes($object, $control, $name_path) {
    $attributes = array();
    $attributes['for'] = $control->control_id($name_path, $object->name());
    
    $attributes['class'] = 'ui-widget';
    
    if ($this->current_model && $this->current_model->errors->on($object->name()))
      $attributes['class'] .= ' ui-state-error-text';
    
    if ($control->name() == 'checkbox')
      $attributes['class'] .= ' control-checkbox';
    
    return $attributes;
  }

  /**
   * Join the HTML for a field's label and a field's control
   *
   * @param Form_Object $object The object the fields are for
   * @param Form_Control $control The control instance being used for the field
   * @param array $name_path The name path to the field
   * @param string $labelHTML The field label HTML
   * @param string $controlHTML The field control HTML
   * @return string
   */
  public function join_label_and_control($object, $control, $name_path, $labelHTML, $controlHTML) {
    $br = $this->line_break_after_label ? '<br/>' : '';
    
    $errors = $this->errors_after_control ? $this->error_list($object, $control, $name_path) : '';
    
    if ($control->name() == 'checkbox')
      return $controlHTML . ' ' . $labelHTML . "<br/>$errors\n";
    elseif ($this->wrap_textfield && $control->name() == 'textfield')
      return $labelHTML . "$br\n" .
        $this->wrap_textfield_control($object, $control, $name_path, $controlHTML) . $errors . "\n";
    else
      return $labelHTML . "$br\n" . $controlHTML . "<br/>$errors\n";
  }

  /**
   * Wrap a textfield control with a containing div
   *
   * @param Form_Object $object The object the fields are for
   * @param Form_Control $control The control instance being used for the field
   * @param array $name_path The name path to the field
   * @param string $controlHTML The field control HTML
   * @return string
   */
  public function wrap_textfield_control($object, $control, $name_path, $controlHTML) {
    $classes = array('ui-widget', 'ui-widget-content', 'ui-corner-all', 'control-textfield');
    
    if ($object->data_type_name() == 'date')
      $classes[] = 'datatype-date';
    if ($this->current_model && $this->current_model->errors->on($object->name()))
      $classes[] = 'error';
    
    $options = array('class'=>implode(' ', $classes));
    
    return Support_TagHelper::content_tag('div', $controlHTML, $options);
  }
  
  /**
   * Return HTML listing errors associated with a control
   *
   * @param Form_Object $object The object the fields are for
   * @param Form_Control $control The control instance being used for the field
   * @param array $name_path The name path to the field
   * @return string
   */
  public function error_list($object, $control, $name_path) {
    $errors = $this->current_model ? $this->current_model->errors->on($object->name()) : false;
    if (!$errors)
      return '';
    
    $html = '<ul class="ui-state-error ui-corner-all">';
    
    foreach ($errors as $error) {
      $html .= '<li><span class="ui-icon ui-icon-alert"></span>' .
        htmlspecialchars($object->title() . ' ' . $error) . '</li>';
    }
    
    $html .= '</ul>';
    
    return $html;
  }
  
}
