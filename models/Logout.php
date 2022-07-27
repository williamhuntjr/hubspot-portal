<?php
namespace Portal\Model\Logout;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    $this->data = array(
      'action' => $route['controller']
    );

  }
}

?>
