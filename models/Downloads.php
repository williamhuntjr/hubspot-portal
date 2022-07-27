<?php
namespace Portal\Model\Downloads;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    $this->data = array(
      'logged_in' => $portal->is_logged_in(),
      'software' => $route['software'],
      'version' => $route['version'],
      'platform' => $route['platform']
    );

    // Set template file
    $this->template = "downloads.html.twig";
  }
}

?>
