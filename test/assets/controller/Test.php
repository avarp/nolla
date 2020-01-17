<?php
namespace Controller;

class Test extends AbstractController
{

  public function index()
  {
    $this->setResponseText('Test 1 completed');
    return $this->response;
  }

  public function nested()
  {
    $this->setResponseText(
      'Test '.
      $this->loadController('TestNested::getTestNumber').' '.
      $this->loadController('TestNested::getTestStatus')
    );
    return $this->response;
  }

  public function middleware()
  {
    $this->setResponseText(
      'Test '.
      $this->request->getAttribute('TestNumber')
    );
    return $this->response;
  }

  public function error404()
  {
    $this->setResponseText('Test 4 completed');
    return $this->response;
  }

  public function routingParameters()
  {
    $id = $this->request->getAttribute('id');
    if ($id > 200) {
      throw new \System\Http\Error4xx(404);
    }

    $title = $this->request->getAttribute('title');
    $body = "id=$id";
    if ($title) {
      $body .= ", title=$title";
    }
    $this->setResponseText($body);
    return $this->response;
  }

  public function articleNotFound()
  {
    $this->setResponseText('Article not found');
    return $this->response;
  }

  public function badMethod()
  {
    return 'blablabla';
  }

  public function Error418()
  {
    throw new \System\Http\Error4xx(418);
  }

  public function testView()
  {
    $this->setResponseText(
      $this->loadView('testView', ['testNum' => 15])
    );
    return $this->response;
  }
}