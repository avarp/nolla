<?php declare(strict_types=1);
namespace Controller;

use \Nyholm\Psr7\Factory\Psr17Factory;
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;
use \Nyholm\Psr7\Stream;
use \Nolla\Core\App as NollaCore;


/**
 * Basis for all controllers across the system.
 * You should develop your controllers extennding this class.
 */
abstract class AbstractController
{
  /**
   * @var RequestInterface currentrly processing request
   */
  protected $request;




  /**
   * Instantiate controller
   */
  public function __construct(RequestInterface $request)
  {
    $this->request = $request;
  }




  /**
   * Load controller
   * @param string $controllerPath path to controller and method
   * @param array $params params passing to method
   * @return mixed depends on controller
   */
  public function loadController(string $controllerPath, array $params=[])
  {
    [$class, $method] = NollaCore::parsePath($controllerPath, '\\Controller\\');
    if (class_exists($class) && method_exists($class, $method)) {
      $controller = new $class($this->request);
      return $controller->$method(...$params);
    } else {
      throw new \BadMethodCallException("The controller $class->$method() does not exist.");
    }
  }


  

  /**
   * Instantiate another controller
   * @param string $controllerPath path to controller and method
   * @return object instance of controller
   */
  public function newController(string $controllerPath): object
  {
    [$class, $_] = NollaCore::parsePath($controllerPath, '\\Controller\\');
    if (class_exists($class)) {
      return new $class($this->request);
    } else {
      throw new \InvalidArgumentException("The controller $class does not exist.");
    }
  }




  /**
   * Create PSR7 response from string
   * @param string $string data for a body of a response
   * @return ResponseInterface PSR7 response
   */
  protected function createResponseFromString(string $string): ResponseInterface
  {
    return (new Psr17Factory)
      ->createResponse(200)
      ->withBody(
        Stream::create($string)
      );
  }




  /**
   * Create PSR7 response from string with MIME-type text/plain
   * @param string $text data for a body of a response
   * @return ResponseInterface PSR7 response
   */
  protected function textPlain(string $text): ResponseInterface
  {
    return $this->createResponseFromString($text)->withHeader('Content-type', 'text/plain');
  }




  /**
   * Create PSR7 response from HTML with MIME-type text/html
   * @param string $text data for a body of a response
   * @return ResponseInterface PSR7 response
   */
  protected function textHtml(string $html): ResponseInterface
  {
    return $this->createResponseFromString($html)->withHeader('Content-type', 'text/html');
  }




  /**
   * Create PSR7 JSON response from array or object with MIME-type application/json
   * @param mixed $object any JSON-representable data
   * @return ResponseInterface PSR7 response
   */
  protected function json($object): ResponseInterface
  {
    return $this->createResponseFromString(json_encode($object))->withHeader('Content-type', 'application/json');
  }




  /**
   * Create PSR7 JSONP response from array or object with MIME-type application/javascript
   * @param mixed $object any JSON-representable data
   * @param string $padding name of function to call on the browser's side
   * @return ResponseInterface PSR7 response
   */
  protected function jsonp($object, string $padding): ResponseInterface
  {
    return $this->createResponseFromString($padding.'('.json_encode($object).');')->withHeader('Content-type', 'application/javascript');
  }




  /**
   * Load view template
   * @param string $view name of view without extension
   * @param array $data any variables to be visible in the scope of the view
   * @return string result of executing template view
   */
  protected function loadView(string $view, array $data=[]): string
  {
    ob_start();
    extract($data);
    include('view/'.$view.'.php');
    return ob_get_clean();
  }
}