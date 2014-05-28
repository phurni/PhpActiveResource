# PHP ActiveResource

Flexible implementation of the Ruby on Rails ActiveResource REST client for PHP.
Intended to work with RoR-based REST servers, which all share similar
API patterns.

The implementation is decoupled with many concerns:

 - ActiveResource itself
 - Transport
 - Serialization
 - Inflection
  
The provided serializations are XML and JSON.

An AuthenticatedActiveResourceBase class is also provided that may be used in conjonction with [authlogic_api](http://github/phurni/authlogic_api)

## Legal

Copyright 2010 Pascal Hurni

[Licensed under the MIT License](http://www.opensource.org/licenses/mit-license.php)

## Usage

ActiveResource.php:

```php
<?php

    require_once('ActiveResourceBase.php');
    require_once('CurlTransporter.php');
    require_once('XmlSerializer.php');
    require_once('JsonSerializer.php');
    require_once('TypeMarshaller.php');

    // Setup a base class with all your chosen concerns
    class ActiveResource extends ActiveResourceBase {
      static $marshaller;

      function __construct ($data = array ()) {
        // These may be set by subclasses constructor, so check them before re-setting
        if (! $this->transporter) $this->transporter = new CurlTransporter();
        if (! $this->serializer) $this->serializer = new XmlSerializer(self::$marshaller, array('element_name' => $this->element_name)));
    
        parent::__construct($data);
      }
    }

    ActiveResource::$marshaller = new TypeMarshaller(array('User' => 'Person', 'Member' => 'Person', 'Project' => 'weird_PHP_classname_for_Project'));
    
?>
```

Person.php

```php
<?php
    require_once ('ActiveResource.php');

    class Person extends ActiveResource {
        var $site = 'http://localhost:3000/';
        var $element_name = 'person';
    }
?>
```

UseCases.php

```php
<?php
    require_once ('Person.php');

    // create a new person
    $person = new Person( array('firstname' => 'John', 'lastname' => 'Doe') );
    if (!$person->save()) {
      echo $person->error_message;
    }

    // for all other operations you must have a blank object as a handle.
    $Person = new Person();

    // find people
    $people = $Person->find('all');
    if ($people === false) { // don't use "if (!$people)" because an empty array will match!
      echo $people->error_message;
    }
    else {
      foreach ($people as $person) {
        var_dump($person->to_object());
      }
    }
 
    // find people with some options (passed as query string parameters)
    $people = $Person->find('all', array('lastname' => 'Doe'));

    // find a known person
    $person = $Person->find(123);
    if ($person === false)
      echo $Person->error_message;   // read carefully $Person with a uppercase P
    }
    else {
      var_dump($person->to_object());
    }

    // update an attribute
    // have a Person object in $person (find() it) 
    $person->phone_number = '555-98-76';
    if (!$person->save()) {
      echo $person->error_message;
    }

    // delete a person
    // have a Person object in $person (find() it) 
    if (!$person->destoy()) {
      echo $person->error_message;
    }
 
    // custom method
    object = $Person->get('banned', array('level' => 3));
    if ($object === false) {
      echo $person->error_message;
    }

?>
```
