<?php

class UrlError extends Exception {};

class Url {
  
  protected $url_raw;

  protected $url;
  protected $scheme;
  protected $user;
  protected $pass;
  protected $host;
  protected $port;
  protected $path;
  protected $query;
  protected $fragment;

  protected $qs_components;
  protected $path_components;



  public function __construct($url=null) {
    if(!$url) {
    	$url = $this->request_url();
    } else {
    	if (!filter_var($url, FILTER_VALIDATE_URL, array(FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED))) {
            throw new UrlError(sprintf('Url: invalid (%s)', $url));
	    }
    }
    $this->_parse(urldecode($url));
  }
  
  protected function request_url() {
  	$tmp_url = '';
  	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') {
  		$this->scheme = 'https';
  		$tmp_url = 'https://';
  	} else {
  		$this->scheme = 'http';
  		$tmp_url = 'http://';
  	}
  	if(!isset($_SERVER["SERVER_NAME"])) {
  		throw new UrlError('No server name');
  	}
  	$this->host = $_SERVER["SERVER_NAME"];
  	$tmp_url .= $_SERVER["SERVER_NAME"];
  	if(isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") {
  		$this->port = $_SERVER["SERVER_PORT"];
  		$tmp_url .= ":%s" % $_SERVER["SERVER_PORT"];
  	}
  	if(isset($_SERVER["REQUEST_URI"])) {
  		$this->path = $_SERVER["REQUEST_URI"];
  		$tmp_url .= $_SERVER["REQUEST_URI"];
  	}
  	return $tmp_url;
  }

  protected function _parse($url) {
  	$this->url_raw = $url;

  	$comps = parse_url($url);
  	if($comps === false) {
  		throw new UrlError('Invalid URL('.$url.')');
  	}

  	foreach($comps as $c => $v) {
  		if(property_exists($this, $c)) {
  			$this->$c = $v;
  		}
  	}
  	$this->_parse_components();
  	$this->_parse_path();
  	$this->_assemble();
  }

  protected function _assemble() {
  	$clean = '';
  	$clean .= $this->scheme;
  	$clean .= '://';
  	$clean .= $this->host;
  	if(substr($this->path, 0, 1) !== '/')
	  	$clean .= '/';
  	$clean .= $this->path;
  	if($this->query) {
  		$clean .= '?';
	  	$clean .= $this->query;
  	}
  	$this->url = $clean;
  
  }

  protected function _parse_path() {
  	if(strlen(trim($this->path, '/')) > 0) {
  		$pc = array();
  		$pc = explode('/', trim($this->path, '/'));
  		$this->path_components = $pc;
  	} 
  }

  protected function _parse_components() {
  	if($this->query) {
  		$qc = array();
  		$qc = explode('&', $this->query);
  		foreach($qc as $qv) {
  			if(strstr($qv, '=') !== false) {
  				$qt = explode('=', $qv, 2);
  				$query_components[$qt[0]] = $qt[1];
  			} else {
  				$query_components[$qv] = '';
  			}
  		}
  		$this->qs_components = $query_components;
  	}
  }

  public function __toString() {
  	return $this->url;
  }
  
}