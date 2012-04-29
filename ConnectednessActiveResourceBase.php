<?php

require_once ('ActiveResourceBase.php');

/**
 ** Extends ActiveResource with handling for linked sub-objects (connectedness).
 ** 
 ** Usage:
 ** @code
 **
 ** ActiveResource.php
 ** <?php
 ** require_once('ActiveResourceBase.php');
 ** require_once('CurlTransporter.php');
 ** require_once('XmlSerializer.php');
 ** require_once('JsonSerializer.php');
 ** require_once('ConnectednessTypeMarshaller.php');
 **
 ** // Setup a base class with all your chosen concerns
 ** class ActiveResource extends ActiveResourceBase {
 **   static $marshaller;
 **
 **   function __construct ($data = array(), $options = array()) {
 **     // These may be set by subclasses constructor, so check them before re-setting
 **     if (! $this->transporter) $this->transporter = new CurlTransporter();
 **     if (! $this->serializer) $this->serializer = new XmlSerializer(self::$marshaller, array('element_name' => $this->element_name)));
 **     
 **     parent::__construct($data, $options);
 **   }
 ** }
 ** ?>
 ** @endcode 
 **
 ** @copyright Copyright 2012 Pascal Hurni
 ** @license http://www.opensource.org/licenses/mit-license.php Licensed under the MIT License
 ** @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 **
 */
 
class ConnectednessActiveResourceBase extends ActiveResourceBase {
  
  protected $_loaded = false;
  
  protected $_link = null;

  /**
   ** @param assoc $data attributes map to initialize the instance with.
   */
  function __construct($data = array(), $options = array()) {
    if (isset($options['link'])) {
      $this->_link = $options['link'];
    }
    else {
      $this->_loaded = true;
      parent::__construct($data);
    }
  }

  /*
   * Sets multiple attributes for the record
   */

  protected function _set_data($data) {
    $this->_loaded = true;
    parent::_set_data($data);
  }

  /*
   * Sets an attribute for the record
   */

  protected function _set_attribute($name, $value) {
    // load if not already
    if ($this->_loaded) $this->_get_data();
    parent::_set_attribute($name, $value);
  }

  /*
   * Returns the attributes of the record or load it
   */

  protected function _get_data() {
    if (!$this->_loaded) {
      $data = $this->_decode($this->_get(array('base_url' => $this->_link->url)));
      if ($data === false) {
        // Generate an error;
      }
      // Transfer data from the new record to this.
      $this->_set_data($data->_get_data());
    }
    return parent::_get_data();
  }

  /*
   * Returns the id of the record or null if it is a freshly created one
   */

  protected function _get_id() {
    return $this->_loaded ? parent::_get_id() : $this->_link->id;
  }

  /**
   ** Return the string representation of this object.
   ** It will return the value of the first attribute present that is named after the ones
   ** stored in the array $to_string_attributes.
   */
  public function __toString() {
    return $this->_loaded ? parent::__toString() : $this->_link->name;
  }
}

?>
