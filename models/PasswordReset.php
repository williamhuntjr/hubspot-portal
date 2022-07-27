<?php
namespace Portal\Model\PasswordReset;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    $this->data = array(
      'method' => $_SERVER['REQUEST_METHOD'],
      'referer' => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $portal->site_url),
      'site_url' => $portal->site_url,
      'action' => (isset($route['action']) ? $route['action'] : ''),
      'token' => (isset($route['token']) ? $route['token'] : '')
    );

    // Set template file
    $this->template = "password.html.twig";
  }
}

?>
