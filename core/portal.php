<?php
namespace Portal;

// Load composer packages
require_once('assets/vendor/autoload.php');

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include Twig
use Twig_Environment;
use Twig_Loader_Filesystem;

// Load router
require_once('router.php');
require_once('hubspot.php');
require_once('database.php');

class Core
{
  public $router;
  public $loader;
  public $twig;
  public $options;

  // Globals
  public $site_domain;
  public $current_page;
  public $site_url;
  public $site_folder;
  public $ssl_enabled;

  public $tickets;
  public $tickets_open;
  public $tickets_closed;

  public function __construct() {

    // Initiate routing object
    $this->router = new Core\Router();
    $this->options = array(
      'strict_variables' => false,
      'debug' => false,
      'cache'=> false
    );

    // Globals
    $this->site_folder = constant('SITE_FOLDER');
    $this->ssl_enabled = constant('USE_SSL');
    $this->site_domain = constant('SITE_DOMAIN');
    $this->site_url = ($this->ssl_enabled == true ? 'https://' : 'http://') . $this->site_domain . (isset($this->site_folder) === true && $this->site_folder !== '' ? '/' . $this->site_folder : '');
    $this->logged_in = (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == "true" ? true : false);

    // Initiate hubspot API
    $this->api = new Core\HubspotAPI(constant('API_KEY'));

    // Set up twig templating
    $this->loader = new Twig_Loader_Filesystem(__DIR__.'/../templates');
    $this->twig = new Twig_Environment($this->loader, $this->options);

    // Create database object
    $this->db = new \PortalDB();
  }

  public function header($route) {

    // Load required MVC frameworks
    require_once('models/Header.php');
    require_once('views/Header.php');
    require_once('controllers/Header.php');

    // Initiate MVC objects
    $model = new Model\Header\Model($route);
    $controller = new Controller\Header\Controller($model);
    $view = new View\Header\View($controller, $model);

    // Display page
    $view->render();

  }
  public function navigation($route) {

    // Load required MVC frameworks
    require_once('models/Navigation.php');
    require_once('views/Navigation.php');
    require_once('controllers/Navigation.php');

    // Initiate MVC objects
    $model = new Model\Navigation\Model($route);
    $controller = new Controller\Navigation\Controller($model);
    $view = new View\Navigation\View($controller, $model);

    // Display page
    $view->render();

  }

  public function page($route) {

    // Load required MVC frameworks
    require_once('models/Page.php');
    require_once('views/Page.php');
    require_once('controllers/Page.php');

    // Initiate MVC objects
    $model = new Model\Page\Model($route);
    $controller = new Controller\Page\Controller($model);
    $view = new View\Page\View($controller, $model);

    // Display page
    $view->render();

  }
  public function controller($route) {

    // Determine which controller name to use for given route
    if ($route['controller'] == 'login') { $class = "Login"; }
    if ($route['controller'] == 'logout') { $class = "Logout"; }
    if ($route['controller'] == 'tickets') { $class = "Tickets"; }
    if ($route['controller'] == 'register') { $class ="Register"; }
    if ($route['controller'] == 'activate') { $class ="Activate"; }
    if ($route['controller'] == 'downloads') { $class = "Downloads"; }
    if ($route['controller'] == 'settings') { $class = "Settings"; }
    if ($route['controller'] == 'send-response') { $class = "TicketReply"; }
    if ($route['controller'] == 'reset-password') { $class = "PasswordReset"; }

    // Load required MVC frameworks
    require_once('models/'. $class . '.php');
    require_once('views/'. $class . '.php');
    require_once('controllers/'. $class .'.php');

    // Initiate MVC objects
    $model_name = 'Portal\Model\\' . $class .'\Model';
    $model = new $model_name($route);

    $controller_name = 'Portal\Controller\\' . $class .'\Controller';
    $controller = new $controller_name($model);

    $view_name = 'Portal\View\\' . $class .'\View';
    $view = new $view_name($controller, $model);

    // Display page
    $view->render();

  }
  public function footer($route) {

    // Load required MVC frameworks
    require_once('models/Footer.php');
    require_once('views/Footer.php');
    require_once('controllers/Footer.php');

    // Initiate MVC objects
    $model = new Model\Footer\Model($route);
    $controller = new Controller\Footer\Controller($model);
    $view = new View\Footer\View($controller, $model);

    // Display page
    $view->render();

  }

