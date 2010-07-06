<?php
/**
 * p Smarty function (URL prefix)
 *
 * Returns the URL prefix (for when the application is not in the web root).
 *
 * @param array $options  The function options
 * @param Smary $smarty   The Smarty instance
 *
 * @return string
 */
function smarty_function_p ($options, $smarty) {
  return Cfg::get('url_prefix', '');
}
