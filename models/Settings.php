<?php
namespace Portal\Model\Settings;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    $this->data = array(
      'logged_in' => $portal->is_logged_in(),
      'method'=> $_SERVER['REQUEST_METHOD']
    );

    if (!$portal->is_logged_in()) { $portal->go_login(); }
    else {
      // Create database object
      $this->db = new \PortalDB();
      $this->db->connect();

      // Prepare SQL statement for sanitization
      $stmt = $this->db->pdo->prepare('SELECT * FROM accounts WHERE hs_id = :hs_id');

      // Execute query
      $stmt->execute(['hs_id' => $_SESSION['hs_id']]);

      // Store results
      $results = $stmt->fetch();
      $this->data['email'] = (isset($results['smtp_email']) ? $results['smtp_email'] : '');
      $this->data['password'] = (isset($results['smtp_password']) ? $results['smtp_password'] : '');
      $this->data['server'] = (isset($results['smtp_server']) ? $results['smtp_server'] : '');
      $this->data['port'] = (isset($results['smtp_port']) ? $results['smtp_port'] : '');
      $this->data['ssl'] = (isset($results['smtp_ssl']) ? $results['smtp_ssl'] : false);

      // Get existing password
      if (isset($results['smtp_password']) && !empty($results['smtp_password'])) {
        $plaintext = "";
        $encoded = "";
        $key = hex2bin(constant('EMAIL_KEY'));
        $encoded = $results['smtp_password'];
        $decoded = base64_decode($encoded);
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
      }

      // Generate placeholder for the form
      $length = (isset($_POST['emailPassword']) && !empty($_POST['emailPassword']) ? strlen($_POST['emailPassword']) : (isset($plaintext) ? strlen($plaintext) : 0) );
      $placeholder = "";
      for ($x = 0; $x < $length; $x++) {
        $placeholder .= "*";
      }

      $this->data['placeholder'] = $placeholder;

      // If posting, then store data needed for controller
      if ($this->data['method'] == "POST") {
        if (isset($_POST['emailAddress']) && !empty($_POST['emailAddress']) && isset($_POST['emailPassword']) && !empty($_POST['emailPassword']) && isset($_POST['outgoingServer']) && !empty($_POST['outgoingServer']) && isset($_POST['outgoingPort']) && !empty($_POST['outgoingPort'])) {

          $this->data['settings'] = array(
            'email' => $_POST['emailAddress'],
            'server' => $_POST['outgoingServer'],
            'port' => $_POST['outgoingPort'],
            'password' => $_POST['emailPassword'],
            'ssl' => (isset($_POST['enableSSL']) && $_POST['enableSSL'] == "on" ? true : false)
          );
        }
        else {
          $this->data['error'] = "true";
          $this->data['error_message'] = "You are missing required fields.";
        }
      }
    }
    // Set template file
    $this->template = "settings.html.twig";
  }
}

?>
