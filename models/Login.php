<?php
namespace Portal\Model\Login;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    $this->data = array(
      'method' => $_SERVER['REQUEST_METHOD']
    );
    $this->template = "login.html.twig";
  }
}

?>
