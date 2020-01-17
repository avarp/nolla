<?php
namespace Controller;

class ExampleController extends AbstractController
{
  public function index()
  {
    $this->setResponseHtml('<h1>Nolla</h1>');
    return $this->response;
  }

  public function notFound()
  {
    $this->setResponseHtml('<h1>Error 404</h1>');
    return $this->response;
  }
}