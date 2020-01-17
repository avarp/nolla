<?php
namespace Middleware;

class Test
{
  public function onStartup($request)
  {
    $url = $request->getUri()->getPath();
    if ($url == '/test-middleware') {
      $request = $request->withUri(
        $request->getUri()->withPath('/middleware-test')
      );
      return $request;
    }
  }

  public function onRoutingDone($request)
  {
    $request = $request->withAttribute('TestNumber', '3');
    return $request;
  }

  public function onResponseCreated($request, $response)
  {
    $url = $request->getUri()->getPath();
    if ($url == '/middleware-test') {
      $body = $response->getBody().' completed';
      $response = $response->withBody(
        \Nyholm\Psr7\Stream::create($body)
      );
      return $response;
    }
  }
}