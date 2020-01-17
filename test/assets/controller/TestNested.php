<?php
namespace Controller;

class TestNested extends AbstractController
{

  public function getTestNumber()
  {
    return 2;
  }

  public function getTestStatus()
  {
    return 'completed';
  }
}