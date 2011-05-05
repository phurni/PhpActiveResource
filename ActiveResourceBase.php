<?php
/** \mainpage
 * Flexible implementation of the Ruby on Rails ActiveResource REST client.
 * Intended to work with RoR-based REST servers, which all share similar
 * API patterns.
 * 
 * The implementation is decoupled with many concerns:
 *   - ActiveResource itself
 *   - Transport
 *   - Serialization
 *   - Inflection
 *   
 * The provided serializations are XML and JSON.
 *
 * An AuthenticatedActiveResourceBase class is also provided that may be used in conjonction with authlogic_api
 * (see http://github/phurni/authlogic_api)
 *
 * @copyright Copyright 2010 Pascal Hurni
 * @license http://www.opensource.org/licenses/mit-license.php Licensed under the MIT License
 * @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 *
 * Usage:
 * @code
 *
 * ActiveResource.php
 * <?php
 * require_once('ActiveResourceBase.php');
 * require_once('CurlTransporter.php');
 * require_once('XmlSerializer.php');
 * require_once('JsonSerializer.php');
 * require_once('TypeMarshaller.php');
 *
 * // Setup a base class with all your chosen concerns
 * class ActiveResource extends ActiveResourceBase {
 *   static $marshaller;
 *
 *   function __construct ($data = array ()) {
 *     // These may be set by subclasses constructor, so check them before re-setting
 *     if (! $this->transporter) $this->transporter = new CurlTransporter();
 *     if (! $this->serializer) $this->serializer = new XmlSerializer(self::$marshaller, array('element_name' => $this->element_name)));
 *     
 *     parent::__construct($data);
 *   }
 * }
 * ?>
 *
 * Person.php
 * <?php
 * require_once ('ActiveResource.php');
 *
 * class Person extends ActiveResource {
 *     var $site = 'http://localhost:3000/';
 *     var $element_name = 'person';
 *     var $collection_name = 'people';
 * }
 * ?>
 *
 * UseCases.php
 * <?php
 * require_once ('Person.php');
 *
 * // create a new person
 * $person = new Person( array('firstname => 'John', 'lastname => 'Doe') );
 * if (!$person->save()) {
 *   echo $person->error_message;
 * }
 *
 * // for all other operations you must have a blank object as a handle.
 * $Person = new Person();
 *
 * // find people
 * $people = $Person->find('all');
 * if ($people === false) { // don't use "if (!$people)" because an empty array will match!
 *   echo $people->error_message;
 * }
 * else {
 *   foreach ($people as $person) {
 *     var_dump($person->to_object());
 *   }
 * }
 * 
 * // find people with some options (passed as query string parameters)
 * $people = $Person->find('all', 'lastname' => 'Doe');
 *
 * // find a known person
 * $person = $Person->find(123);
 * if ($person === false)
 *   echo $Person->error_message;   // read carefully $Person with a uppercase P
 * }
 * else {
 *   var_dump($person->to_object());
 * }
 *
 * // update an attribute
 * // have a Person object in $person (find() it) 
 * $person->phone_number = '555-98-76';
 * if (!$person->save()) {
 *   echo $person->error_message;
 * }
 *
 * // delete a person
 * // have a Person object in $person (find() it) 
 * if (!$person->destoy()) {
 *   echo $person->error_message;
 * }
 * 
 * // custom method
 * $object = $Person->get('banned', array('level' => 3));
 * if ($object === false) {
 *   echo $person->error_message;
 * }
 *
 * ?>
 *
 * @endcode
 */

/**
 ** You'll find here examples of concrete ActiveResource classes that may be used as a base class for your real model classes
 **
 ** @example ActiveResource_withInflection.php
 ** @example ActiveResource_withMarshaller.php
 */