  // Hubspot API properties list
  function TicketProperties() {
    $params = array(
      'subject',
      'content',
      'createdate',
      'closed_date',
      'hs_ticket_category',
      'hs_ticket_id',
      'hs_ticket_priority',
      'hs_pipeline',
      'hs_pipeline_stage',
      'hubspot_owner_id'
    );
    $properties = '';

    // Loop through property list and build the URL block
    foreach ($params as &$value) {
      $properties = $properties . '&properties=' . $value;
    }
    return $properties;
  }

  // Get all tickets for contact
  function getTickets($id, $offset)
  {
    global $api;

    $url = '/crm-associations/v1/associations/' . $id . '/HUBSPOT_DEFINED/15';
    $params = '&limit=1' . ($offset != '' ? '&offset=' . $offset : '');

    $results = $this->api->get($url,$params);
    return $results;
  }

  function loopTickets($id, $offset, $ticketsList) {
    $tickets = $this->getTickets($id, $offset);
    foreach ($tickets as &$ticket) {
       if (!empty($ticket[0])) { array_push($ticketsList, $ticket[0]); }
    }

    // Check if there are remaining pages
    if ($tickets['hasMore'] == true) {
      $this->loopTickets($id, $tickets['offset'], $ticketsList);
    }
   else { $this->tickets = $ticketsList; }
  }

  function getTicket($id) {
    $url = '/crm-objects/v1/objects/tickets/' . $id;
    $params = $this->TicketProperties();

    $results = $this->api->get($url,$params);
    return $results;
  }

  function getTicketEmails($id) {
    $url = '/crm-associations/v1/associations/' . $id . '/HUBSPOT_DEFINED/17';
    $params = '&limit=100';

    $results = $this->api->get($url,$params);

    $ticket_emails = array();

    $associations = $results['results'];

    foreach ($associations as &$association) {
      $engagement = $this->getTicketEngagement($association);
      $engagement_type = $engagement['engagement']['type'];

      if ($engagement_type == 'EMAIL' || $engagement_type == 'INCOMING_EMAIL') {

        $end_element = end($ticket_emails);

        $end_timestamp = (!empty($end_element['engagement']['createdAt']) ? $end_element['engagement']['createdAt'] : '0');
        $this_timestamp = $engagement['engagement']['createdAt'];

        if ($this_timestamp > $end_timestamp) {
          array_push($ticket_emails, $engagement);
        }
        else {
          if (count($ticket_emails) == 0) {
            array_push($ticket_emails, $engagement);
          }
          else {
            $count = count($ticket_emails);
            $current = $count - 1;
            $ticket_emails = array_insert($ticket_emails, $engagement, $current);
          }
        }
      }
    }
    return $ticket_emails;

  }

  function getTicketEngagement($id) {
    $url = '/engagements/v1/engagements/' . $id;
    $params = '';

    $results = $this->api->get($url,$params);
    return $results;
  }

  function filterTickets() {
    $url = '/crm-pipelines/v1/pipelines/tickets';
    $params = '';

    $pipelines = $this->api->get($url,$params);

    $this->tickets_open = array();
    $this->tickets_closed = array();

    foreach ($this->tickets as &$ticket) {
      $ticket_details = $this->getTicket($ticket);

      foreach ($pipelines as &$pipeline) {
        if ($pipeline[0]['pipelineId'] == $ticket_details['properties']['hs_pipeline']['value']) {

          foreach ($pipeline[0]['stages'] as &$stage) {
            if ($stage['stageId'] == $ticket_details['properties']['hs_pipeline_stage']['value']) {
              if ($stage['metadata']['isClosed'] == 'true') {
                array_push($this->tickets_closed, $ticket);
              }
              else {
                array_push($this->tickets_open, $ticket);
              }
            }
          }
        }
      }
    }
    $this->tickets_open = array_reverse($this->tickets_open);
    $this->tickets_closed = array_reverse($this->tickets_closed);
  }

  function maxTickets() {
    $maxOpen = count($this->tickets_open) / (constant('TICKETS_PER_PAGE') ? constant('TICKETS_PER_PAGE') : '10');
    $maxClosed = count($this->tickets_closed) / (constant('TICKETS_PER_PAGE') ? constant('TICKETS_PER_PAGE') : '10');

    if ($_SESSION['view_by'] == 'open') { $max = $maxOpen; }
    elseif ($_SESSION['view_by'] == 'closed') { $max = $maxClosed; }

    if (!isset($max)) { $max = 1; }
    if ($max < 1) { $max = 1; }
    return ceil($max);
  }



