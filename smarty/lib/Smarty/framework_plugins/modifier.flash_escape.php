<?php
/**
 * Escape a string for flash HTML
 *
 * @param string
 * @return string
 */
function smarty_modifier_flash_escape ( $string ) {
  return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
}
