<?php
namespace Portal\Controller\Template;

class Controller
{
  private $model;

  public function __construct($model) {
    $this->model = $model;
  }
}

?>
