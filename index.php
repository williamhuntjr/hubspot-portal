<?php
session_start();

// Load website configuration
require_once('config/config.php');

// Load core
require_once('core/portal.php');

// Initiate core XTC object
$portal = new Portal\Core();

// Auth routes
$portal->router->addRoute("/(login)", array("controller"), "GET");
$portal->router->addRoute("/(login)", array("controller"), "POST");

$portal->router->addRoute("/(logout)", array("controller"), "GET");

$portal->router->addRoute("/(register)", array("controller"), "GET");
$portal->router->addRoute("/(register)", array("controller"), "POST");

$portal->router->addRoute("/(activate)/([0-9, a-z, A-Z]{1,32})", array("controller", "token"), "GET");

$portal->router->addRoute("/(tickets)", array("controller"), "GET");
$portal->router->addRoute("/(tickets)/(open)/([0-9]{1,10})", array("controller", "action", "page_num"), "GET");
$portal->router->addRoute("/(tickets)/(closed)/([0-9]{1,10})", array("controller", "action", "page_num"), "GET");
$portal->router->addRoute("/(tickets)/(all)/([0-9]{1,10})", array("controller", "action", "page_num"), "GET");
$portal->router->addRoute("/(tickets)/(view)/([0-9]{1,12})", array("controller", "action", "id"), "GET");

$portal->router->addRoute("/(downloads)", array("page"), "GET");
$portal->router->addRoute("/(downloads)/(client)/([0-9\.]{1,6})/(windows|linux|mac)", array("controller", "software", "version", "platform"), "GET");

$portal->router->addRoute("/(settings)", array("controller"), "GET");
$portal->router->addRoute("/(settings)", array("controller"), "POST");

$portal->router->addRoute("/(reset-password)", array("controller"), "GET");
$portal->router->addRoute("/(reset-password)", array("controller"), "POST");
$portal->router->addRoute("/(reset-password)/(confirm)/([0-9, a-z, A-Z]{1,32})", array("controller", "action", "token"), "GET");

$portal->router->addroute("/(send-response)", array("controller"), "POST");

// Page route
$portal->router->addRoute("/(.*)", array("page"), "GET");

$route = $portal->router->parse(($portal->site_folder !== '' ? substr($_SERVER['REQUEST_URI'],(strlen($portal->site_folder) + 1)) : $_SERVER['REQUEST_URI']), $_SERVER['REQUEST_METHOD']);

reset($route);

// Set current page
$portal->current_page = (isset($route['controller']) ? $route['controller'] : ($route['page'] == '' ? 'home' : $route['page']));

if (key($route) == 'page') {
  $portal->header($route);
  $portal->navigation($route);
  $portal->page($route);
  $portal->footer($route);
}
elseif (key($route) == 'controller') {
  $portal->header($route);
  $portal->navigation($route);
  $portal->controller($route);
  $portal->footer($route);
}


?>
