<?php
namespace Controller;

abstract class AbstractController
{
  protected $request;
  protected $response;

  public function __construct($request = null, $response = null)
  {
    $this->request = $request;
    $this->response = $response;
  }

  public function loadController($controller)
  {
    $a = explode('::', str_replace('/', '\\', $controller));
    $class = '\\Controller\\'.$a[0];
    $method = isset($a[1]) ? $a[1] : 'index';

    if (class_exists($class) && method_exists($class, $method)) {
      $instance = new $class($this->request, $this->response);
      return $instance->$method();
    } else {
      throw new \BadMethodCallException("The controller $class->$method() does not exist.");
    }
  }

  public function newController($controller)
  {
    $class = '\\Controller\\'.str_replace('/', '\\', $controller);
    if (class_exists($class)) {
      return new $class($this->request, $this->response);
    } else {
      throw new \InvalidArgumentException("The controller $class does not exist.");
    }
  }

  protected function setResponseString($string)
  {
    $this->response = $this->response->withBody(
      \Nyholm\Psr7\Stream::create($string)
    );
  }

  protected function setResponseText($text)
  {
    $this->setResponseString($text);
    $this->response = $this->response->withHeader('Content-type', 'text/plain');
  }

  protected function setResponseHtml($html)
  {
    $this->setResponseString($html);
    $this->response = $this->response->withHeader('Content-type', 'text/html');
  }

  protected function setResponseJson($object)
  {
    $this->setResponseString(json_encode($object));
    $this->response = $this->response->withHeader('Content-type', 'application/json');
  }

  protected function setResponseJsonp($object, $padding)
  {
    $this->setResponseString($padding.'('.json_encode($object).');');
    $this->response = $this->response->withHeader('Content-type', 'application/javascript');
  }

  protected function loadView($view, $data)
  {
    ob_start();
    extract($data);
    include('view/'.$view.'.php');
    return ob_get_clean();
  }
}