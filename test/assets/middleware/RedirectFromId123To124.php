<?php
namespace Middleware;

Class RedirectFromId123To124
{
  public function onRoutingDone($request)
  {
    $id = $request->getAttribute('id');
    $title = $request->getAttribute('title');

    if ($id == 123) {
      $newUrl = '/article/124';
      if ($title) $newUrl .= '/'.$title;
      throw new \System\Http\Redirect3xx(301, $newUrl);
    }
  }
}