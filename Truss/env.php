<?php

// App ini config
// ini_set('include_path', get_include_path() . PS . PEAR_PATH);

$valid_env = array('DEV', 'STAGING', 'TESTING', 'PRODUCTION');

if(isset($_SERVER['TRUSS_ENV'])) {
  $env = strtoupper($_SERVER['TRUSS_ENV']);
  if(!in_array($env, $valid_env)) {
    exit("Not a valid environment type: " . $env);
  }
  define('ENV', $env);
} else {
  define('ENV', 'DEV');
}

if(!defined(ENV)) {
  define(ENV, 'PRODUCTION');
}

switch(ENV) {
  case 'DEV':
  case 'STAGING':
  case 'TESTING:':
    ini_set('display_startup_errors', true);
    ini_set('error_reporting', E_ALL);
    ini_set('error_log', PATH_LOGS . DS . 'error_log');
    ini_set('log_errors', true);
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', true);
    define('DEBUG_START_TIME', (isset($_SERVER['REQUEST_TIME'])) ? $_SERVER['REQUEST_TIME'] : microtime(true));
    define('DEBUG_START_MEMORY', memory_get_usage());
    define('OFFLINE_DEVELOPMENT', true);
    break;
  case 'PRODUCTION':
    ini_set('display_startup_errors', false);
    ini_set('error_reporting', E_ALL | E_STRICT);
    ini_set('error_log', PATH_LOGS . DS . 'error_log');
    ini_set('log_errors', true);
    ini_set('error_reporting', E_ALL | E_STRICT);
    ini_set('display_errors', false);
    define('OFFLINE_DEVELOPMENT', false);
    break;
  default:
    exit('env error');
    break;
}

function is_dev() {
  return ENV === 'DEV' or ENV == 'DEVELOPMENT';
}

function is_prod() {
  return ENV == 'PRODUCTION' or ENV == 'PROD';
}

function is_stage() {
  return ENV == 'STAGING';
}

class Dotenv {

    public static function load($path, $file = '.env') {
        $filePath = rtrim($path, '/') . '/' . $file;
        if(!file_exists($filePath)) {
            throw new \InvalidArgumentException("Dotenv: Environment file .env not found. Create file with your environment settings at " . $filePath);
        }

        // Read file and get all lines
        $fc = file_get_contents($filePath);
        $lines = explode(PHP_EOL, $fc);

        foreach($lines as $line) {
            // Only use non-empty lines that look like setters
            if(!empty($line) && strpos($line, '=') !== false) {
                // Strip quotes because putenv can't handle them
                $line = trim(str_replace(array('\'', '"'), '', $line));

                putenv($line);

                // Set PHP superglobals
                list($key, $val) = explode('=', $line);
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}
