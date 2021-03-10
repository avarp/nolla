<?php declare(strict_types=1);
namespace Controller;

use \Psr\Http\Message\ResponseInterface;

class PageController extends AbstractController
{
  /**
   * Build page from header, body and footer
   * @param string $body HTML placed between header and footer
   * @return ResponseInterface
   */
  protected function page($body): ResponseInterface
  {
    return $this->textHtml(
      $this->loadController('Header')
      .$body
      .$this->loadController('Footer')
    );
  }




  public function home()
  {
    HeaderController::setTitle('Nolla');
    return $this->page($this->loadView('home'));
  }




  public function notFound()
  {
    HeaderController::setTitle('Error 404');
    return $this->page($this->loadView('404'));
  }
}
