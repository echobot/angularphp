<?php

namespace Echobot\AngularPHP\Manifest\Entry;

/**
 * A representation of a property of a class
 *
 * @author    Dave Kingdon <kingdon@echobot.de>
 * @copyright 2014 Echobot Media Technologies GmbH
 */
class Property
{
    /**
     * The name of the class
     * @var string
     */
    public $name;

    /**
     * Whether to be exported
     * @var boolean
     */
    public $export = false;

    /**
     * Whether an identifier
     * @var boolean
     */
    public $isIdentifier;

    /**
     * Whether read-only
     * @var boolean
     */
    public $isReadOnly;

    /**
     * Whether the property is public
     * @var boolean
     */
    public $isPublic = false;

    /**
     * Whether the property is static
     * @var boolean
     */
    public $isStatic = false;

    /**
     * The default value of the property
     * @var mixed
     */
    public $defaultValue = null;


    public function __construct(\ReflectionProperty $reflector)
    {
        $this->name = $reflector->name;

        $comment = $reflector->getDocComment();
        $this->export = preg_match('/@Export\s/', $comment) > 0;
        $this->isReadOnly = preg_match('/@ReadOnly\s/', $comment) > 0;
        $this->isIdentifier = preg_match('/@Id\s/', $comment) > 0;

        $this->isPublic = $reflector->isPublic();
        $this->isStatic = $reflector->isStatic();

        $defaultProperties = $reflector->getDeclaringClass()->getDefaultProperties();

        if (isset($defaultProperties[$this->name])) {
            $this->defaultValue = $defaultProperties[$this->name];
        }
    }


     /**
     * Returns an array representation of the property
     * 
     * @return array
     *   The definition
     */
    public function export()
    {
        $definition = array(
            'name' => $this->name,
            'value' => $this->defaultValue
        );

        if ($this->isStatic) {
            $definition['static'] = true;
        }

        if ($this->isPublic) {
            $definition['public'] = true;
        }

        if ($this->isReadOnly) {
            $definition['readOnly'] = true;
        }

        return $definition;
    }
}
