<?php

require_once ('ActiveResourceBase.php');

/**
 * Extended version of the ActiveResource REST client to automatically sign the request.
 * The RoR backend server must use authlogic_api to authenticate the requests.
 *
 * Usage:
 * @code
 *
 * <?php
 *
 * require_once ('AuthenticatedActiveResource.php');
 *
 * class Song extends ActiveResource {
 *     var $site = 'http://localhost:3000/';
 *     var $element_name = 'songs';
 *     var $app_key = 'demo';
 *     var $app_secret = 'secret';
 * }
 *
 * @endcode
 *
 * @copyright Copyright 2010 Pascal Hurni
 * @license http://www.opensource.org/licenses/mit-license.php Licensed under the MIT License
 * @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 */
class AuthenticatedActiveResourceBase extends ActiveResourceBase {
	/// The application key used to identify all requests
	var $app_key = false;

	/// The application secret which is used to sign all requests
	var $app_secret = false;

  /// The parameter name for the application key
  var $app_key_param = 'api_key';
  
  /// The parameter name for the signature
  var $signature_param = 'signature';
  
  /// The parameter name for the request id
  var $request_id_param = 'reqid';
  
	protected function _get ($options) {
    return parent::_get($this->_sign_get($options));
  }

	protected function _post ($options, $data) {
    return parent::_post($this->_sign_post($options, $data));
  }

	protected function _put ($options, $data) {
    return parent::_put($this->_sign_post($options, $data));
  }

	protected function _delete ($options) {
    return parent::_delete($this->_sign_get($options));
  }

  protected function _sign_get($options) {
    $reqid = microtime(true);
    $sig = $this->_collect_authentic_keys($options, $reqid) . $this->app_secret;
    $options[$this->app_key_param] = $this->app_key;
    $options[$this->request_id_param] = $reqid;
    $options[$this->signature_param] = md5($sig);
    return $options;
  }

  protected function _sign_post($options, $data) {
    $reqid = microtime(true);
    $sig = $this->_collect_authentic_keys($options, $reqid) . $data . $this->app_secret;
    $options[$this->app_key_param] = $this->app_key;
    $options[$this->request_id_param] = $reqid;
    $options[$this->signature_param] = md5($sig);
    return $options;
  }
  
  protected function _collect_authentic_keys($options, $reqid) {
    $args = $this->_remove_path_keys($options);
    $args[$this->app_key_param] = $this->app_key;
    $args[$this->request_id_param] = $reqid;
    ksort($args);
    $request_str = '';
    foreach ($args as $key => $value) {
      $request_str .= $key . $value;
    }
    return $request_str;
  }

}

?>