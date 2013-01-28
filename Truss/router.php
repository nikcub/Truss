<?php

class Router_Exception extends NotFound {};

class Router {
  
  protected $routes = array();
  protected $request;
  public $debug = false;
  
  public function __construct($routes=false, $request=false, $debug=false) {
    if(is_array($routes)) {
      $this->add_routes($routes);
    }
    
    if($request instanceof Request) {
      $this->attach_request($request);
    } else {
      $this->attach_request(new Request());
    }

    $this->debug = $debug;
  }
  
  public function add_routes($routes) {
    foreach($routes as $pattern => $handler) {
      $this->add_route($pattern, $handler);
    }
  }
  
  protected function attach_request($request) {
    if($request instanceof Request) {
      $this->request = $request;
      return true;
    }
    throw new Router_Exception('Need request');
  }
  
  public function compile_route($pattern) {
    $pattern = str_replace('<int:>', '([0-9]+)', $pattern);
    $pattern = str_replace('<int>', '([0-9]+)', $pattern);
    $pattern = str_replace('<integer>', '([0-9]+)', $pattern);
    $pattern = str_replace('<integer:>', '([0-9]+)', $pattern);
    $pattern = str_replace('<str:>', '([a-zA-Z0-9-_]+)', $pattern);
    $pattern = str_replace('<str>', '([a-zA-Z0-9-_]+)', $pattern);
    $pattern = str_replace('<string>', '([a-zA-Z0-9-_]+)', $pattern);
    $pattern = str_replace('<string:>', '([a-zA-Z0-9-_]+)', $pattern);
    $pattern = preg_replace("/<([\w-_]+)>/", '(?P<$1>[a-zA-Z0-9-_]+)', $pattern);
    $pattern = preg_replace("/<(?:int|integer)(?:\:)([\w-_]+)>/", '(?P<$1>[0-9]+)', $pattern);
    $pattern = preg_replace("/<(?:str|string|:|)(?:\:)([\w-_]+)>/", '(?P<$1>[a-zA-Z0-9-_]+)', $pattern);
    return $pattern;   
  }

  private function flatten_matches($match_array) {
    $ret_array = array();
    $last_pos = -1;
    unset($match_array[0]);
    foreach($match_array as $k => $v) {
      if($last_pos != $v[1]) {
        $ret_array[$k] = $v[0];
        $last_pos = $v[1];
      }
    }
    return $ret_array;
  }
  
  public function add_route($pattern, $handler, $methods=false) {
    $compiled_route = $this->compile_route($pattern);
    $this->routes[$compiled_route] = $handler;
  }
  
  protected function match_route() {
    $_path = $this->request->url->path or '/';
    $matches = array();
    
    if(in_array($_path, $this->routes)) {
      return array($this->routes[$_path], array());
    }
    foreach($this->routes as $pattern => $handler) {
      if (preg_match('#^/?' . $pattern . '/?$#', $_path, $matches, PREG_OFFSET_CAPTURE)) {
        $matches = $this->flatten_matches($matches);
        return array($handler, $matches);
      }
    }
  }
  
  public function serve($request=null) {
    
    if($request) $this->attach_request($request);
    
    if(!$this->request || !$this->routes)
      throw new Router_Exception('Need routes and request');

    list($handler, $match_params) = $this->match_route();
    
    if (is_callable($handler)) {
      $ret = call_user_func_array($handler, $match_params);
    } else if(is_string($handler) && class_exists($handler) && array_key_exists('action', $match_params)) {
      $handler_instance = new $handler();
      $handler_method = $match_params['action'];
      if(method_exists($handler_instance, $handler_method)) {
        unset($match_params['action']);
        $ret = call_user_func_array(array($handler_instance, $handler_method), $match_params);
      } else {
        print 'no method to match action';
        $ret = 1;
      }
    } else if(is_string($handler) && class_exists($handler)) {
      $handler_instance = new $handler();
      if(method_exists($handler_instance, $this->request->method)) {
        $ret = call_user_func_array(array($handler_instance, $this->request->method), $match_params);
      } else {
        print 'no method'.PHP_EOL;
        $ret = 1;
      }
    } else {
      // $ret = new View('404');
      throw new NotFound();
    }
    
    return $ret;
    // if ($handler_name && class_exists($discovered_handler)) {
    //   $handler_instance = new $discovered_handler();
    // 
    //   if (method_exists($discovered_handler, $request_method . '_xhr')) {
    //     header('Content-type: application/json');
    //     header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    //     header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    //     header('Cache-Control: no-store, no-cache, must-revalidate');
    //     header('Cache-Control: post-check=0, pre-check=0', false);
    //     header('Pragma: no-cache');
    //     $request_method .= '_xhr';
    //   }
    // 
    //   if (method_exists($handler_instance, $request_method)) {
    //     WW_Hook::fire('before_handler');
    //     $ret = call_user_func_array(array($handler_instance, $request_method), $regex_matches);
    //     WW_Hook::fire('after_handler');
    //   } else {
    //     WW_Hook::fire('404');
    //   }
    //   
    //   print $ret->render();
    // } else {
    //   WW_Hook::fire('404');
    // }

  }

}

