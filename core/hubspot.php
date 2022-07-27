<?php
namespace Portal\Core;

// Class used to query hubspot API
class HubspotAPI {
  function __construct($key) {
    $this->key = $key;
  }

  // Send GET request
  function get($url, $params) {
    $get_url = 'https://api.hubapi.com' . $url . '?hapikey=' . $this->key . $params;

    //  Initiate curl
    $ch = curl_init();
    // Will return the response, if false it print the response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Set the url
    curl_setopt($ch, CURLOPT_URL,$get_url);
    // Execute
    $result=curl_exec($ch);
    // Closing
    curl_close($ch);

    // Return the results
    return json_decode($result, true);
  }

  // Send PUT request
  function put($url, $json_data) {
    $put_url = 'https://api.hubapi.com' . $url . '?hapikey=' . $this->key;

    //  Initiate curl
    $ch = curl_init();
    // Set headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($json_data)));
    // Set post fields
    curl_setopt($ch, CURLOPT_POSTFIELDS,$json_data);
    // Will return the response, if false it print the response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Tell it to use PUT
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    // Set the url
    curl_setopt($ch, CURLOPT_URL,$put_url);
    // Execute
    $result=curl_exec($ch);
    // Closing
    curl_close($ch);

    // Return the results
    return json_decode($result, true);
  }
}
