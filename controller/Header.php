<?php
namespace Controller;


class Header extends AbstractController
{
  protected static $data = [
    'title' => 'Untitled',
    'lang' => 'en',
    'dir' => 'ltr',
    'meta' => [],
    'scripts' => [],
    'styles' => []
  ];

  public function index()
  {
    return $this->loadView('header', self::$data);
  }

  public static function setTitle(string $title)
  {
    self::$data['title'] = $title;
  }

  public static function setLang(string $lang)
  {
    self::$data['lang'] = $lang;
  }

  public static function addMeta(string $tag)
  {
    self::$data['meta'][] = $tag;
  }

  public static function addScript(string $script)
  {
    self::$data['scripts'][] = $script;
  }

  public static function addStyle(string $style)
  {
    self::$data['styles'][] = $style;
  }
}