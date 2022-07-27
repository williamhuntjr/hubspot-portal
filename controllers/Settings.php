<?php
namespace Portal\Controller\Settings;

class Controller
{
  private $model;

  public function __construct($model) {
    global $portal;

    $this->model = $model;

    if ($this->model->data['method'] == "POST" && $portal->is_logged_in()) {
      // Create database object
      $this->db = new \PortalDB();
      $this->db->connect();

      $settings = $this->model->data['settings'];
      $settings['user'] = $_SESSION['email'];
      $settings['hs_id'] = $_SESSION['hs_id'];

      if (isset($this->model->data['error']) && $this->model->data['error'] == "true") {
      }
      elseif (isset($settings['email']) && isset($settings['server']) && isset($settings['port']) && isset($settings['ssl']) && isset($settings['password'])) {
        $key = hex2bin(constant('EMAIL_KEY'));

        $password = $settings['password'];
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($password, $nonce, $key);
        $encoded = base64_encode($nonce . $ciphertext);

        $settings['password'] = $encoded;
        $settings['ssl'] = ($settings['ssl'] == true ? 1 : 0);

        try {

          // Prepare SQL query for sanitization
          $stmt = $this->db->pdo->prepare("UPDATE accounts SET smtp_email=:email, smtp_password=:password, smtp_server=:server, smtp_port=:port, smtp_ssl=:ssl WHERE email=:user AND hs_id=:hs_id");
          // Execute query
          $stmt->execute($settings);
        }

        // Store any errors in the error log
        catch(PDOException $e) {
          error_log($e->getMessage(),0);

          $this->model->data['error'] = "true";
          $this->model->data['error_message'] = "There was an error submitting your request. Please contact our support for more assistance.";

          throw $e;
        }
        $this->model->data['email'] = $settings['email'];
        $this->model->data['server'] = $settings['server'];
        $this->model->data['port'] = $settings['port'];
        $this->model->data['ssl'] = $settings['ssl'];
        $this->model->data['password'] = $settings['password'];
      }
    }
  }
}

?>
