<?php
namespace System;
use \System\Http\Error4xx;
use \System\Http\Redirect3xx;


abstract class AbstractApp
{
  protected $request;
  protected $dispatcher;
  protected $response;
  protected $middlewareArray;
  protected $errorHandlers;


  public function __construct()
  {
    $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
    $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
      $psr17Factory, // ServerRequestFactory
      $psr17Factory, // UriFactory
      $psr17Factory, // UploadedFileFactory
      $psr17Factory  // StreamFactory
    );
    $this->request = $creator->fromGlobals();
    $this->dispatcher = \FastRoute\simpleDispatcher(function($router) {
      $this->createRoutingMap($router);
    });
    $this->response = $psr17Factory->createResponse(200);
  }


  protected function getErrorHandler($errCode)
  {
    if (!isset($this->errorHandlers[$errCode])) {
      throw new \UnexpectedValueException("Error handler for error code $errCode not defined.");
    }
    return $this->errorHandlers[$errCode];
  }


  protected function runMiddleware($method)
  {
    foreach ($this->middlewareArray as $m) {
      $class = '\\Middleware\\'.str_replace('/', '\\', $m);
      if (!class_exists($class)) {
        throw new \UnexpectedValueException("The middleware $class does not exist. Check your implementation of createRoutingMap() and defineMiddleware() methods in \\System\\App class.");
      }
      if (method_exists($class, $method)) {
        $middleware = new $class();
        switch ($method) {
          case 'onStartup':
          case 'onRoutingDone':
          $result = $middleware->$method($this->request);
          if ($result instanceof \Psr\Http\Message\ServerRequestInterface) {
            $this->request = $result;
          }
          break;

          case 'onResponseCreated':
          $result = $middleware->$method($this->request, $this->response);
          if ($result instanceof \Psr\Http\Message\ResponseInterface) {
            $this->response = $result;
          }
          break;
        }
      }
    }
  }


  protected function runController()
  {
    $a = explode('::', str_replace('/', '\\', $this->request->getAttribute('controller')));
    $class = '\\Controller\\'.$a[0];
    $method = isset($a[1]) ? $a[1] : 'index';

    if (class_exists($class) && method_exists($class, $method)) {
      $controller = new $class($this->request, $this->response);
      $response = $controller->$method();
      if ($response instanceof \Psr\Http\Message\ResponseInterface) {
        $this->response = $response;
      } else {
        throw new \UnexpectedValueException("The controller $class->$method() returned unexpected value instead of instance of \\Psr\\Http\\Message\\ResponseInterface");
      }
    } else {
      throw new \BadMethodCallException("The controller $class->$method() does not exist.");
    }
  }


  protected function acceptError4xx($err)
  {
    $this->response = $this->response->withStatus(
      $err->getCode(),
      $err->getMessage()
    );
    $this->request = $this->request->withAttribute(
      'controller',
      $this->getErrorHandler($err->getCode())
    );
  }


  protected function acceptRoute($route)
  {
    foreach ($route[2] as $key => $value) {
      if ($key != 'controller') {
        $this->request = $this->request->withAttribute($key, $value);
      } else {
        throw new \UnexpectedValueException('Usage of routing map\'s placeholder {controller:...} is prohibited. Check implementation of createRoutingMap() in \\System\\App class.');
      }
    }
      
    if (is_string($route[1])) {
      $this->request = $this->request->withAttribute('controller', $route[1]);
    } elseif (is_array($route[1]) && isset($route[1]['controller']) && is_string($route[1]['controller'])) {
      
      $this->request = $this->request->withAttribute('controller', $route[1]['controller']);

      if (isset($route[1]['middleware']) && is_array($route[1]['middleware'])) {
        $this->middlewareArray = array_unique(array_merge(
          $this->middlewareArray, $route[1]['middleware']
        ));
      }

      if (isset($route[1]['errorHandlers']) && is_array($route[1]['errorHandlers'])) {
        $this->errorHandlers = $route[1]['errorHandlers'] + $this->errorHandlers;
      }

    } else {
      throw new \UnexpectedValueException('Router returned an unknown route type. Check implementation of createRoutingMap() in \\System\\App class.');
    }
  }


  public function run($request = null)
  {
    if ($request instanceof \Psr\Http\Message\ServerRequestInterface) {
      $this->request = $request;
    }
    $this->middlewareArray = $this->defineMiddleware();
    $this->errorHandlers = $this->defineErrorHandlers();

    try {

      try {
        $this->runMiddleware('onStartup');
      } catch (Error4xx $e) {
        $this->acceptError4xx($e);
      }
      
      if (empty($this->request->getAttribute('controller'))) {
        $route = $this->dispatcher->dispatch(
          $this->request->getMethod(),
          $this->request->getUri()->getPath()
        );
        if ($route[0] == \FastRoute\Dispatcher::FOUND) {
          $this->acceptRoute($route);
        } else {
          if ($route[0] == \FastRoute\Dispatcher::NOT_FOUND) {
            $e = new Error4xx(404);
          } elseif ($route[0] == \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            $e = new Error4xx(405);
          } else {
            throw new \UnexpectedValueException('Router returned an unknown error code '.$route[0]);
          }
          $this->acceptError4xx($e);
        }
      }

      try {
        $this->runMiddleware('onRoutingDone');
      } catch (Error4xx $e) {
        $this->acceptError4xx($e);
      }

      try {
        $this->runController();
      } catch (Error4xx $e) {
        $this->acceptError4xx($e);
        $this->runController();
      }

      $this->runMiddleware('onResponseCreated');

    } catch (Redirect3xx $redirect) {
      $this->response = $this->response->withStatus(
        $redirect->getCode(),
        $redirect->getMessage()
      )
      ->withHeader(
        'Location',
        $redirect->location
      );
    }
    return $this;
  }


  public function sendResponse()
  {
    // Remove all headers, but save them in array
    $headers = headers_list();
    header_remove();

    // Set cookies which go from $_COOKIE
    foreach ($headers as $h) {
      $p = strpos($h, ':');
      $name = trim(substr($h, 0, $p));
      $value = trim(substr($h, $p+1));
      if (strtolower($name) == 'set-cookie') {
        $this->response = $this->response->withAddedHeader($name, $value);
      }
    }

    // Set cookies for session id
    if (!isset($_COOKIE['PHPSESSID'])) {
      $id = session_id();
      $path = $this->request->getUri()->getPath();
      $this->response = $this->response->withAddedHeader(
        'Set-Cookie', "PHPSESSID=$id; path=/"
      );
    }

    // Set HTTP status line
    $r = $this->response;
    $http_line = sprintf('HTTP/%s %s %s',
      $r->getProtocolVersion(),
      $r->getStatusCode(),
      $r->getReasonPhrase()
    );

    // Send headers one by one
    header($http_line, true, $r->getStatusCode());
    foreach ($r->getHeaders() as $name => $values) {
      $values = array_unique($values);
      foreach ($values as $value) {
        header("$name: $value", false);
      }
    }

    // Rewind body stream
    $stream = $r->getBody();
    if ($stream->isSeekable()) {
      $stream->rewind();
    }

    // Send the body
    while (!$stream->eof()) {
      echo $stream->read(1024 * 8);
    }
  }


  public function getResponse()
  {
    return $this->response;
  }


  protected abstract function createRoutingMap($router);

  protected abstract function defineMiddleware();

  protected abstract function defineErrorHandlers();
}