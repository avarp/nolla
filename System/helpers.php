<?php
/**
 * You can put here your useful functions
 */



 
/**
 * Detetc whether string is an URL
 * @param string $s tested string
 * @return bool result
 */
function isUrl(string $s): bool
{
  return $s[0] == '/' || substr($s, 0, 4) == 'http';
}




/**
 * Prepare variables for exporting into the javascript global scope (window object)
 * @param array $data keys are names of variables
 * @return string javascript code
 */
function exportToJs(array $data): string
{
  $lines = [];
  foreach ($data as $key => $value) {
    $line = "window.$key = ";
    if (is_scalar($value)) {
      if (is_string($value)) {
        $line .= '"' . str_replace('"', '\"', $value) . '"';
      } else {
        $line .= $value;
      }
    } else {
      $line .= json_encode($value);
    }
    $lines[] = $line;
  }
  return implode(";\n", $lines);
}