/**
 ** ActiveResourceBase is the abstract base class for a REST resource.
 ** 
 ** To become a concrete class, subclasses have to set the $serializer and $transporter variables 
 ** with objects having interfaces (duck typing not PHP Interface) explained below.
 **
 ** They also have to set
 **  - $site to point to the base URL for the remote site
 **  - $element_name The singular underscored name of the resource
 **  - $collection_name The plural underscored name of the resource
 **
 *
 * @code
 * Serializer
 *   / **
 *    * Constructor
 *    *
 *    * @param object $model_object The related ActiveResource object.
 *    * @param assoc $options Future options may be passed here.
 *    *
 *    * /
 *   function __construct($model_object, $options = array()) 
 *     
 *   / **
 *    * Encodes an object to the target format (specified by the identity of the class)
 *    * Will return `false` if process failed. Check $error_code to know what went wrong.
 *    *
 *    * @param object|assoc $data The object to encode, will be iterated with foreach.
 *    * @param assoc $options Options map. Currently no options available.
 *    * @return string The object encoded in the right format
 *    *
 *    * /
 *   function encode($data, $options = array())
 *     
 *   / **
 *    * Decodes a text stream to any of three forms.
 *    * Will return `false` if process failed. Check $error_code to know what went wrong.
 *    * The first form is an array consisting of objects of the $model_object class.
 *    * The second form is a single object of the $model_object class.
 *    * The third form is an array of string each of them being an error. (The caller already knows the stream is an error stream)
 *    * 
 *    * It is also possible to get simple objects instead of $model_object class objects. Pass `raw` => true in the options.
 *    * 
 *    * @param string $text The stream to decode.
 *    * @param assoc $options Options map. Valid options: `raw`
 *    * @return array|object 
 *    *
 *    * /
 *   function decode($text, $options = array())
 *     
 *   / **
 *    * contains the extension used when generating URL. Must be a string.
 *    * /
 *   var $extension
 *     
 *   / **
 *    * contains the MIME type of the format this class handles. Must be a string.
 *    * /
 *   var $mime_type
 *
 *
 * Transporter
 *   / **
 *    * Sends a GET request using the passed URL
 *    * Will return FALSE if an error occured. Check $error_code to know what went wrong.
 *    *
 *    * @param string $url The complete URL to request.
 *    * @param assoc $options Options map. Currently no options.
 *    * @return string The response body.
 *    *
 *    * /
 *   function get($url, $options = array())
 *     
 *   / **
 *    * Sends a POST request.
 *    * You may set the `Content-Type` header by passing a string to options `format`.
 *    * Will return FALSE if an error occured. Check $error_code to know what went wrong.
 *    *
 *    * @param string $url The complete URL for the request.
 *    * @param string $data The data to pass as the request body.
 *    * @param assoc $options Options map. Valid options: `format`
 *    * @return string The response body.
 *    *
 *    * /
 *   function post($url, $data, $options = array())
 *     
 *   / **
 *    * Sends a PUT request.
 *    * You may set the `Content-Type` header by passing a string to options `format`.
 *    * Will return FALSE if an error occured. Check $error_code to know what went wrong.
 *    *
 *    * @param string $url The complete URL for the request.
 *    * @param string $data The data to pass as the request body.
 *    * @param assoc $options Options map. Valid options: `format`
 *    * @return string The response body.
 *    *
 *    * /
 *   function put($url, $data, $options = array())
 *     
 *   / **
 *    * Sends a DELETE request.
 *    * Will return FALSE if an error occured. Check $error_code to know what went wrong.
 *    *
 *    * @param string $url The complete URL for the request.
 *    * @param assoc $options Options map. Currently no options.
 *    * @return string The response body (has no meaning).
 *    *
 *    * /
 *   function delete($url, $options = array())
 *     
 * @endcode
 */
abstract class ActiveResourceBase {
  /// The serializer object (see above)
  var $serializer = null;
  
  /// The transporter object (see above)
  var $transporter = null;

  /// The REST site address, e.g., http://www.example.com/
  var $site = null;

  /// The element name used for encoding and url generation
  var $element_name = false;
  
