<?php
namespace Portal\Controller\Tickets;

class Controller
{
  private $model;

  public function __construct($model) {
    $this->model = $model;

    global $portal;

  }
}

?>
