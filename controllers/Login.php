<?php
namespace Portal\Controller\Login;

class Controller
{
  private $model;

  public function __construct($model) {
    global $portal;

    $this->model = $model;

    // Re-direct if already logged in
    if ($portal->is_logged_in()) {
      header("Location: " . $portal->site_url . "/tickets");
      die();
    }

    // Check if attempting to login
    elseif ($this->model->data['method'] == 'POST') {

      // Check if user and password is given
      if (!empty($_POST['email']) && !empty($_POST['password'])) {

        // Create db controller
        $db = new \PortalDB();
        $db->connect();

        // Get login credentials
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check database for e-mail/password
        try {
          $stmt = $db->pdo->prepare('SELECT * FROM accounts WHERE email = :email');
          $stmt->execute(['email' => $email]);

          $results = $stmt->fetch();
          $dbpass = $results['password'];

        }

        // Store any errors in the error log
        catch(PDOException $e) {
          error_log($e->getMessage(),0);

          $this->model->data['error'] = "true";
          $this->model->data['error_message'] = "There was an error processing your request. Please contact our support for more assistance.";

          throw $e;
        }
        
        if (empty($results)) {
          $this->model->data['error'] = "true";
          $this->model->data['error_message'] = "This email address does not exist in our database. Please register your account before attempting to login.";
        }
        elseif ($results['is_suspended'] == true) {
          $this->model->data['error'] = "true";
          $this->model->data['error_message'] = "This account is currently suspended. Please contact our support team directly for more assistance.";
        }
        elseif ($results['is_active'] == false) {
          $this->model->data['error'] = "true";
          $this->model->data['error_message'] = "This account is not activated. Please check your email inbox for the activation email.";
        }
        // Verify if passwords match
        elseif (password_verify($password, $dbpass)) {

          // Set session data if login is valid
          $_SESSION['logged_in'] = true;
          $_SESSION['hs_id'] = $results['hs_id'];
          $_SESSION['email'] = $results['email'];

          if (isset($_SESSION['redirect']) && $_SESSION['redirect'] == "true") {
            $redirect_url = $_SESSION['redirect_url'];
            $_SESSION['redirect'] = "false";
          }
          else { $redirect_url = $portal->site_url . '/tickets'; }

          // Re-direct home
          header("Location: " . $redirect_url);
          die();
        }

        // Set variable for twig to evaluate if login failed
        else { $this->model->data['failed_login'] = true; }

      }
    }
  }
}


?>
