<?php
/** @package smarty */

/**
 * number_format Smarty modifier
 *
 * Formats a number with decimal and thousand separator
 *
 * <b>Example:</b>
 * <code>
 *   {$basic_number|number_format}         {*  1,234    *}
 *   ${$currency|number_format:2}          {* $1,234.00 *}
 *   {$euro_style|number_format:2:',':'.'} {*  1.234,00 *}
 * </code>
 *
 * @param float  $number        The number to format
 * @param int    $decimals      Number of decimal places to include (precision), default is 0
 * @param string $decimal_sep   Decimal separator character
 * @param string $thousands_sep Thousands separator character
 *
 * @return string
 */
function smarty_modifier_number_format($number, $decimals = false, $decimal_sep = false, $thousands_sep = false) {
  if ($thousands_sep !== false)
    return number_format($number, $decimals, $decimal_sep, $thousands_sep);
  if ($decimal_sep !== false)
    return number_format($number, $decimals, $decimal_sep);
  if ($decimals !== false)
    return number_format($number, $decimals);

  return number_format($number);
}

?>