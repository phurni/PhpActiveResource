<?php
/**
 ** Example for ActiveResource PHP Client
 **
 ** @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 **
 */

require_once('ActiveResourceBase.php');
require_once('CurlTransporter.php');
require_once('JsonSerializer.php');
require_once('inflector.php');  // PHPonTrax

class ActiveResource extends ActiveResourceBase {

  var $inflector = null;

  function __construct($data = array ()) {
    // These may be set by subclasses constructor, so check them before re-setting
    if (!$this->transporter) $this->transporter = new CurlTransporter();
    if (!$this->serializer) $this->serializer = new JsonSerializer($this);
    if (!$this->inflector) $this->inflector = new Inflector();
    
    // Inflect names or guess them from the class name
    if (!$this->element_name && !$this->collection_name) {
      $name = get_class($this);
      $this->element_name = $this->inflector->underscore($name);
      $this->collection_name = $this->inflector->pluralize($this->element_name);
    } elseif ($this->element_name && !$this->collection_name) {
      $this->collection_name = $this->inflector->pluralize($this->element_name);
    } elseif (!$this->element_name && $this->collection_name) {
      $this->element_name = $this->inflector->singularize($this->collection_name);
    }
    
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