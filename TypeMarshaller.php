<?php
/**
 ** Handles nested object creation from assoc content.
 **
 ** @copyright Copyright 2010 Pascal Hurni
 ** @license http://www.opensource.org/licenses/mit-license.php Licensed under the MIT License
 ** @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 **
 */
 
class TypeMarshaller {
  
  protected $type_map = null;

	/**
	 ** @param assoc $map a map of Resource type name to PHP model class name. Example: array('User' => 'User', 'Member' => 'User')
	 ** @param assoc $options Options. Currently none.
	 */
	function __construct ($map, $options = array()) {
    $this->type_map = $map;
	}
	
	/**
	 ** Creates an object of the PHP class found in the type map taken from the 'type' member of the passed $data assoc.
	 ** 
	 ** @param assoc $data the assoc (attributes => value) representation of the data to create. The value of any attribute may also be an assoc
	 **                    in which case the objects are recursively created.
	 ** @return object The created instance
	 */
	function create_object($data) {
	  $class_name = $this->class_name_for($data->type);
    $obj = new $class_name;
    // FIXME: Currently no check is done if the keys are valid when populating the object
    foreach ($data as $key => $value) {
      if (is_object($value)) {
        $value = $this->create_object($value);
      }
      $obj->set($key, $value);
    }
    return $obj;
	}
  
	/**
	 ** Returns the PHP class name associated to the passed data type name.
	 **
	 ** @param string $type Data type name (as found in the data assoc)
	 ** @return string The matching class name
	 */	 
	function class_name_for($type) {
	  return $this->type_map[$type];
	}

	/**
	 ** Add a new type => class association (or replace an existing)
	 **
	 ** @param string $type Data type name
	 ** @param string $class_name PHP class name
	 */
	function add_type($type, $class_name) {
	  $this->map[$type] = $class_name;
	}
  
}

?>