<?php
/**
 ** Handles XML serialization and de-serialization
 **
 ** @copyright Copyright 2010 Pascal Hurni
 ** @license http://www.opensource.org/licenses/mit-license.php Licensed under the MIT License
 ** @author Pascal Hurni <phi@ruby-reactive.org> http://github.com/phurni
 **
 */
 
class XmlSerializer {
  
  /// contains the extension used when generating URL, must be a string.
  var $extension = 'xml';
  
  /// contains the MIME type of the format this class handles, must be a string.
  var $mime_type = 'text/xml';

  /// An error code in case decode/encode fails.
  var $error_code = 0;

  protected $marshaller = null;
  
  protected $element_name;
  
  protected $tag_decoration = 'camelize';

	/**
	 ** @param object $marshaller An object that responds to create_object(assoc)
	 ** @param assoc $options Options. See below:
	 **                       element_name
	 **                         Encode needs the element_name for your objects in an underscored fashion,
	 **                         it will be camelized or dasherized internally.
	 **                         Example: 'element_name' => 'the_desired_element_name'
	 **                       tag_decoration
	 **                         Either 'dasherize' or 'camelize'. Will be used when encoding XML tag names.
	 **                         Defaults to 'camelize'
	 */
	function __construct ($marshaller, $options = array()) {
    $this->marshaller = $marshaller;
    $this->element_name = $options['element_name'];
    if (array_key_exists('tag_decoration', $options)) {
      $this->tag_decoration = $options['tag_decoration'];
    }
	}
  
  /**
   ** Encodes an object to XML
   ** Will return `false` if the process fails. Check $error_code to know what went wrong.
   **
   ** @param object|assoc $data The object to encode, will be iterated with foreach.
	 ** @param assoc $options Options. See below:
	 **                       tag_decoration
	 **                         Either 'dasherize' or 'camelize'. Defaults to the option passed at creation time.
   ** @return string The object encoded in XML
	 */
	function encode($data, $options = array()) {
    $this->error_code = 0;
    
    // dasherize or camelize based on options (either class options or method options)
    if ($this->tag_decoration == 'dasherize') {
      $element_name = $this->_dasherize($this->element_name);
    }
    else {
      $element_name = $this->_camelize($this->element_name);
    }
  
    $output = $options['instruct'] ? '<?xml version="1.0" encoding="UTF-8"?>' : '';
    $output .= '<' . $element_name . ">\n";
    foreach ($data as $k => $v) {
      if ($k != 'id' && $k != 'created-at' && $k != 'updated-at') {
        $output .= $this->_build_xml($k, $v);
      }
    }
    $output .= '</' . $element_name . '>';
    
    return $output;
	}

  /**
   ** Decodes an XML text to any of three forms.
   ** Will return `false` if the process fails. Check $error_code to know what went wrong.
   ** The first form is an array consisting of objects.
   ** The second form is a single object.
   ** The third form is an array of string each of them being an error. (The caller already knows the stream is an error stream)
   ** 
   ** It is also possible to get simple objects instead of instances created by the marshaller. Pass `raw` => true in the options.
   ** 
   ** @param string $text The XML to decode.
   ** @param assoc $options Options map. Valid options: `raw`
   ** @return array|object 
   **
   */
	function decode($text, $options = array()) {
    $this->error_code = 0;
  
		// parse XML response
    try {
      $xml = new SimpleXMLElement ($text);
    }
    catch (Exception $e) {
      $this->error_code = 54;
      return false;
    }

    // First check if we have an errors object
    $root = $this->_underscore($xml->getName());
    if ($root == 'errors') {
			// parse error message
      $err = array();
			foreach ($xml->children () as $child) {
				$err[] = $child;
			}
			return $err;
		}

    // Parse the XML (returns an object with nested objects)
    $parsed = $this->_object_shift($this->_parse_xml($xml, (object)null));

    // Stop here if raw parsing requested
    if (isset($options['raw']) && $options['raw']) {
      return $parsed;
    }

    // Okay, now check the type of the parsed data, it is either an array, this indicates a collection, or an object which indicates a single element.
    // We do NOT explicitely compare the $root to the collection_name or the element_name because when using STI or mixed object in collection,
    // we would break.
    if (is_array($parsed)) {
      // convert the items to the model class objects
      $result = array();
      foreach ($parsed as $item) {
        $result[] = $this->_create_object($item);
      }
      return $result;
    }
    else {
      // convert the single element to the model class object
      return $this->_create_object($parsed);
    }
	}
  
