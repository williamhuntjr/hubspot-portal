<?php
namespace Portal\Core;

class Router {

  private $routes = array();

  public function addRoute($pattern, $tokens = array(), $method) {
    $this->routes[] = array(
      "pattern" => $pattern,
      "tokens" => $tokens,
      "method" => $method
    );
  }

  // Parse URL to see if it matches any pre-defined routes inside of index.php
  public function parse($url, $method) {
    $tokens = array();

    foreach ($this->routes as $route) {
      preg_match("@^" . $route['pattern'] . "$@", $url, $matches);
      if ($matches && $method == $route['method']) {
        foreach ($matches as $key=>$match) {
          // Not interested in the complete match, just the tokens
          if ($key == 0) {
              continue;
          }
          // Save the token
          $tokens[$route['tokens'][$key-1]] = $match;
        }
        return $tokens;
      }
    }
  }
}
?>
