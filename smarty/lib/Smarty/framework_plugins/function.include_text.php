<?php
/** @package smarty */

/**
 * include_text Smarty function
 *
 * Include the contents of a plain text file.
 *
 * Options:
 *  - <b>file:</b>  The file to include relative to the template root
 *  - <b>nl2br:</b> If set, replaces newlines in the file with <br> tags
 *  - <b>raw:</b>   If set, will not escape the output
 *
 * @param array $options  The function options
 * @param Smary $smarty   The Smarty instance
 *
 * @return string
 */
function smarty_function_include_text($options, $smarty) {
  if (!isset($options['file'])) {
    $smarty->trigger_error('Missing required parameter "file" in function "include_text"');
    return;
  }

  $filename  = $smarty->template_dir . '/' . $options['file'];
  $txt = file_get_contents($filename);
  if (!isset($options['raw']))
    $txt = htmlspecialchars($txt);

  if (isset($options['nl2br']))
    $txt = nl2br($txt);

  return $txt;
}

?>