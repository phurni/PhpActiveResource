<?php
/**
 ** Handles XML serialization and de-serialization
 **
 ** @copyright Copyright 2010 Pascal Hurni
 ** @license http://www.opensource.org/licenses/mit-license.php Licensed under the MIT License
 ** @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 **
 */

class JsonSerializer {

  /// contains the extension used when generating URL. Must be a string.
  var $extension = 'json';
  
  /// contains the MIME type of the format this class handles. Must be a string.
  var $mime_type = 'application/json';
  
  /// An error code in case decode/encode fails.
  var $error_code = 0;

  protected $marshaller = null;

	/**
	 ** @marshaller object An object that responds to create_object(assoc)
	 ** @options assoc Options. Currently none.
	 */
	function __construct ($marshaller, $options = array()) {
    $this->marshaller = $marshaller;
	}
  
  /**
   ** Encodes an object to JSON
   ** Will return `false` if the process fails. Check $error_code to know what went wrong.
   **
   ** @param object|assoc $data The object to encode, will be iterated with foreach.
   ** @param assoc $options Options map. Currently no options available.
   ** @return string The object encoded in JSON
	 */
	function encode ($data, $options = array()) {
    $this->error_code = 0;
    
    $retval = json_encode($data);
    if ($retval === false)
      $this->error_code = 54;
    return $retval;
	}

  /**
   ** Decodes a JSON text to any of three forms.
   ** Will return `false` if the process fails. Check $error_code to know what went wrong.
   ** The first form is an array consisting of objects.
   ** The second form is a single object.
   ** The third form is an array of string each of them being an error. (The caller already knows the stream is an error stream)
   ** 
   ** It is also possible to get simple objects instead of instances created by the marshaller. Pass `raw` => true in the options.
   ** 
   ** @param string $text The JSON to decode.
   ** @param assoc $options Options map. Valid options: `raw`
   ** @return array|object 
   **
   */
	function decode ($text, $options = array()) {
    $this->error_code = 0;

    $object = json_decode($text);
    if (!$object) {
      $this->error_code = 54;
      return false;
    }
    
    if (isset($options['raw']) && $options['raw']) {
      return $object;
    }

    // Convert the object into any of the 3 forms
    if (is_array($object)) {
			$res = array ();
			foreach ($object as $item) {
				$res[] = $this->_create_object($item);
			}
			return $res;
    }
    elseif (isset($object->errors)) {
      return $object->errors;
    }
    else {
      return $this->_create_object($object);
    }
    
    // Should never come this far!
    return $object;
	}
  
  protected function _create_object($data) {
    return $this->marshaller->create_object($data);
  }
  
}

?>