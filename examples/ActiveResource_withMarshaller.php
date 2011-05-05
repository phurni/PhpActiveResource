<?php
/**
 ** Example for ActiveResource PHP Client
 **
 ** @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 **
 */

require_once('ActiveResourceBase.php');
require_once('CurlTransporter.php');
require_once('XmlSerializer.php');
require_once('TypeMarshaller.php');

class ActiveResource extends ActiveResourceBase {
  static $marshaller;

  function __construct ($data = array ()) {
    // These may be set by subclasses constructor, so check them before re-setting
    if (!$this->transporter) $this->transporter = new CurlTransporter();
    if (!$this->serializer) $this->serializer = new XmlSerializer(self::$marshaller, array('element_name' => $this->element_name));

    parent::__construct($data);
  }
}

ActiveResource::$marshaller = new TypeMarshaller(array('User' => 'Person', 'Member' => 'Person', 'Project' => 'weird_PHP_classname_for_Project'));

?>