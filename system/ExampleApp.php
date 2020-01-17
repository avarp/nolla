<?php
namespace System;

class ExampleApp extends AbstractApp
{
  protected function createRoutingMap($router)
  {
    $router->addRoute('GET', '/', 'ExampleController');
  }

  protected function defineMiddleware()
  {
    return [];
  }

  protected function defineErrorHandlers()
  {
    return [
      404 => 'ExampleController::notFound'
    ];
  }
}