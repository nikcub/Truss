<?php

/* Helper function to setup site to use the truss library for the first time.
 * 
 *
 */

function _install_check() {
  $allgood = true;
  
  echo '<h3>PHP Settings</h3>';
  foreach(array('display_errors', 'include_path', 'allow_url_fopen', 'allow_url_include', 'date.timezone', 'default_charset', 'default_mimetype', 'display_startup_errors', 'error_log', 'error_reporting', 'log_errors', 'memory_limit', 'open_basedir', 'register_globals', 'safe_mode') as $pv) {
    $conf_value = ini_get($pv);
    echo "<p>$pv: $conf_value</p>";
    
  }

  echo '<h3>Paths</h3>';
  foreach(array('PATH_LIBRARY', 'PATH_PUBLIC', 'PATH_TRUSS', 'PATH_BASE', 'PATH_APP', 'PATH_CONT', 'PATH_VIEW', 'PATH_VAR', 'PATH_LOGS', 'PATH_TMP', 'PATH_CACHE') as $pv) {
    $st = is_dir(constant($pv)) ? 'OK' : 'ERR';
    if(!$st) $allgood = false;
    echo "<p>$pv: " . constant($pv) . " (".$st.")</p>";
  }

  echo '<h3>Includes</h3>';
  $includes = explode(PS, ini_get('include_path'));
  foreach($includes as $ip) {
    $st = is_dir($ip) ? 'OK' : 'ERR';
    if(!$st) $allgood = false;
    echo "<p>" . $ip . " (".$st.")</p>";
  }

  $htaccess_path = PATH_PUBLIC . DIRECTORY_SEPARATOR . '.htaccess';
  $have_rewrite = is_file($htaccess_path);

  echo '<h3>Router</h3>';
  echo '<p><code>.htaccess</code> ... ';
  if($have_rewrite) {
    echo 'yes</p>';
  } else {
    echo 'no';
    echo '(writing..) </p>';
    try {
      echo '<p>writing .htaccess file .. ' . $rt . '</p>';
      $rt = truss_write_htaccess(PATH_PUBLIC);      
    } catch(Exception $e) {
      $allgood = false;
      echo '<p>ERROR: ' . $e->getMessage() . '</p>';
    }
  }

  if(!$have_rewrite) {

  } else {
    echo '<p>have a rewrite file..</p>';
  }

  echo "<p>" . ($allgood) ? "All good!" : "Errors" . "</p>";
}


$htaccess_default = <<<EOB
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !favicon.ico$
RewriteRule ^ index.php [QSA,L]
EOB;

function truss_write_htaccess($dir=false) {
  global $htaccess_default;
  $htaccess_fn = '.htaccess';
  $dir = $dir or PATH_PUBLIC;
  $htaccess_path = $dir . DIRECTORY_SEPARATOR . $htaccess_fn;
  
  if(!is_dir($dir)) {
    throw new Exception('Could not write .htaccess - no such directory ('.$dir.')');
  }
  if(!is_writable($dir)) {
    throw new Exception('Could not write .htaccess - permission denied. <br/>Run <code>chmod ' . $dir .'</code>');
  }
  $rt = file_put_contents($htaccess_path, $htaccess_default);
  return $rt;
}

if(isset($_GET['_installcheck'])) {
  _install_check();
  exit();
}

if(isset($_GET['_phpconf'])) {
  phpinfo();
  exit();
}