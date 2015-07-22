<?php

namespace Echobot\AngularPHP\Manifest\Entry;

/**
 * A representation of a method of a class
 *
 * @author    Dave Kingdon <kingdon@echobot.de>
 * @copyright 2014 Echobot Media Technologies GmbH
 */
class Method
{
    /**
     * The name of the method
     * @var string
     */
    public $name;

    /**
     * The owning class
     * @var string
     */
    public $class;

    /**
     * Whether to be exported
     * @var boolean
     */
    public $export = false;

    /**
     * The number of required parameters
     * @var integer
     */
    public $reqParamCount = 0;

    /**
     * Whether the method is public
     * @var boolean
     */
    public $isPublic;

    /**
     * Whether the method is static
     * @var boolean
     */
    public $isStatic;


    public function __construct(\ReflectionMethod $reflector)
    {
        $this->name = $reflector->name;
        $this->class = $reflector->class;
        $this->export = preg_match('/@Export\s/', $reflector->getDocComment()) > 0;
        $this->reqParamCount = $reflector->getNumberOfRequiredParameters();
        $this->isPublic = $reflector->isPublic();
        $this->isStatic = $reflector->isStatic();
    }


    /**
     * Returns an array representation of the method
     * 
     * @return array
     *   The definition
     */
    public function export()
    {
        $definition = array(
            'name' => $this->name,
            'parameterCount' => $this->reqParamCount
        );

        if ($this->isStatic) {
            $definition['static'] = true;
        }

        if ($this->isPublic) {
            $definition['public'] = true;
        }

        return $definition;
    }
}
