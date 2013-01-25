<?php

// set_error_handler('ex_handler');

function exception_handler(Exception $e) {
  try {
    if($e instanceof Http_Exception) {
      dump_http_exception($e);
      exit();
    }
    $traceline = "#%s %s(%s): %s(%s)";
    $msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

    $message = exception_text($e);
    $content = ob_get_contents();
    ob_end_clean();

    header('X-Debug: Error', true, 500);

    print '<h1>500 - Server Error</h1>';
    print '<p>' . $e->getMessage() . '</p>';

    if(is_dev()) {
      print '<p>' . $e->getFile() . ' ('.$e->getLine().')</p>';
      print '<pre>' . $e->getTraceAsString() . '</pre>';

      // foreach($e->getTrace() as $line) {
      //   print '<p>' . $line[0] . ':::::::::' . $line[1]. '</p>';
      // }
    }
    exit(1);
  } catch(Exception $e) {
    print 'Exception exception..' . $e->getMessage() . NL;
    var_dump($e);
    exit(1);
  }
}

function error_handler($code, $errstr, $file = __FILE__, $line = 0) {
// echo '<br />' . $code . ' ' . $errstr . ' ' . $file . '<Br />';
// echo error_reporting() . ' ' . $code . ' <br/>';
  if(error_reporting() & $code) {
    // throw new ErrorException($errstr, $code, 0, $file, $line);
    throw new Truss_Error(sprintf("%d %s %s(%d)", $code, $errstr, $file, $line));
  }
  return true;
}


function ex_handler_old($errno, $errstr, $errfile, $errline) {
  global $controller;
  $req = null;
  $ret = array('msg' => $errstr . " - " . $errfile . "(".$errline.")");
  
  if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $req = $_SERVER['HTTP_X_REQUESTED_WITH'];
  }
  
  if($controller == 'api' || $req == 'XMLHttpRequest') {
    $r = new JSONView($ret, 200);
  } else {
    $r = new View('404', $ret, $status=404);
  }
  echo $r->render();
  exit();
}

function exception_text(Exception $e) {
  return sprintf('%s [ %s ]: %s ~ %s:%d', get_class($e), $e->getCode(), strip_tags($e->getMessage()), $e->getFile(), $e->getLine());
}

function dump_http_exception(Http_Exception $e) {
  if(ob_get_level() > 0) {
    ob_end_clean();
  }
  if(is_dev()) {
    header('X-Debug: Error', true, $e->getCode());
  }
  header($e->status());
  echo $e->get_body();
}

function _log($msg) {
  trigger_error($msg, E_USER_NOTICE);
}

function ex_handler($errno, $errstr, $errfile, $errline) {
  global $controller;
  $req = null;
  $ret = array('msg' => $errstr . " - " . $errfile . "(".$errline.")");
  
  if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $req = $_SERVER['HTTP_X_REQUESTED_WITH'];
  }
  
  if($controller == 'api' || $req == 'XMLHttpRequest') {
    $r = new JSONView($ret, 200);
  } else {
    $r = new View('404', $ret, $status=404);
  }
  echo $r->render();
  exit();
}

// ob_start();
set_error_handler('error_handler');
set_exception_handler('exception_handler');
	