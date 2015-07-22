<?php

namespace Echobot\AngularPHP;

/**
 * The State contains the state of the objects AngularPHP knows about during its execution
 *
 * @author    Dave Kingdon <kingdon@echobot.de>
 * @copyright 2014 Echobot Media Technologies GmbH
 */
class State
{
    /**
     * The manifest defining the objects the state will contain
     * @var \Echobot\AngularPHP\Manifest
     */
    private $manifest;

    /**
     * The content of the state, stored in an id => data hash
     * @var array
     */
    private $state = array();


    /**
     * Created a State object which is aware of the passed manifest
     * 
     * @param Manifest $manifest
     *   The manifest
     */
    public function __construct(Manifest $manifest)
    {
        $this->manifest = $manifest;
    }


    /**
     * Converts a variable into a type suitable for conversion to JSON
     * 
     * @param mixed $something
     *   The variable to convert
     *   
     * @throws \UnexpectedValueException
     *   If an unsupported type is encountered
     *   
     * @return mixed
     *   The JSON-friendly return
     */
    public function export($something)
    {
        switch (gettype($something)) {
            case 'object':
                return $this->exportObject($something);
            case 'array':
                return $this->exportArray($something);
            case 'string':
            case 'integer':
            case 'boolean':
            case 'double':
            case 'NULL':
                return $something;
            case 'resource':
                return null;
            default:
                $type = gettype($something);
                throw new \UnexpectedValueException('Type not supported: ' . $type);
        }
    }


    /**
     * Converts an array into an array suitable for JSON encoding
     * 
     * @param mixed $something
     *   The array to export
     *   
     * @return array
     *   The exported array
     */
    private function exportArray($something)
    {
        $return = array();
        foreach ($something as $key => $s) {
            $return[$key] = $this->export($s);
        }
        return $return;
    }


    /**
     * Converts an object into an array suitable for JSON encoding
     * 
     * @param object $something
     *   The object to export
     *   
     * @return array
     *   The exported object
     */
    private function exportObject($something)
    {
        if (get_class($something) == 'stdClass') {
            foreach ((object) $something as $key => $value) {
                $something->$key = $this->export($value);
            }
            return $something;
        }

        if (!$entry = $this->manifest->getEntryByClass($something)) {
            return;
        }

        $return = array(
            '__class__' => $entry->alias
        );

        /**
         * We want to store a copy of any object which has non-static methods
         * as these can be called from JavaScript, and they can only sensibly
         * be called on the same object. If the class in question has marked
         * identifiers, the contents of these is used to generate the key. If
         * not, we use spl_object_hash and set the __id__ value of the
         * returned array. This is sent back in the 'context' of the call,
         * meaning methods called on JavaScript objects will call the same
         * method on their server-side counterpart.
         */
        $objId = $this->getIdOfObject($something);
        $newObjId = $this->generateObjectId($something);

        if (!$objId) {
            $objId = 'spl:' . md5(spl_object_hash($something));

            if (!$newObjId) {
                $newObjId = $objId;
            }
        }

        if ($objId && $newObjId && $objId != $newObjId) {
            $return['__id__'] = $newObjId;
            $return['__old_id__'] = $objId;
        } else {
            $return['__id__'] = $objId;
        }

        foreach ($entry->properties as $name => $prop) {
            if ($prop->isStatic) {
                $return[$name] = $this->export($something::$$name);
            } else {
                if (property_exists($something, $name)) {
                    $return[$name] = $this->export($something->$name);
                }
            }
        }
        return $return;
    }

    private function generateObjectId($object)
    {
        $entry = $this->manifest->getEntryByClass(get_class($object));

        if (!$entry) {
            throw new \RuntimeException('Class not in manifest');
        }

        if (!count($entry->identifiers)) {
            return;
        }

        $ids = array();

        foreach ($entry->identifiers as $prop) {
            $val = $object->$prop;
            if ($val != null) {
                $ids[] = sprintf('%s=%s', $prop, $val);
            }
        }

        if (count($ids)) {
            $src = 'SERVER:' . get_class($object) . ':' . join(',', $ids);
            $generatedId = md5($src);
            return $generatedId;
        }
    }


    /**
     * Returns the object ID for an object based on the manifest and the 
     * content of the object
     * 
     * @param object $object
     *   The object of which to generate the ID
     *   
     * @throws \RuntimeException
     *   If the object's class is not in the manifest
     *   
     * @return string
     */
    private function getIdOfObject($object)
    {
        foreach ($this->state as $id => $o) {
            if ($object === $o) {
                return $id;
            }
        }
    }


    /**
     * Takes an array and converts it back to the native PHP objects it 
     * represents
     * 
     * @param mixed $something
     *   The thing to import
     * 
     * @return mixed
     *   Either the unchanged context, or a new object based on the context
     */
    public function import($something)
    {
        if (!is_array($something)) {
            return $something;
        }

        foreach ($something as $var => $val) {
            if (is_array($val)) {
                $something[$var] = $this->import($val);
            }
        }

        if (isset($something['__class__'])) {
            return $this->importObject($something);
        } else {
            return $something;
        }
    }


    /**
     * Imports an array representation into an actual PHP object
     * 
     * @param array $something
     *   The object representation to import
     *   
     * @return object
     *   The PHP object
     */
    private function importObject($something)
    {
        if (isset($something['__id__'])) {
            if (isset($this->state[$something['__id__']])) {
                return $this->state[$something['__id__']];
            }
        }

        $entry = $this->manifest->getEntryByAlias($something['__class__']);

        $className = $entry->class;
        if (method_exists($className, '__set_state')) {
            $object = call_user_func(array($className, '__set_state'), $something);
        } else {
            $object = new $className;
        }

        if (isset($something['__id__'])) {
            $this->state[$something['__id__']] = $object;
        }

        $ref = new \ReflectionClass(get_class($object));

        foreach ($entry->properties as $name => $propertyDefinition) {
            if (!isset($something[$name])) {
                continue;
            }

            if ($propertyDefinition->isReadOnly) {
                continue;
            }

            $property = $ref->getProperty($name);
            $property->setAccessible(true);

            if ($propertyDefinition->isStatic) {
                $property->setValue(new \stdClass(), $something[$name]);
            } else {
                $property->setValue($object, $something[$name]);
            }
        }

        return $object;
    }
}
