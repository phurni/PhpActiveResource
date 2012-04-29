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
  
  protected $link_object_name = 'link';

	/**
	 ** @param assoc $map a map of Resource type name to PHP model class name. Example: array('User' => 'User', 'Member' => 'User')
	 ** @param assoc $options Options. Currently none.
	 */
	function __construct ($map, $options = array()) {
    $this->type_map = $map;
    if (isset($options['link_object_name']) && $options['link_object_name']) {
      $this->link_object_name = $options['link_object_name'];
    }
	}
	
	/**
	 ** Creates an object of the PHP class found in the type map taken from the 'type' member of the passed $data assoc.
	 ** 
	 ** @param assoc $data the assoc (attributes => value) representation of the data to create. The value of any attribute may also be an assoc
	 **                    in which case the objects are recursively created except for an assoc having only a _link_ key.
	 ** @return object The created instance
	 */
	function create_object($data) {
	  // return null if we receive an empty object
	  if (count((array)$data) == 0) {
	    return null;
	  }
	  
	  $class_name = $this->class_name_for($data->type);
    $obj = new $class_name;
    // FIXME: Currently no check is done if the keys are valid when populating the object
    foreach ($data as $key => $value) {
      if ($link = $this->_get_link($value)) {
        $value = $link->name;
      }
      elseif (is_object($value)) {
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
	  return isset($this->type_map[$type]) ? $this->type_map[$type] : null;
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
	
	/*
	 * Determine if the passed value is a Link and return it. Returns null if not a link.
	 */
	protected function _get_link($data) {
    // Check if it is a Link
    if (count((array)$data) == 1 && isset($data->{$this->link_object_name})) {
      return $data->{$this->link_object_name};
    }
    else {
      return null;
    }
	}
  
}

?>