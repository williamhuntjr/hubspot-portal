<?php
namespace Portal\Controller\Logout;

class Controller
{
  private $model;

  public function __construct($model) {
    global $portal;

    $this->model = $model;

    // Check if we are logging out
    if ($this->model->data['action'] == 'logout') {
      session_destroy();

      header("Location: " . $portal->site_url);
      die();
    }
  }
}

?>
