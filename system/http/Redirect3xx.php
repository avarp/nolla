<?php
namespace System\Http;

class Redirect3xx extends ThrowableHttpStatus 
{
  protected const REASON = [
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => 'Switch Proxy',
    307 => 'Temporary Redirect'
  ];

  public $location;

  public function __construct($code, $location)
  {
    $this->location = $location;
    parent::__construct($code);
  }
}