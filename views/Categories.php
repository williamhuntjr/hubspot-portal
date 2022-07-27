<?php
namespace XTC\View\Categories;

class View
{
  private $model;
  private $controller;
  
  public function __construct($controller,$model) {
    $this->controller = $controller;
    $this->model = $model;
  }

  public function render(){
    global $xtc;
    echo $xtc->twig->render($this->model->template, $this->model->data);
  }
}