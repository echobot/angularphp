<?php

class ManifestTest extends PHPUnit_Framework_TestCase
{
    protected static $manifest;
    protected static $exampleClassEntry;

    public static function setUpBeforeClass()
    {
        self::$manifest = new \Echobot\AngularPHP\Manifest();
        self::$manifest->addDirectory(__DIR__ . '/../../resources/exportedClasses');
        self::$exampleClassEntry = self::$manifest->getEntryByClass('ExampleClass');
        self::$exampleClassEntry->finalise();
    }

    public function testCorrectClassesPresent()
    {
        $this->assertTrue(self::$manifest->containsClass('ExampleClass'));
        $this->assertFalse(self::$manifest->containsClass('SomeNonexistentClass'));
    }

    public function testExportedMethods()
    {
        $this->assertTrue(self::$exampleClassEntry->hasMethod('exportedMethod'));
        $this->assertFalse(self::$exampleClassEntry->hasMethod('nonExportedMethod'));
    }

    public function testExportedProperties()
    {
        $this->assertTrue(self::$exampleClassEntry->hasProperty('exportedProperty1'));
        $this->assertTrue(self::$exampleClassEntry->hasProperty('exportedProperty2'));
        $this->assertFalse(self::$exampleClassEntry->hasProperty('nonExportedProperty'));
    }
}

