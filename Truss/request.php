<?php

class Request {
  
  
  protected $qs = array();
  protected $headers = array();

  public $url;

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
  
  static private $filter_post = "/^[a-z0-9:_\.\/-]+$/i";

  function __construct($url=null) {
    if($url instanceof Url) {
      $this->url = $url;
    } else {
      $this->url = new Url($url);
    }

    $this->_parse();

    // content negotiation variables
    // @TODO content negotiation in Request for use with API etc.

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

  public function has_data() {

  }

  public function has_query() {
    return $this->url->has_query();
  }

  public function is_xhr() {
    return $this->_parse_json();
  }


  protected function _parse() {
    $this->_parse_body();
    $this->_parse_method();
    $this->_parse_ip();
    $this->_parse_headers();
  }

  // parse client IP address
  private function _parse_ip() {
    $vals = array();
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
      $vals = Array($_SERVER["HTTP_X_FORWARDED_FOR"], true, $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT']);
    }
    else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
      $vals = Array($_SERVER["HTTP_CLIENT_IP"], true, $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT']);
    }
    else {
      $vals = Array($_SERVER['REMOTE_ADDR'], false, null, null);
    }
    list($this->ip, $this->proxied, $this->proxy_ip, $this->proxy_port) = $vals;
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
    
    $this->method = $method;
  }
 

  // parses post vars and/or post body
  protected function _parse_body() {
    $data_arr = array();
    $body = '';

    if(isset($_POST) && sizeof($_POST) > 0) {
      foreach($_POST as $var => $val) {
        if(preg_match(self::$filter_post, $var) && preg_match(self::$filterPost, $val)) {
            $data_arr[$var] = $val;
        }
      }
    }
    
    $this->data = $data_arr;
    $this->body = $body;
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

    $this->headers = $tmp;
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