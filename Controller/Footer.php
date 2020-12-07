<?php
namespace Controller;

class Footer extends AbstractController
{
  protected static $data = [
    'scripts' => []
  ];

  public static function addScript(string $script)
  {
    self::$data['scripts'][] = $script;
  }

  public function index()
  {
    return $this->loadView('footer', self::$data);
  }
}