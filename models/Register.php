<?php
namespace Portal\Model\Register;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    // Data to be passed to twig
    $this->data = array(
      'method' => $_SERVER['REQUEST_METHOD'],
      'referer' => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $portal->site_url),
      'site_url' => $portal->site_url
    );

    // Check if we are submitting register form
    if ($this->data['method'] == "POST" && !isset($route['action'])) {

      // Data to be passed to twig
      $this->data['action'] = 'register-submit';

      // Store the email address and password
      if (isset($_POST['emailAddress'])) { $this->data['emailAddress'] = $_POST['emailAddress']; }
      if (isset($_POST['accountPassword'])) { $this->data['accountPassword'] = $_POST['accountPassword']; }
    }

    // Check if we are displaying registration form
    elseif ($this->data['method'] == "GET" && !isset($route['action'])) {

      // Data to be passed to twig
      $this->data['action'] = 'register-form';
    }

    // Check if we are activating an account
    elseif ($this->data['method'] == "GET" && isset($route['action']) && $route['action'] == 'activate') {

      // Data to be passed to twig
      $this->data['action'] = 'activate';
      $this->data['activation_key'] = $route['id'];
    }
    // Set template file
    $this->template = "register.html.twig";
  }
}

?>
