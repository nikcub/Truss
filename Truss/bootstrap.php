<?php


// Global vars used throughout
define('TRUSS_VERSION', '0.1.2');
define('TRUSS_PHP_REQUIRED', '5.3.0');
define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
define('NL', "\n");
define('PEAR_ENABLED', false);

date_default_timezone_set('UTC');
ini_set('date.timezone', 'UTC');
setlocale(LC_ALL, 'en_US.utf-8');

define('PEAR_PATH', '/usr/lib/php');
// define('PEAR_PATH', '/usr/share/pear');

// Compat stuff
if(version_compare(PHP_VERSION, TRUSS_PHP_REQUIRED) == -1) {
	exit('Requires PHP v' . TRUSS_PHP_REQUIRED);
}

if (!isset($_SERVER['DOCUMENT_ROOT'])) {
  $_SERVER['DOCUMENT_ROOT'] = substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen($_SERVER['SCRIPT_NAME']));
}

// Globs (urgh)
defined('PATH_LIBRARY') or define('PATH_LIBRARY', realpath(dirname(__file__) . DS . '..' . DS));
defined('PATH_PUBLIC') or define('PATH_PUBLIC', (is_dir($_SERVER['DOCUMENT_ROOT'])) ? str_replace('/', DS, $_SERVER['DOCUMENT_ROOT']) : null);
define('PATH_TRUSS', dirname(__FILE__));
define('PATH_BASE', realpath(PATH_PUBLIC . DS . '..' . DS));
defined('PATH_APP') or define('PATH_APP', realpath(PATH_BASE . DS . 'app'));
define('PATH_CONT', realpath(PATH_APP . DS . 'controllers'));
define('PATH_VIEW', realpath(PATH_APP . DS . 'views'));
define('PATH_VAR', realpath(PATH_BASE . DS . 'var'));
define('PATH_LOGS', realpath(PATH_VAR . DS . 'logs'));
define('PATH_TMP', realpath(PATH_VAR . DS . 'tmp'));
define('PATH_CACHE', realpath(PATH_VAR . DS . 'cache'));

ini_set('include_path', PATH_TRUSS . PS . ini_get('include_path'));

ob_start();

function d($str) {
  echo '<p>' . $str .'</p>';
}

// libs
require_once 'install.php';
require_once 'env.php';
require_once 'http_exceptions.php';
require_once 'error_handler.php';

require_once 'url.php';
require_once 'request.php';
require_once 'router.php';
require_once 'reqhandler.php';

// require_once 'hash.php';
// require_once 'config.php';
// require_once 'toro.php';   // old router
// require_once 'Inspekt/Inspekt.php';
// require_once 'valid_email.php';
// require_once 'password_hash.php';
// require_once 'view.php';
// require_once 'exceptions.php';
// require_once 'request.php';
// require_once 'sessions.php';
// require_once 'router.php';

// Filter
if(class_exists('Inspekt')) {
  $sc = Inspekt::makeSuperCage();
}


return 1;

// ################### OLD #########################

if(strpos($_SERVER['REQUEST_URI'], '?')) {
  $clean_url = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));
} else {
   $clean_url = $_SERVER['REQUEST_URI']; 
}

if(substr($clean_url, 0, 1) == '/')
  $clean_url = substr($clean_url, 1);
  
if($clean_url == '') {
  $parts = array();
} else {
  $parts = explode('/', $clean_url);
}

if(sizeof($parts) < 1 || empty($parts)) {
  $controller = 'index';
} else {
  $clean = urldecode($parts[0]);
  $controller = preg_replace('/[^a-zA-Z0-9\-\_]/', '', $clean);
}

if(sizeof($parts) > 1) {
  $action = urldecode($parts[1]);
  $action = preg_replace('/[^a-zA-Z0-9\-\_]/', '', $action);
} else {
  $action = "home";
}

if(sizeof($parts) > 2) {
  $params = array_slice($parts, 2);
} else {
  $params = array();
}

// print "<!--";
// print $clean_url;
// print_r($parts);
// print "controller:" . $controller . "\n";
// print "action:" . $action . "\n";
// print_r($params);
// print_r($_GET);
// print "-->";

try {
  $dbh = new PDO(DBCON, DBUSER, DBPASS);
} catch(PDOException $e) {
  $r = new View('404', array('msg' => 'Database error'));
}

if(!file_exists(PATH_CONT . DS . $controller . '.php')) {
  $r = new View('404', array('msg' => 'file not found (c)'));
} else {

  $r = include(PATH_CONT . DS . $controller . '.php');

  if(($r === 1) && function_exists($action)) {
    try {
      $r = call_user_func($action, $params);
    } catch(Exception $e) {
      $r = new View('404', array('msg' => $e->getMessage()));
    }
  } elseif(!function_exists($action)) {
    $r = new View('404', array('msg' => 'file not found'));
  }
}

if($r) print $r->render();
