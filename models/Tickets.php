<?php
namespace Portal\Model\Tickets;

class Model
{
  public $template;
  public $data = array();

  public function __construct($route){
    global $portal;

    // Data to be passed to twig
    $this->data = array(
      'logged_in' => $portal->is_logged_in(),
      'method' => $_SERVER['REQUEST_METHOD'],
      'referer' => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
      'site_url' => $portal->site_url,
      'action' => ''
    );

    // Check if user is logged in before displaying tickets or ticket data
    if ($this->data['method'] == 'GET' && $portal->is_logged_in()) {

      if (!$portal->has_email_config($_SESSION['hs_id'])) { $this->data['has_email_config'] = false; }
      else { $this->data['has_email_config'] = true; }

      $_SESSION['last_url'] = $_SERVER['REQUEST_URI'];
      // Check action of page
      if (empty($route['action'])) {

        // Data to be passed to twig
        if (!isset($_SESSION['view_by'])) { $_SESSION['view_by'] = 'open'; }
        if (!isset($_SESSION['page_num'])) { $_SESSION['page_num'] = '1'; }

      }

      else {
        // Data to be passed to twig
        $this->data['action'] = $route['action'];

        if ($this->data['action'] == 'open' || $this->data['action'] == 'closed' && isset($route['page_num'])) {
          $_SESSION['page_num'] = $route['page_num'];
        }

        if ($this->data['action'] == 'open') { $_SESSION['view_by'] = 'open'; }
        if ($this->data['action'] == 'closed') { $_SESSION['view_by'] = 'closed'; }
        if ($this->data['action'] == 'all') { $_SESSION['view_by'] = 'all'; }
      }

      $this->data['view_by'] = $_SESSION['view_by'];
      $this->data['page_num'] = $_SESSION['page_num'];

      // Grab a list of all tickets and filter them
      $portal->loopTickets($_SESSION['hs_id'], '', array());
      $portal->filterTickets();

      // Store the count on how many pages are available
      $this->data['max_pages'] = $portal->maxTickets();

      // Store the page offset
      $this->data['offset'] = ($this->data['page_num'] - 1) * constant('TICKETS_PER_PAGE');

      // Determine where loop will stop at based on page offset for when looping through tickets to display
      $end = $this->data['offset'] + constant('TICKETS_PER_PAGE');

      // Store empty arrays for list of open and closed tickets
      $this->data['tickets_open'] = array();
      $this->data['tickets_closed'] = array();

      // Loop through all tickets for the user
      for ($x = $this->data['offset']; $x < $end; $x++) {

        // Add ticket to open tickets array if exists
        if (!empty($portal->tickets_open[$x])) {
         $ticket = $portal->getTicket($portal->tickets_open[$x]);
         $ticket['status'] = "open";

         array_push($this->data['tickets_open'], $ticket);
        }

        // Add ticket to closed tickets array if exists
        if (!empty($portal->tickets_closed[$x])) {
         $ticket = $portal->getTicket($portal->tickets_closed[$x]);
         $ticket['status'] = "closed";

         array_push($this->data['tickets_closed'], $ticket);

        }
      }

      $this->data['tickets_all'] = array();

      // Loop through all tickets for the user
      for ($x = $this->data['offset']; $x < $end; $x++) {

        // Add ticket to open tickets array if exists
        if (!empty($portal->tickets_open[$x])) {
          $ticket = $portal->getTicket($portal->tickets_open[$x]);
          $ticket['status'] = "open";

          array_push($this->data['tickets_all'], $ticket);
        }
        // Add ticket to closed tickets array if exists
        if (!empty($portal->tickets_closed[$x])) {
          $ticket = $portal->getTicket($portal->tickets_closed[$x]);
          $ticket['status'] = "closed";

          array_push($this->data['tickets_all'], $ticket);
        }
      }

      // Check if we are viewing ticket details
      if ($this->data['action'] == 'view') {

        // Store the id of the ticket we are viewing
        $this->data['id'] = $route['id'];

        // Loop through all tickets for user
        $portal->loopTickets($_SESSION['hs_id'], '', array());

        // By default, disallow user to view specified ticket id
        $this->data['cannot_view'] = 'true';

        // Check if user is allowed to view specified ticket id
        if (in_array($this->data['id'],$portal->tickets)) { $this->data['cannot_view'] = 'false'; }

        // Store information about the ticket for twig to parse
        $this->data['ticket_data'] = $portal->getTicket($this->data['id']);

        // Get the status of a ticket
        $status = $this->data['ticket_data']['properties']['hs_pipeline_stage']['value'];

        // Get pipelines
        $url = '/crm-pipelines/v1/pipelines/tickets';
        $params = '';
        
        $pipelines = $portal->api->get($url,$params);

        foreach ($pipelines as &$pipeline) {
          if ($pipeline[0]['pipelineId'] == $this->data['ticket_data']['properties']['hs_pipeline']['value']) {
            
            foreach ($pipeline[0]['stages'] as &$stage) {
              if ($stage['stageId'] == $this->data['ticket_data']['properties']['hs_pipeline_stage']['value']) {
                if ($stage['metadata']['isClosed'] == 'true') {
                  $this->data['ticket_data']['hs_status'] = "closed";
                }
                else {
                  $this->data['ticket_data']['hs_status'] = "open";
                }
              }
            }
          }
        }
        
        
        // Store the data of the ticket for twig to parse
        $this->data['date_timestamp'] = floor($this->data['ticket_data']['properties']['createdate']['value'] / 1000);

        $counter = 0;
        $this->data['last_references'] = '';

        // Store all emails attached to ticket for twig to parse
        $this->data['ticket_emails'] = $portal->getTicketEmails($this->data['id']);
        $max = count($this->data['ticket_emails']);

        // Store the references for the last response
        foreach ($this->data['ticket_emails'] as &$email) {
          $this->data['last_references'] = $this->data['last_references'] . ($counter != 0 ? ' ' : '') . (substr($email['metadata']['messageId'], 0, 1) != "<" ? '<' : '') . $email['metadata']['messageId'] . (substr($email['metadata']['messageId'], 0, 1) != "<" ? '>' : '');

          // Generate initials for avatar
          $name = $email['metadata']['from']['firstName'] . " " . $email['metadata']['from']['lastName'];
          $words = explode(" ", $name);
          $acronym = "";
          $letters = 0;

          foreach ($words as $w) {
            if ($letters <= 1) { $acronym .= $w[0]; }
            ++$letters;
          }
          $this->data['ticket_emails'][$counter]['initials'] = $acronym;

          ++$counter;

          // Store last email response for twig
          if ($email['engagement']['type'] == 'EMAIL') { $this->data['last_email'] = $email; }
        }
      }
    }
    elseif (!$portal->is_logged_in()) { $portal->go_login(); }
    // Set template file
    $this->template = "tickets.html.twig";
  }
}

?>
