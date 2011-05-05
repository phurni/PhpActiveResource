<?php
/**
 ** Example for ActiveResource PHP Client with request authentication through signing
 ** see http://github/phurni/authlogic_api for server side handling.
 **
 ** @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 **
 */

require_once('AuthenticatedActiveResourceBase.php');
require_once('CurlTransporter.php');
require_once('JsonSerializer.php');

class AuthenticatedActiveResource extends AuthenticatedActiveResourceBase {

  var $site = 'http://localhost:3000/';
  var $app_key = 'demo';
  var $app_secret = 'secret';

	/*
	 * Constructor
	 */
	function __construct ($data = array ()) {
    // These may be set by subclasses constructor, so check them before re-setting
    if (!$this->transporter) $this->transporter = new CurlTransporter();
    if (!$this->serializer) $this->serializer = new JsonSerializer($this);
    
    parent::__construct($data);
	}
	
	function create_object($data) {
	  $class_name = get_class($this);
    $obj = new $class_name;
    foreach ($data as $key => $value) {
      $obj->set($key, $value);
    }
    return $obj;
	}
}

?>