<?php
namespace Portal\View\Tickets;

class View
{
  private $model;
  private $controller;

  public function __construct($controller,$model) {
    $this->controller = $controller;
    $this->model = $model;
  }

  public function render(){
    global $portal;
    echo $portal->twig->render($this->model->template, $this->model->data);
  }
}
