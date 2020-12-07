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