  protected function _create_object($data) {
    return $this->marshaller->create_object($data);
  }
  
  protected function _parse_xml($xml, $object) {
    $name = $this->_underscore($xml->getName());
    if (isset($xml['nil']) && $xml['nil'] == 'true') {
      $object->{$name} = null;
    }
    elseif (isset($xml['type']) && $xml['type'] == 'array') {
      $object->{$name} = array();
      foreach ($xml->children() as $child) {
        $object->{$name}[] = $this->_object_shift($this->_parse_xml($child, (object)null));
      }
    }
    else {
      if (count($xml->children()) == 0) {
        $object->{$name} = trim($xml[0]);
      }
      else {
        $child_object = (object) null;
        foreach ($xml->children() as $child) {
          $child_object = $this->_parse_xml($child, $child_object);
        }
        $object->{$name} = $child_object;
      }
    }
    return $object;
  }

  // convert the passed string to underscore notation. It is able to un-camelize and un-dasherize. These are the two forms of XML generated by Rails.
  protected function _underscore($camel_cased_word) {
    $camel_cased_word = str_replace('::', '/', $camel_cased_word);
    $camel_cased_word = preg_replace('/([A-Z]+)([A-Z])/', '\1_\2', $camel_cased_word);
    return strtolower(str_replace('-', '_', preg_replace('/([a-z\d])([A-Z])/','\1_\2', $camel_cased_word)));
  }
  
  protected function _dasherize($underscored_word) {
    return str_replace('_', '-', $underscored_word);
  }
  
  protected function _camelize($underscored_word) {
    return str_replace(" ", "", ucwords(str_replace("_", " ", $underscored_word)));
  }

  protected function _object_shift($object) {
    foreach ($object as $item) {
      return $item;
    }
  }

	/*
	 * Simple recursive function to build an XML response.
	 */
	protected function _build_xml($k, $v) {
		if (is_object($v) && strtolower(get_class($v)) == 'simplexmlelement') {
			return preg_replace('/<\?xml(.*?)\?>/', '', $v->asXML());
		}
		$res = '';
		$attrs = '';
		if (!is_numeric($k)) {
			$res = '<' . $k . '{{attributes}}>';
		}
		if (is_array($v)) {
			foreach ($v as $key => $value) {
				if (strpos($key, '@') === 0) {
					$attrs .= ' ' . substr($key, 1) . '="' . $this->_xml_entities($value) . '"';
					continue;
				}
				$res .= $this->_build_xml ($key, $value);
				$keys = array_keys($v);
				if (is_numeric($key) && $key != array_pop($keys)) {
					$res .= '</' . $k . ">\n<" . $k . '>';
				}
			}
		}
		else {
			$res .= $this->_xml_entities($v);
		}
		if (!is_numeric($k)) {
			$res .= '</' . $k . ">\n";
		}
		$res = str_replace('<' . $k . '{{attributes}}>', '<' . $k . $attrs . '>', $res);
		return $res;
	}

	/*
	 * Converts entities to unicode entities (ie. < becomes &#60;).
	 * From php.net/htmlentities comments, user "webwurst at web dot de"
	 */
	protected function _xml_entities($string) {
		$trans = get_html_translation_table(HTML_ENTITIES);

		foreach ($trans as $key => $value) {
			$trans[$key] = '&#' . ord ($key) . ';';
		}

		return strtr($string, $trans);
	}
}

?>