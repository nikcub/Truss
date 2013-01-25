<?php

$http_status_codes = array(
    100 => 'Continue',
    201 => 'Created',
    200 => 'OK',
    202 => 'Accepted',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    410 => 'Gone',
    500 => 'Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable'
  );

abstract class Http_Exception extends Exception {
  protected $code = 0;
  protected $description = '';
  protected $extra = array();

  public function __construct($description='') {
    if($description) {
      $this->description = $description;
    }
  }

  public function name() {
    global $http_status_codes;
    return $http_status_codes[$this->code];
  }
  
  public function description() {
    if(is_array($this->description) && sizeof($this->description) > 0)
      return implode('<br>', $this->description);
    if(is_string($this->description))
      return $this->description;
    return '';
  }
  
  public function extra() {
    if(is_array($this->extra) && sizeof($this->extra) > 0) {
      return implode('<br>', $this->extra);
    }
    return '';
  }

  public function status() {
    return "HTTP/1.1 {$this->code} {$this->name()}";
  }

  public function get_body() {
    return "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n
            <html><head>
              <title>{$this->code} {$this->name()}</title>\n
            </head>
            <body>\n
              <h1>{$this->code} - {$this->name()}</h1>\n
              <p>{$this->description()}</p>\n
              <p>{$this->extra()}</p>\n
            </body>
            </html>
            ";
  }
  
}

class Unauthorized extends Http_Exception {
  protected $code = 401;
  protected $description = array(
    '<p>The server could not verify that you are authorized to access ',
    'the URL requested.  You either supplied the wrong credentials (e.g. ',
    'a bad password), or your browser doesn\'t understand how to supply ',
    'the credentials required.</p><p>In case you are allowed to request ',
    'the document, please check your user-id and password and try ',
    'again.</p>'
  );
  
}

class Forbidden extends Http_Exception {
  protected $code = 403;
  protected $description = array(
    '<p>You don\'t have the permission to access the requested resource. ',
    'It is either read-protected or not readable by the server.</p>'
  );
  
}


class NotFound extends Http_Exception {
  protected $code = 404;
  protected $description = array(
    'The requested URL was not found on the server', 
    'If you entered the URL manually please check the spelling and try again.'
  );
  
}

class ServerError extends HTTP_Exception {
  protected $code = 500;
  protected $description = array(
    '<p>The server encountered an internal error and was unable to ',
    'complete your request.  Either the server is overloaded or there ',
    'is an error in the application.</p>'
    );
}

class NotImplemented extends HTTP_Exception {
  protected $code = 501;
  protected $description = array(
      '<p>The server does not support the action requested by the ',
      'browser.</p>'
    );
}

class Truss_Error extends ServerError {

  public function __construct($extra=null) {
    if($extra && is_string($extra)) {
      $this->extra[] = $extra;
    }
    if($extra && is_array($extra) && sizeof($extra) > 0) {
      $this->extra = $extra;
    }
  }
}
