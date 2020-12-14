<?php
define("ROOT_DIR", __DIR__);
require('vendor/autoload.php');
use \System\App;

App::sendHttpResponse(
  App::processHttpRequest(
    App::httpRequestFromGlobals()
  )
);