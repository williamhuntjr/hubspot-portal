<?php
namespace Portal\Model\Template;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    // Set template file
    $this->template = "template.html.twig";
  }
}

?>
