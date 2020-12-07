<?php
namespace System;

class App extends \Nolla\Core\App
{
  protected static function createRoutingMap($router): void
  {
    $router->addRoute('GET', '/', 'Page::home');
  }

  protected static function defineMiddleware(): array
  {
    return [];
  }

  protected static function defineErrorHandlers(): array
  {
    return [
      404 => 'Page::notFound'
    ];
  }
}