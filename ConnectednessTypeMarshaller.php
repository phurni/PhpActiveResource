<?php

require_once ('TypeMarshaller.php');

/**
 ** Marshaller aware of nested _link_ objects that will be lazy loaded when accessed.
 ** Model objects are created by passing the link as an option, example:
 **   $user = new User(array(), array('link' => $link))
 **
 ** @copyright Copyright 2012 Pascal Hurni
 ** @license http://www.opensource.org/licenses/mit-license.php Licensed under the MIT License
 ** @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 **
 */
 
class ConnectednessTypeMarshaller extends TypeMarshaller {
	
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
    	  $class_name = $this->class_name_for($link->type);
    	  if ($class_name) {
          $linked_obj = new $class_name(array(), array('link' => $link));
          $value = $linked_obj;
        }
        else {
      	  // put the string representation if we have no specified type
          $value = $link->name;
        }
      }
      elseif (is_object($value)) {
        $value = $this->create_object($value);
      }
      $obj->set($key, $value);
    }
    return $obj;
	}
  
}

?>