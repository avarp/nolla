<?php
define("ROOT_DIR", __DIR__);
require('vendor/autoload.php');

App::sendHttpResponse(
  App::processHttpRequest(
    App::httpRequestFromGlobals()
  )
);