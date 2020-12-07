<?php
require('vendor/autoload.php');
use \System\App;

App::sendHttpResponse(
  App::processHttpRequest(
    App::httpRequestFromGlobals()
  )
);