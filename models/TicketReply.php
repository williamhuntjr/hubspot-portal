<?php
namespace Portal\Model\TicketReply;

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

    // Check if we are submitting a reply
    if ($portal->is_logged_in() && $this->data['method'] == "POST") {

      // Get the data needed to send reply
      $subject = $_POST['lastSubject'];
      $lastEmail = $_POST['lastEmail'];
      $lastId = strip_tags($_POST['lastId']);
      $reply = $_POST['reply'];
      $references = $_POST['lastReferences'];

      // Generate custom headers
      $headers = array(
        'In-Reply-To' => $lastId,
        'References' => $references
      );

      // Send the response
      $portal->emailUser($lastEmail, $subject, $reply, $reply, $_SESSION['hs_id'], $headers);

      // Sleep and wait to reload page while response sends and is registered on Hubspot
      sleep(20);

      // Get the URL of the last viewed ticket and re-direct
      $last_url = $_SESSION['last_url'];
      if (isset($last_url) && !empty($last_url)) {
        // Re-direct to previous page
        $_SESSION['last_url'] = '';
        header('Location: ' . $last_url);
        exit;
      }

      // Go home if no last url exists
      else  {
        header('Location: ' . $portal->site_url);
      }
    }

    // Set template file
    $this->template = "template.html.twig";
  }
}

?>
