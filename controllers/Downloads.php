<?php
namespace Portal\Controller\Downloads;

class Controller
{
  private $model;

  public function __construct($model) {
    global $portal;

    $this->model = $model;

    // Get the information about the file being requested
    $software = $this->model->data['software'];
    $version = $this->model->data['version'];
    $platform = $this->model->data['platform'];

    // Generate filename extension
    switch ($platform) {
    case "windows":
        $extension = '.zip';
        break;
    case "mac":
        $extension = '.dmg';
        break;
    case "linux":
        $extension = '.tar.bz2';
        break;
    }

    // Create and store the filename
    $file = $software . '-' . $version . '-' . $platform . $extension;
    $filename = dirname(__FILE__) . '/../assets/files/' . $file;

    // Check if file exists
    if (file_exists($filename) && $portal->is_logged_in()) {
      $this->model->data['file_exists'] = "true";
      $this->model->data['filename'] = $filename;

      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="'.basename($filename).'"');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize($filename));
      readfile($filename);
      exit;
    }
    elseif (!file_exists($filename)) {

      // Set browser header
      header("HTTP/1.0 404 Not Found");

      // Determine template and referer
      $this->model->template = "404.html.twig";

      // Data to be passed to twig
      $this->model->data = array(
        'referer' => $portal->site_url . "/downloads",
        'site_url' => $portal->site_url
      );
    }
    elseif (!$portal->logged_in()) { $portal->go_login(); }
  }
}

?>
