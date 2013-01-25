<?php

class Request {
  
  public $has_post = false;
  public $has_query = false;

  public $uri;
  public $path;
  public $xhr = false;
  
  protected $qs = array();
  protected $headers = array();

  // body data
  private $data = array();

  public $method;
  public $ip;
  public $proxied;
  public $proxy_ip;
  public $proxy_port;
  public $qs_raw;

  // const GET =     0b00000001;
  // const POST =    0b00000010;
  // const PUT =     0b00000100;
  // const HEAD =    0b00001000;
  // const DELETE =  0b00010000;
  // const OPTIONS = 0b00100000;
  
  static private $filterUri = "/^[a-z0-9:_\.\/-]+$/i";
  static private $filterPost = "/^[a-z0-9:_\.\/-]+$/i";

  function __construct() {
    // Set request variables
    $this->path =       $this->_parse_path();
    $this->uri = $this->_parse_uri();
    $this->headers =   $this->_parse_headers();
    $this->qs = $this->_parse_qs();
    $this->method = $this->_parse_method();
    $this->xhr = $this->_parse_json();
    
    list($this->data, $this->body) = $this->_parse_body();
    
    // user IP
    list($this->ip, $this->proxied, $this->proxy_ip, $this->proxy_port) = $this->parseClientIp();

    // content negotiation variables
    // @TODO content negotiation in Request for use with API etc.

    // shortcuts
    if(is_array($this->data) && sizeof($this->data) > 0)
      $this->has_post = true;

    if(is_array($this->qs) && sizeof($this->qs) > 0)
      $this->has_query = true;

    // reset all php vars to force using this obj for access
    $this->unset_defaults();

    return true;
  }

  public function header($header_name, $default=false) {
    // consistancy
    $header_name = strtolower($header_name);
    // replace hyphen with underscore in lookup name since we store as later
    $header_name = str_replace(chr(45), chr(95), $header_name);
            
    if(in_array($header_name, $this->headers)) {
      return $this->headers[$header_name];
    }
    
    return $default;
  }


  public function getPostData() {
    return (is_array($this->postData)) ? $this->postData : null;
  }

  public function getQueryData() {
    return (is_array($this->queryData)) ? $this->queryData : null;
  }

  public function getReqType() {
    return $this->reqType;
  }

  // @todo support multiple variables as array or as func_get_args()
  public function data($name, $default=null) {
    if(isset($this->data[$name])) {
      return $this->data[$name];
    } else {
      return false;
    }
  }

  public function getForUrl($name) {
    $var = $this->data($name);
    return rawurlencode($var);
  }

  public function getForDb($name) {
    $var = $this->data($name);
    return mysql_real_escape_string($var);
  }

  // * Get a variable formatted for HTML output
  public function getForHtml($name) {
    $var = $this->data($name);
    return htmlentities($var, ENT_QUOTES, 'UTF-8');
  }


  // parse client IP address
  private function parseClientIp() {
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
      return Array($_SERVER["HTTP_X_FORWARDED_FOR"], true, $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT']);
    }
    else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
      return Array($_SERVER["HTTP_CLIENT_IP"], true, $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT']);
    }
    else {
      return Array($_SERVER['REMOTE_ADDR'], false, null, null);
    }
  }


  // parses query string and returns array
  protected function _parse_qs() {
    $qs = array();
    if(isset($_GET) && sizeof($_GET) > 0) {
      foreach($_GET as $var => $val) {
        if(preg_match(self::$filterPost, $var) && preg_match(self::$filterPost, $val)) {
          $qs[$var] = $val;
        }
      }
    }
    return $qs;
  }
  
  protected function _parse_method() {
    $method = (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    
    // @TODO implement
    if($this->header('x-method')) {
      $method = $this->header('x-method');
    }
    
    // @TODO implement
    if($this->data('_method')) {
      $method = $this->data('_method');
    }
    
    return $method;
  }
  
  // parses post vars and/or post body
  protected function _parse_body() {
    $data_arr = array();
    $body = '';

    if(isset($_POST) && sizeof($_POST) > 0) {
      foreach($_POST as $var => $val) {
        if(preg_match(self::$filterPost, $var) && preg_match(self::$filterPost, $val)) {
            $data_arr[$var] = $val;
        }
      }
    }
        
    return array($data_arr, $body);
  }

  // parse and return all client http request headers
  // returns Array of headers
  protected function _parse_headers() {
    /*
     * Note: instead of using apache_request_headers() we use the server vars
     * as they are more reliable
     */
    foreach($_SERVER as $key => $val) {
      if(substr($key, 0, 5) == 'HTTP_') {
        $tmp[strtolower(substr($key, 5))] = $val;
      }
    }

    return $tmp;
  }


  protected function _parse_uri() {
    $schema = 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = $_SERVER['REQUEST_URI'];
    $qs = '';
    
    return sprintf("%s://%s%s%s", $schema, $host, $path, $qs);
  }
  
  // parses server info to get a clean request URI (sans query string etc.)
  // returns $uri
  protected function _parse_path() {
    if(isset($_SERVER['REQUEST_URI'])) {
      $uri = $_SERVER['REQUEST_URI'];
      if(strpos($uri, '?')) {
        list($uri) = explode('?', $uri, 2);
      }
      return strtolower($uri);
    }
    
    // @TODO work with no mod_rewrite
    // @TODO work with app in subdirectory
    if(isset($_SERVER['PATH_INFO'])) {
      $uri = trim($_SERVER['PATH_INFO'], chr(47));
    } else if(isset($_SERVER['REDIRECT_URL'])) {
      list($uri) = explode('?', trim($_SERVER['REDIRECT_URL'], chr(47)), 2);
    } else {
      list($uri) = explode('?', trim($_SERVER['REQUEST_URI'], chr(47)), 2);
    }

    if(strlen($uri) == 0 || preg_match(self::$filterUri, $uri)) {
        return strtolower($uri);
    } else {
        throw new Exception('Invalid Request');
    }
  }

  protected function _parse_json() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
  }

  // removes the pho super globals to force use of the request object
  // @TODO add other potentiall dangerous globals here
  protected function unset_defaults() {        
    $defaultVars = Array('_GET', '_POST', '_REQUEST');
    foreach($defaultVars as $var) {
      unset($var);
    }
  }

}