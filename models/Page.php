<?php
namespace Portal\Model\Page;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    // Data to be passed to twig
    $this->data = array(
      'logged_in' => $portal->is_logged_in(),
      'site_url' => $portal->site_url
    );

    // Check if we are on the homepage
    if ($route['page'] == '') {
      header('Location: ' . $portal->site_url . '/login');
      exit;

      $this->template = "homepage.html.twig";
    }
    elseif ($route['page'] == 'downloads') {

      if (!$this->data['logged_in']) { $portal->go_login(); }
      else { $this->template = "downloads.html.twig"; }
    }

    else {
      // Set browser header
      header("HTTP/1.0 404 Not Found");

      // Determine template and referer
      $this->template = "404.html.twig";

      // Data to be passed to twig
      $this->data = array(
        'referer' => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $portal->site_url),
        'site_url' => $portal->site_url
      );
    }
  }
}

?>