  /// The collection name used for encoding and url generation
  var $collection_name = false;

  /// An error message if an error occurred.
  var $error_message = false;

  /// The error number if an error occurred.
  var $error_code = false;


  // The data of the current object, accessed via the anonymous get/set methods.
  protected $_data = array();

  /**
   ** @param $data assoc attributes map to initialize the instance with.
   */
  function __construct($data = array()) {
    $this->_data = $data;
  }

  /**
   ** Saves a new record or updates an existing one via:
   **
   ** @code
   **   POST /collection.format
   **   PUT  /collection/id.format
   ** @endcode
   */
  function save() {
    if (isset($this->_data['id'])) {
      return $this->_put(array('id' => $this->_data['id']), $this->_encode($this->_data));
    }
    return $this->_post(array(), $this->_encode($this->_data));
  }

  /**
   ** Deletes a record via:
   **
   ** DELETE /collection/id.xml
   */
  function destroy() {
    return $this->_delete(array('id' => $this->_data['id']));
  }

  /**
   ** Finds a record or a collection of records via:
   **
   ** @code
   **   GET /collection/id.xml
   **   GET /collection.xml
   ** @endcode
   **
   ** @param mixed $id
   ** @param array $options key/value pairs sent as query string parameters
   ** @return mixed|Object
   */
  function find($id, $options = array()) {
    if ($id == 'all') {
      return $this->_decode($this->_get($options));
    }
    return $this->_decode($this->_get(array_merge($options, array('id' => $id))));
  }

  /**
   ** Calls a specified custom method with the GET verb on the current object via:
   **
   ** @code
   **   GET /collection/id/method.xml
   **   GET /collection/id/method.xml?attr=value
   ** @endcode
   **
   ** @param string $method The custom method name
   ** @param assoc $options Key/value pairs for query string parameters
   ** @return Object A simple object (StdClass) representing the result body (passed to the serializer)
   */
  function get($method, $options = array()) {
    return $this->_decode_object($this->_get(array_merge($options, array('action' => $method, 'id' => isset($this->_data['id']) ? $this->_data['id'] : null))));
  }

  /**
   ** Calls a specified custom method with the POST verb on the current object via:
   **
   ** @code
   **   POST /collection/id/method.xml
   **   POST /collection/id/method.xml?attr=value
   ** @endcode
   **
   ** @param string $method The custom method name
   ** @param assoc $options Key/value pairs for query string parameters
   ** @param string $body The body to send for the request
   ** @return Object A simple object (StdClass) representing the result body (passed to the serializer)
   */
  function post ($method, $options = array(), $body = '') {
    return $this->_decode_object($this->_post(array_merge($options, array('action' => $method, 'id' => isset($this->_data['id']) ? $this->_data['id'] : null)), $this->_encode($body)));
  }

  /**
   ** Calls a specified custom method with the PUT verb on the current object via:
   **
   ** @code
   **   PUT /collection/id/method.xml
   **   PUT /collection/id/method.xml?attr=value
   ** @endcode
   **
   ** @param string $method The custom method name
   ** @param assoc $options Key/value pairs for query string parameters
   ** @param string $body The body to send for the request
   ** @return Object A simple object (StdClass) representing the result body (passed to the serializer)
   */
  function put($method, $options = array(), $body = '') {
    return $this->_decode_object($this->_put(array_merge($options, array('action' => $method, 'id' => isset($this->_data['id']) ? $this->_data['id'] : null)), $this->_encode($body)));
  }

  /**
   ** Calls a specified custom method with the DELETE verb on the current object via:
   **
   ** @code
   **   DELETE /collection/id/method.xml
   **   DELETE /collection/id/method.xml?attr=value
   ** @endcode
   **
   ** @param string $method The custom method name
   ** @param assoc $options Key/value pairs for query string parameters
   ** @return Object A simple object (StdClass) representing the result body (passed to the serializer)
   */
  function delete($method, $options = array()) {
    return $this->_decode_object($this->_delete(array_merge($options, array('action' => $method, 'id' => isset($this->_data['id']) ? $this->_data['id'] : null))));
  }

