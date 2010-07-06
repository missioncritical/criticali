<?php

/**
 * obfuscate Smarty modifier
 *
 * Obfuscates a string using javascript.  Requires the function base64decode to be defined.
 *
 * @param string $value The value to obfuscate
 *
 * @return string
 */
function smarty_modifier_obfuscate($value) {
  
  // first, break the string into a number of random segments
  $segments = array();
  $idx = 0;
  $consumed = 0;
  $valueLen = strlen($value);
  
  while ($consumed < $valueLen) {
    $segmentSize = rand(3, 12);
    if ($segmentSize > ($valueLen - $consumed))
      $segmentSize = $valueLen - $consumed;
    $segment = substr($value, $consumed, $segmentSize);
    $consumed += $segmentSize;
    $segments["s$idx"] = $segment;
    $idx += 1;
  }
  
  // now randomize the segments
  uasort($segments, 'obfuscate_random_sort');
  
  // create the assembly and output code
  $code = "(function () { var data = {";
  $parts = array();
  foreach ($segments as $key=>$segment) { $parts[] = "$key: \"".addslashes($segment)."\""; }
  $code .= implode(',', $parts) . "}; document.write(";
  $parts = array();
  for ($i = 0; $i < $idx; $i++) { $parts[] = "data.s$i"; }
  $code .= implode(',', $parts) . "); })();";
  
  // now encode it and assemble the final javascript
  $finalCode = "<script type=\"text/javascript\"><!--\n eval(base64decode('".base64_encode($code)."')); \n--></script>";
  
  return $finalCode;
}

/**
 * Randomized sort function used by the obfuscate modifier
 */
function obfuscate_random_sort($a, $b) {
  return (rand(0, 15) - 8);
}

?>