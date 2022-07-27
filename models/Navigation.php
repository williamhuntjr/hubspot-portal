<?php
namespace Portal\Model\Navigation;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    // Set current page to determine which navigation tab will be active

    // Data to be passed to twig
    $this->data = array(
      'site_url' => $portal->site_url,
      'current_page' => $portal->current_page,
      'logged_in' => $portal->is_logged_in()
    );

    // Set template file
    $this->template = 'navigation.html.twig';

  }
}

?>