  /// Returns the data as a simple object
  function to_object() {
    return (object) $this->_data;
  }

  /// Generate URL based on object identity and options as query string parameters
  protected function _url_for($options) {
    if (isset($options['base_uri'])) {
      $url = $this->site . $options['base_uri'];
    }
    elseif (isset($options['base_url'])) {
      $url = $options['base_url'];
    }
    else {
      $url = $this->site . $this->collection_name;
      if (isset($options['id']) && $options['id']) {
        $url .= "/" . $options['id'];
      }
      if (isset($options['action']) && $options['action']) {
        $url .= "/" . $options['action'];
      }
    }
    
    if ($this->serializer->extension) {
      $url .= '.' . $this->serializer->extension;
    }

    $options = $this->_remove_path_keys($options);
    if ($options) {
      $url .= '?' . http_build_query($options);
    }
    
    return $url;
  }
  
  protected function _remove_path_keys($options) {
    if (array_key_exists('id', $options))        unset($options['id']);
    if (array_key_exists('action', $options))    unset($options['action']);
    if (array_key_exists('base_uri', $options))  unset($options['base_uri']);
    if (array_key_exists('base_url', $options))  unset($options['base_url']);
    return $options;
  }

  protected function _encode($data) {
  }

  protected function _decode($text, $options = array()) {
    // skip decoding if text is FALSE
    if ($text === false)
      return $text;
      
    $retval = $this->serializer->decode($text, $options);
    if ($this->serializer->error_code) {
      $this->error_code = $this->serializer->error_code;
      $this->error_message = "Serialization error " . $this->error_code;
    }
    // If we just decode an error message passed through HTTP, convert it to our error message.
    if ($this->error_code >= 400) {
      $this->error_message = implode("\r\n", $retval);
      return false;
    }
    return $retval;
  }

  protected function _decode_object($text) {
    return $this->_decode($text, array('raw' => true));
  }
  
  protected function _get($options) {
    $retval = $this->transporter->get($this->_url_for($options));
    $this->error_code = $this->transporter->error_code ? $this->transporter->error_code : ($this->transporter->http_code >= 400 ? $this->transporter->http_code : 0);
    return $retval;
  }

  protected function _post($options, $data) {
    $retval = $this->transporter->post($this->_url_for($options), $data);
    $this->error_code = $this->transporter->error_code ? $this->transporter->error_code : ($this->transporter->http_code >= 400 ? $this->transporter->http_code : 0);
    return $retval;
  }

  protected function _put($options, $data) {
    $retval = $this->transporter->put($this->_url_for($options), $data);
    $this->error_code = $this->transporter->error_code ? $this->transporter->error_code : ($this->transporter->http_code >= 400 ? $this->transporter->http_code : 0);
    return $retval;
  }

  protected function _delete($options) {
    $retval = $this->transporter->delete($this->_url_for($options));
    $this->error_code = $this->transporter->error_code ? $this->transporter->error_code : ($this->transporter->http_code >= 400 ? $this->transporter->http_code : 0);
    return $retval;
  }

  /**
   ** Attribute style getter
   */
  function __get($k) {
    if (array_key_exists($k, $this->_data)) {
      return $this->_data[$k];
    }
    return $this->{$k};
  }

  /**
   ** Attribute style setter
   */
  function __set($k, $v) {
    if (array_key_exists($k, $this->_data)) {
      $this->_data[$k] = $v;
      return;
    }
    $this->{$k} = $v;
  }

  /**
   ** Chainable setter
   */
  function set($k, $v = false) {
    if (!$v && is_array($k)) {
      foreach ($k as $key => $value) {
        $this->_data[$key] = $value;
      }
    }
    else {
      $this->_data[$k] = $v;
    }
    return $this;
  }

}

?>