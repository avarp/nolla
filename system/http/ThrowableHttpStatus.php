<?php
namespace System\Http;

abstract class ThrowableHttpStatus extends \Exception 
{

  protected const REASON = [];

  public function __construct($code) {
    if (isset(static::REASON[$code])) {
      $message = static::REASON[$code];
    } else {
      $message = 'Unknown status code';
    }
    parent::__construct($message, $code, null);
  }
}