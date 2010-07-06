<?php

/**
 * Helper functions for creating HTML tags
 */
class Support_TagHelper {
  /**
   * Constructor -- instantiation not allowed
   */
  private function __construct() {
    throw new Exception("Cannot create an instance of TagHelper");
  }

  /**
   * Output a non-content tag
   *
   * @param string $name  The tag name
   * @param array  $attrs The tag attributes
   *
   * @return string
   */
  public static function tag($name, $attrs = array()) {
    $attrText = array();
    foreach ($attrs as $attr=>$value) {
      $attrText[] = $attr . '="' . htmlspecialchars($value) . '"';
    }

    return "<$name ".implode(' ', $attrText)." />";
  }

  /**
   * Output a content tag
   *
   * @param string $name    The tag name
   * @param string $content The tag contents
   * @param array  $attrs   The tag attributes
   *
   * @return string
   */
  public static function content_tag($name, $content = '', $attrs = array()) {
    $attrText = array();
    foreach ($attrs as $attr=>$value) {
      $attrText[] = $attr . '="' . htmlspecialchars($value) . '"';
    }

    return "<$name ".implode(' ', $attrText).">$content</$name>";
  }
}

?>