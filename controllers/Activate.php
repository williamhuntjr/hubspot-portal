<?php
namespace Portal\Controller\Activate;

class Controller
{
  private $model;

  public function __construct($model) {
    global $portal;

    $this->model = $model;

    // Create database object
    $this->db = new \PortalDB();
    $this->db->connect();

    // Execute activate function
    $this->activate($this->model->data['activation_token']);
  }

  public function activate($activation_token) {

    // Query the local database for details about the activation key
    try {

      // Prepare SQL statement for sanitization
      $stmt = $this->db->pdo->prepare('SELECT * FROM accounts WHERE activation_token = :activation_token');

      // Execute query
      $stmt->execute(['activation_token' => $activation_token]);

      // Store results
      $results = $stmt->fetch();
    }
    // Store any errors in the error log
    catch(PDOException $e) {
      error_log($e->getMessage(),0);

      $this->model->data['error'] = "true";
      $this->model->data['error_message'] = "There was an error submitting your request. Please contact our support team directly for more assistance.";

      throw $e;
    }
    // Store error message for if key does not exist
    if (empty($results)) {
      $this->model->data['error'] = "true";
      $this->model->data['error_message'] = "The activation token is invalid. Please check your activation email and be sure you are using the proper URL.";
    }

    // Store error message for if account has already been activated
    elseif (isset($results['is_active']) && $results['is_active']) {
      $this->model->data['error'] = "true";
      $this->model->data['error_message'] = "The account for the specified email address is already activated. Please log in to continue.";
    }

    // Proceed to activate account if not activated and not suspended
    elseif (isset($results['is_active']) && !$results['is_active'] && isset($results['is_suspended']) && !$results['is_suspended']) {
      // Query to activate account by updating activation column
      try {

        // Prepare SQL query for sanitization
        $stmt = $this->db->pdo->prepare("UPDATE accounts SET is_active='1' WHERE activation_token=:activation_token");

        // Execute query
        $stmt->execute(['activation_token' => $activation_token]);

      }
      // Store any errors in the error log
      catch(PDOException $e) {
        error_log($e->getMessage(),0);

        $this->model->data['error'] = "true";
        $this->model->data['error_message'] = "There was an error submitting your request. Please contact our support for more assistance.";

        throw $e;
      }

      // Store confirmation boolean
      $this->model->data['activated'] = "true";

    }
  }
}

?>
