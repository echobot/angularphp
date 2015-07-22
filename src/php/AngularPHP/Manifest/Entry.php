<?php

namespace Echobot\AngularPHP\Manifest;

use \Echobot\AngularPHP\Manifest\Entry\Method;
use \Echobot\AngularPHP\Manifest\Entry\Property;

/**
 * A description of a class held in the manifest
 *
 * @author    Dave Kingdon <kingdon@echobot.de>
 * @copyright 2014 Echobot Media Technologies GmbH
 */
class Entry
{
    /**
     * The manifest this entry belongs to
     * @var \Echobot\AngularPHP\Manifest
     */
    private $manifest;

    /**
     * Whether to export all properties & methods, regardless.
     * @var boolean
     */
    public $exportAll = false;

    /**
     * The class's name
     * @var string
     */
    public $class;

    /**
     * The class's alias in AngularPHP
     * @var string
     */
    public $alias;

    /**
     * The name of the class this entry's class extends, if any
     * @var string|null
     */
    public $extends;

    /**
     * The names of fields which make up the identifier of this class
     * @var array
     */
    public $identifiers = array();

    /**
     * The properties belonging to this class
     * @var array
     */
    public $properties = array();

    /**
     * The methods belonging to this class
     * @var array
     */
    public $methods = array();

    /**
     * The constants belonging to this class
     * @var array
     */
    public $constants = array();


    public function __construct($class, $alias, \Echobot\AngularPHP\Manifest $manifest)
    {
        if (substr($class, 0, 1) == '\\') {
            $class = substr($class, 1);
        }

        $this->class = $class;
        $this->alias = $alias;
        $this->manifest = $manifest;
    }


    /**
     * Compares a class name with this entry's class name
     * 
     * @param string $className
     *   The class name to compare
     *   
     * @return boolean
     *   Whether the class name matches this entry's class's name
     */
    private function sameClass($className)
    {
        return $className == $this->class;
    }


    /**
     * Populate the entry with information about the class
     */
    public function finalise()
    {
        $ref = new \ReflectionClass($this->class);

        $classComment = $ref->getDocComment();
        $this->exportAll = preg_match('/@ExportAll\s/', $classComment) > 0;
        $parent = $ref->getParentClass();

        if ($parent && $this->manifest->containsClass($parent->getName())) {
            $this->extends = $this->manifest->getEntryByClass($parent->getName())->alias;
        }

        $this->finaliseMethods();
        $this->finaliseProperties();
        $this->finaliseConstants();
        $this->finaliseTraits();
    }


    /**
     * Add the class's methods to the manifest
     */
    private function finaliseMethods()
    {
        $ref = new \ReflectionClass($this->class);

        foreach ($ref->getMethods() as $methodReflector) {
            $needExportComment = !$this->exportAll && !$this->manifest->exportAll();

            $method = new Method($methodReflector);

            if ($needExportComment && !$method->export) {
                continue;
            }

            if (!$this->sameClass($method->class)) {
                continue;
            }

            $this->methods[$method->name] = $method;
        }
    }


    /**
     * Add the class's properties to the manifest
     */
    private function finaliseProperties()
    {
        $ref = new \ReflectionClass($this->class);
        
        foreach ($ref->getProperties() as $propertyReflector) {
            $needExportComment = !$this->exportAll && !$this->manifest->exportAll();

            $property = new Property($propertyReflector);

            if ($needExportComment && !$property->export) {
                continue;
            }

            $this->properties[$property->name] = $property;

            if ($property->isIdentifier) {
                $this->identifiers[] = $property->name;
            }
        }
    }


    /**
     * Add the class's traits to the manifest
     */
    private function finaliseTraits()
    {
        $ref = new \ReflectionClass($this->class);
        
        foreach ($ref->getTraits() as $traitReflector) {
            $entry = new self($traitReflector->getName(), null, $this->manifest);
            $entry->finalise();
            $this->methods = array_merge($this->methods, $entry->methods);
            $this->properties = array_merge($this->properties, $entry->properties);
            $this->identifiers = array_merge($this->identifiers, $entry->identifiers);
            $this->constants = array_merge($this->constants, $entry->constants);
        }
    }


    /**
     * Add the class's constants to the manifest
     */
    public function finaliseConstants()
    {
        $ref = new \ReflectionClass($this->class);

        foreach ($ref->getConstants() as $name => $value) {
            $this->constants[$name] = array(
                'name' => $name,
                'value' => $value
            );
        }
    }

    /**
     * Returns whether a method with the specified name exists in this entry
     * 
     * @param  string  $name
     *   The method name for which to search
     *   
     * @return boolean
     *   Whether the method exists
     */
    public function hasMethod($name)
    {
        return isset($this->methods[$name]);
    }

    /**
     * Returns whether a property with the specified name exists in this entry
     * 
     * @param  string  $name
     *   The method name for which to search
     *   
     * @return boolean
     *   Whether the method exists
     */
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * Return a method by its name
     * 
     * @param  string $name
     *   The method's name
     *
     * @throws \RuntimeException
     *   If the method does not exist
     * 
     * @return Entry\Method|null
     *   The method, or null if not found
     */
    public function getMethodByName($name)
    {
        if (isset($this->methods[$name])) {
            return $this->methods[$name];
        }

        throw new \RuntimeException('Method ' . $name . ' not found');
    }


    /**
     * Returns an array representation of this class & its methods, properties, constants, and identifiers
     * 
     * @return array
     *   The representation
     */
    public function export()
    {
        $export = array(
            'name' => $this->alias,
            'extends' => $this->extends,
            'identifiers' => $this->identifiers,
            'properties' => array(),
            'methods' => array(),
            'constants' => $this->constants
        );

        foreach ($this->properties as $property) {
            $export['properties'][$property->name] = $property->export();
        }

        foreach ($this->methods as $method) {
            $export['methods'][$method->name] = $method->export();
        }

        return $export;
    }
}
