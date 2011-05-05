<?php
/**
 ** Handles HTTP transport with cURL
 **
 ** @copyright Copyright 2010 Pascal Hurni
 ** @license http://www.opensource.org/licenses/mit-license.php Licensed under the MIT License
 ** @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 **
 */

class CurlTransporter {

  /// HTTP Basic Authentication username
  var $username = null;

  /// HTTP Basic Authentication password
  var $password = null;

  /// An error message if an error occurred.
  /// Contains the message returned by curl_error()
  var $error_message = false;

  /// The error number if an error occurred.
  ///   -1 is cURL is missing otherwise the code returned by curl_errno()
  var $error_code = false;
  
  /// The response code returned from the server.
  var $http_code = false;

  /// The raw response headers sent from the server.
  var $response_headers = '';

  /// The response body sent from the server.
  var $response_body = '';


  // the cURL instance
  protected $curl = null;


  function __construct () {
  }
  
  /**
   ** Issue a GET request
   **  
   ** @param string $url The target URL.
   ** @param assoc $options Options map. Currently no options.
   ** @return string the response body
   **/
  function get($url, $options = array()) {
    if (! $this->_setup($url, $options))
      return false;
      
    return $this->_execute();
  }
  
  /**
   ** Issue a POST request
   **  
   ** @param string $url The target URL.
   ** @param string $data The body data.
   ** @param assoc $options Options map. You may pass a specific content-type with 'format' => 'your_mime_type'
   ** @return string the response body
   **/
  function post($url, $data, $options = array()) {
    if (! $this->_setup($url, $options))
      return false;

    curl_setopt ($this->curl, CURLOPT_POST, 1);
    curl_setopt ($this->curl, CURLOPT_POSTFIELDS, $data);
    if ($options['format'])
      curl_setopt ($this->curl, CURLOPT_HTTPHEADER, array ("Content-Type: " . $options['format']));

    return $this->_execute();
  }
  
  /**
   ** Issue a PUT request
   **  
   ** @param string $url The target URL.
   ** @param string $data The body data.
   ** @param assoc $options Options map. You may pass a specific content-type with 'format' => 'your_mime_type'
   ** @return string the response body
   **/
  function put($url, $data, $options = array()) {
    if (! $this->_setup($url, $options))
      return false;
    
    curl_setopt ($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt ($this->curl, CURLOPT_POSTFIELDS, $data);
    if ($options['format'])
      curl_setopt ($this->curl, CURLOPT_HTTPHEADER, array ("Content-Type: " . $options['format']));
    
    return $this->_execute();
  }
  
  /**
   ** Issue a DELETE request
   **  
   ** @param string $url The target URL.
   ** @param assoc $options Options map. Currently no options.
   ** @return string the response body (has no meaning)
   **/
  function delete($url, $options = array()) {
    if (! $this->_setup($url, $options))
      return false;

    curl_setopt ($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

    return $this->_execute();
  }
  
  protected function _setup($url, $options) {
    if (!extension_loaded ('curl')) {
      $this->error_message = 'cURL extension missing.';
      $this->error_code = -1;
      return false;
    }

    $this->curl = curl_init ();
    curl_setopt ($this->curl, CURLOPT_URL, $url);
    curl_setopt ($this->curl, CURLOPT_MAXREDIRS, 3);
    curl_setopt ($this->curl, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt ($this->curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($this->curl, CURLOPT_VERBOSE, 0);
    curl_setopt ($this->curl, CURLOPT_HEADER, 1);
    curl_setopt ($this->curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt ($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt ($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
    if (isset($options['headers']))
      curl_setopt ($this->curl, CURLOPT_HTTPHEADER, $options['headers']);
    if (isset($options['forward_cookies']))
      curl_setopt ($this->curl, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);

    /* HTTP Basic Authentication */
    if ($this->username && $this->password) {
      curl_setopt ($this->curl, CURLOPT_USERPWD, $this->username . ":" . $this->password);
    }

    return true;
  }
  
  protected function _execute() {
    $this->error_code = 0;
    $res = curl_exec ($this->curl);
    $this->http_code = curl_getinfo ($this->curl, CURLINFO_HTTP_CODE);

    if (! $res) {
      $this->error_code = curl_errno ($this->curl);
      $this->error_message = curl_error ($this->curl);
      curl_close ($this->curl);
      return false;
    }
    curl_close ($this->curl);
    
    list ($headers, $res) = explode ("\r\n\r\n", $res, 2);
    $this->response_headers = $headers;
    $this->response_body = $res;
    
    return $res;
  }

  function set_auth($username, $password) {
    $this->username = $username;
    $this->password = $password;
  }

}

?>