  function email($to, $subject, $message, $alt) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = 0;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host       = constant('SMTP_HOST');                  // Specify main and backup SMTP servers
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = constant('SMTP_USER');                  // SMTP username
        $mail->Password   = constant('SMTP_PASS');                  // SMTP password
        $mail->SMTPSecure = 'tls';                                  // Enable TLS encryption, `ssl` also accepted
        $mail->Port       = 587;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom(constant('SMTP_USER'), 'Customer Portal');
        $mail->addAddress($to);     // Add a recipient

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;;
        $mail->AltBody = $alt;

        $mail->send();
    } catch (Exception $e) {
//        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
  }



  function emailUser($to, $subject, $message, $alt, $hs_id, $headers) {
    // Connect database
    $this->db->connect();

    // Prepare SQL statement for sanitization
    $stmt = $this->db->pdo->prepare('SELECT * FROM accounts WHERE hs_id = :hs_id');

    // Execute query
    $stmt->execute(['hs_id' => $hs_id]);

    // Store results
    $results = $stmt->fetch();

    if (isset($results['smtp_email']) && isset($results['smtp_server']) && isset($results['smtp_port']) && isset($results['smtp_ssl'])) {

      $mail = new PHPMailer(true);
      $encryption = ($results['smtp_ssl'] == true ? 'ssl' : 'tls');

      $key = hex2bin(constant('EMAIL_KEY'));
      $encoded = $results['smtp_password'];
      $decoded = base64_decode($encoded);
      $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
      $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
      $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

      try {
        //Server settings
        $mail->SMTPDebug = 0;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host       = $results['smtp_server'];                // Specify main and backup SMTP servers
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = $results['smtp_email'];                 // SMTP username
        $mail->Password   = $plaintext;                              // SMTP password
        $mail->SMTPSecure = $encryption;                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port       = $results['smtp_port'];                  // TCP port to connect to

        foreach ($headers as $header => $value) {
          $mail->addCustomHeader($header, trim($value));
        }

        $mail->addCustomHeader('X-Sender', $results['smtp_email']);
        $mail->addCustomHeader('Content-Transfer-Encoding', '7bit');
        $mail->addCustomHeader('User-Agent', 'Customer Portal/1.0');

        //Recipients
        $mail->setFrom($results['smtp_email']);
        $mail->addAddress($to);     // Add a recipient

        // Content
        $mail->isHTML(false);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;;
        $mail->AltBody = $alt;
        $mail->send();
      } catch (Exception $e) {
//        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
      }
    }
  }



  // Check if user is logged in
  function is_logged_in() {
      return $this->logged_in;
  }

  // Redirect to login page
  function go_login() {
    $_SESSION['redirect'] = "true";
    $_SESSION['redirect_url'] = $this->get_url();

    header("Location: " . $this->site_url . "/login");
    die();
  }

  // Get the current URL
  function get_url() { return $this->site_url . $_SERVER['REQUEST_URI']; }

  // Check if email config exists for a user
  function has_email_config($id) {
    // Connect database
    $this->db->connect();

    // Prepare SQL statement for sanitization
    $stmt = $this->db->pdo->prepare('SELECT * FROM accounts WHERE hs_id = :hs_id');

    // Execute query
    $stmt->execute(['hs_id' => $id]);

    // Store results
    $results = $stmt->fetch();
    if (isset($results['smtp_server']) && !empty($results['smtp_server']) && isset($results['smtp_password']) && !empty($results['smtp_password']) && isset($results['smtp_port']) && !empty($results['smtp_port']) && isset($results['smtp_ssl']) && !empty($results['smtp_ssl'])) { return true; }
    else { return false; }

  }

  function is_email_registered($email) {
    // Connect database
    $this->db->connect();

    // Prepare SQL statement for sanitization
    $stmt = $this->db->pdo->prepare('SELECT * FROM accounts WHERE email = :email');

    // Execute query
    $stmt->execute(['email' => $email]);

    // Store results
    $results = $stmt->fetch();

    if (empty($results)) { return false; }
    else { return true; }
  }

  function reset_password($email) {
    return false;
  }
  function check_password_token($token) {
    // Connect database
    $this->db->connect();

    // Prepare SQL statement for sanitization
    $stmt = $this->db->pdo->prepare('SELECT * FROM accounts WHERE reset_token = :token');

    // Execute query
    $stmt->execute(['token' => $token]);

    // Store results
    $results = $stmt->fetch();

    if (empty($results)) { return false; }
    else { return true; }
  }
}


// Function to insert item into array at specific position
function array_insert($array, $item, $position)
{
    $begin = array_slice($array, 0, $position);
    array_push($begin, $item);
    $end = array_slice($array, $position);
    $resultArray = array_merge($begin, $end);
    return $resultArray;
}

?>
