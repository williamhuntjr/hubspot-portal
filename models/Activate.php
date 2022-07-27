<?php
namespace Portal\Model\Activate;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    $this->data = array(
      'activation_token' => $route['token'],
      'site_url' => $portal->site_url
    );

    $this->template = "activate.html.twig";
  }
}

?>
