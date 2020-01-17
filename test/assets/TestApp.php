<?php
namespace System;

class TestApp extends AbstractApp
{
  protected function createRoutingMap($router)
  {
    $router->addRoute('GET', '/', 'Test');
    $router->addRoute('GET', '/nested', 'Test::nested');
    $router->addRoute('GET', '/middleware-test', 'Test::middleware');
    $router->addRoute('GET', '/article/{id:\d+}[/{title}]', [
      'controller' => 'Test::routingParameters',
      'middleware' => ['RedirectFromId123To124'],
      'errorHandlers' => [
        404 => 'Test::articleNotFound'
      ]
    ]);
    $router->addRoute('GET', '/not-existing-controller', 'Test::blabla');
    $router->addRoute('GET', '/bad-controller', 'Test::badMethod');
    $router->addRoute('GET', '/not-existing-error-handler', 'Test::Error418');
    $router->addRoute('GET', '/not-existing-middleware', [
      'controller' => 'Test',
      'middleware' => ['NotExistingMiddleware']
    ]);
    $router->addRoute('GET', '/bad-parameter-name/{controller}', 'Test');
    $router->addRoute('GET', '/bad-route', [1, 2, 3]);
    $router->addRoute('GET', '/test-view', 'Test::testView');
  }

  protected function defineMiddleware()
  {
    return [
      'Test'
    ];
  }

  protected function defineErrorHandlers()
  {
    return [
      404 => 'Test::error404'
    ];
  }
}