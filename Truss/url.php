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

  private $filter_qs_key = "/^[a-zA-Z0-9_\.-]+$/i";
  private $filter_qs_val = "/^[a-zA-Z0-9:_\.\/-]+$/i";
  private $filter_path = "/^[a-zA-Z0-9:_\.-]+$/i";

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



  protected function _assemble() {
  	$clean = '';
  	$clean .= $this->scheme;
  	$clean .= '://';
  	$clean .= $this->host;
  	$clean .= $this->_assemble_path();
  	if($this->query) {
  		$clean .= '?';
	  	$clean .= $this->_assemble_query();
  	}
  	$this->url = $clean;
  
  }

  protected function _assemble_path() {
  	$tmp_path = '';
  	if(sizeof($this->path_components) > 0) {
  		foreach($this->path_components as $pc) {
  			$tmp_path .= $pc . '/';
  		}
  		$tmp_path = rtrim($tmp_path, '/');
  		return '/'.$tmp_path;
  	}
  }
  protected function _assemble_query() {
  	if(sizeof($this->qs_components) == 0) {
  		return '';
  	}
  	$qs = '';
  	foreach($this->qs_components as $ck => $cv) {
  		$qs .= $ck;
  		if($cv) 
  			$qs .= '='.$cv;
  	}
  	return $qs;
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

  protected function _parse_path() {
  	if(strlen(trim($this->path, '/')) > 0) {
  		$tmp_arr = array();
  		$pc = array();
  		$pc = explode('/', trim($this->path, '/'));
  		foreach($pc as $c) {
			if(preg_match($this->filter_path, $c)) {
				$tmp_arr[] = $c;
			}
  		}
  		if(sizeof($tmp_arr) > 0)
	  		$this->path_components = $tmp_arr;
  	} 
  }

  protected function _parse_components() {
  	if($this->query) {
  		$query_components = array();
  		$qc = explode('&', $this->query);
  		foreach($qc as $qv) {
  			if(strstr($qv, '=') !== false) {
  				$qt = explode('=', $qv, 2);
	  			if(preg_match($this->filter_qs_key, $qt[0]) && preg_match($this->filter_qs_val, $qt[1])) {
	          		$query_components[$qt[0]] = $qt[1];
	        	}
  			} else {
				if(preg_match($this->filter_qs_key, $qv)) {
	  				$query_components[$qv] = '';
	  			}
  			}
  		}
  		$this->qs_components = $query_components;
  	}
  }

  public function __toString() {
  	return $this->url;
  }
  
}