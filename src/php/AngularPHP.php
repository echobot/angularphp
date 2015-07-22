<?php

namespace Echobot;

use \Echobot\AngularPHP\Manifest;
use \Echobot\AngularPHP\State;
use \Echobot\AngularPHP\Request;
use \Echobot\AngularPHP\Response;

/**
 * An abstract class for creating an AngularJS module containing services 
 * containing selected properties, methods, and constants of specific PHP 
 * classes.
 *
 * @author    Dave Kingdon <kingdon@echobot.de>
 * @copyright 2014 Echobot Media Technologies GmbH
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AngularPHP
{
    /**
     * The URI this endpoint is listening on
     * @var string
     */
    private $uri = '';

    /**
     * The name of the generated module.  By default this will be the URI
     * @var string
     */
    private $moduleName = null;

    /**
     * The origin of the current request, to be interrogated
     * upon execution
     */
    private $corsOrigin;

    /**
     * Holds objects contained in the state sent from the client
     * @var \Echobot\AngularPHP\State
     */
    private $state = array();

    /**
     * Contains the actual manifest.    It is populated when finalise() is
     * called, for performance reasons
     * 
     * @var \Echobot\AngularPHP\Manifest
     */
    private $manifest;

    /**
     * A flag to specify whether debugging is enabled or not
     * @var boolean
     */
    private $debug = false;

    /**
     * A flag to specify whether to minify the JavaScript output
     * @var boolean
     */
    private $minify = false;

    /**
     * An array of hosts allowed access via CORS
     * @var array
     */
    protected $corsHosts = array();

    /**
     * An error callback, called when an error occurs during execution
     * @var callable|null
     */
    protected $errorHandler;

    /**
     * A static method for quickly constructing an object
     * @return self
     */
    public static function create()
    {
        $reflect = new \ReflectionClass(get_called_class());
        return $reflect->newInstanceArgs(func_get_args());
    }


    /**
     * Looks for the indicated class and returns the result of calling 
     * ::create() on it.
     * 
     * For example: Calling ::createLaravelEndpoint() will attempt to load 
     * class Echobot\AngularPHP\Endpoints\Laravel
     * 
     * @param string $method
     *   The static method called on this class, which includes the class to 
     *   look for
     *   
     * @param array $args
     *   The arguments passed to the method
     *   
     * @throws \BadMethodCallException
     *   If an non-create-endpoint method is specified
     *   
     * @throws \RuntimeException
     *   If the implied class is not found
     *   
     * @return \Echobot\AngularPHP
     *   The created endpoint
     */
    public static function __callStatic($method, $args)
    {
        $startsWithCreate = substr($method, 0, 6) == 'create';
        $endsWithEndpoint = substr($method, -8) == 'Endpoint';

        if (!$startsWithCreate || !$endsWithEndpoint) {
            throw new \BadMethodCallException('Method ' . $method . ' not found');
        }

        $type = substr($method, 6, -8);
        $class = 'Echobot\AngularPHP\Endpoints\\' . $type;

        if (!class_exists($class)) {
            $file = __DIR__ . '/AngularPHP/Endpoints/' . $type . '.php';
            if (!file_exists($file)) {
                throw new \RuntimeException('File not found');
            }
            require_once($file);
        }

        return call_user_func_array(array($class, 'create'), $args);
    }


    /**
     * Creates the manifest, and calls the init() method with the same
     * arguments.
     */
    public function __construct()
    {
        $this->manifest = new Manifest();
        $this->state = new State($this->manifest);
        call_user_func_array(array($this, 'init'), func_get_args());
    }


    /**
     * A method to allow endpoints to initialise.
     */
    abstract protected function init();


    /**
     * Allows the URI to be set/overridden
     * 
     * @param string $uri
     *   The URI to use
     *   
     * @return self
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }


    /**
     * Allows the module name to be set/overridden
     *
     * @param string $name
     *   The module name to use
     *
     * @return self
     */
    public function setModuleName($name)
    {
        $this->moduleName = $name;
        return $this;
    }


    /**
     * Specifies which hosts are to be allowed access via CORS
     * 
     * @param array $hosts
     *   The hosts to be allowed
     *   
     * @return self
     */
    public function enableCORS($hosts)
    {
        if ($hosts === true) {
            $this->corsHosts = array('*');
        } elseif (is_array($hosts)) {
            $this->corsHosts = $hosts;
        }

        return $this;
    }


    /**
     * Set the error handler
     * 
     * @param callback $callback
     *   The callback to be called when an exception is encountered
     *   
     * @return self
     */
    public function setErrorHandler(callable $callback)
    {
        $this->errorHandler = $callback;
        return $this;
    }


    /**
     * Set the debug flag
     * 
     * @param boolean $s
     *   Whether to enable or disable debugging
     *   
     * @return self
     */
    public function debug()
    {
        if (func_num_args() === 0) {
            return $this->debug;
        }

        $this->debug = (bool) func_get_arg(0);
        return $this;
    }


    /**
     * Set the minify flag
     * 
     * @param boolean $s
     *   Whether to enable or disable minification
     *   
     * @return self
     */
    public function minify()
    {
        if (func_num_args() === 0) {
            return $this->minify;
        }

        $this->minify = (bool) func_get_arg(0);
        return $this;
    }


    /**
     * Set the exportAll property
     * 
     * @param string $exportAll
     *   If equal to the string 'This is dangerous', enables this option.
     * 
     * @return self
     */
    public function exportAllMethods($warning)
    {
        $this->manifest->exportAll($warning == 'This is dangerous');
        return $this;
    }


    /**
     * Specified a class to be added to the manifest. The alias is optional, 
     * being auto-generated if ommitted.
     * 
     * @param string $className
     * @param string $alias
     * @return self
     */
    public function addClass($className, $alias = null)
    {
        $this->manifest->addClass($className, $alias);
        return $this;
    }


    /**
     * Adds a file to be scanned for exportable classes
     * 
     * @param string $filename
     *   The filename to scan for classes
     *   
     * @param string|array|null $restrict
     *   One or more patterns to match class names against
     *   
     * @return self
     */
    public function addFile($filename, $restrict = null)
    {
        $this->manifest->addFile($filename, $restrict);
        return $this;
    }


    /**
     * Scans a directory for files to scan for classes to export
     * 
     * @param string $path
     *   The directory to scan
     *   
     * @param string|array|null $restrict
     *   One or more patterns to match class names against
     *   
     * @return self
     */
    public function addDirectory($path, $restrict = null)
    {
        $this->manifest->addDirectory($path, $restrict);
        return $this;
    }


    /**
     * Sets the origin of the current request
     * 
     * @param string $origin
     *   The origin
     */
    protected function setCorsOrigin($origin)
    {
        $this->corsOrigin = $origin;
    }


    /**
     * Performs the actual request
     * 
     * @param string|array $request
     *   The request data
     *   
     * @return array
     *   The response
     */
    public function execute(Request $request)
    {
        $response = new Response();

        if (!is_null($this->corsOrigin)) {
            if (in_array($this->corsOrigin, $this->corsHosts)) {
                $response->addHeader('Access-Control-Allow-Origin', $this->corsOrigin);
                $response->addHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept');
                $response->addHeader('Access-Control-Allow-Credentials', 'true');
            }
        }

        try {
            $this->manifest->finalise();

            switch ($request->action) {
                case 'method':
                    ob_start();
                    $payload = $request->payload;
                    $context = $this->state->import($payload['context']);

                    $content = $this->callMethod(
                        $payload['class'],
                        $payload['name'],
                        $payload['arguments'],
                        $context
                    );

                    $response->content = json_encode(array(
                        'return' => $this->state->export($content),
                        'output' => ob_get_clean(),
                        'state' => $this->state->export($context)
                    ));

                    break;
                default:
                    $response->content = $this->getAngularJSModule();
                    $response->contentType = 'application/javascript; charset=UTF-8';
                    break;
            }
        } catch (\Exception $e) {
            $response->code = 500;

            $content = array(
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            );

            if ($this->debug) {
                $content['output'] = ob_get_clean();
                $content['trace'] = $e->getTraceAsString();
            } else {
                ob_end_clean();
            }

            if (is_callable($this->errorHandler)) {
                call_user_func($this->errorHandler, $e, $request);
            }

            $response->content = json_encode($content);
        }

        return $response;
    }


    /**
     * Calls a method on an object based on the contents of the payload
     * 
     * @param array $payload
     *   The data received from the HTTP request
     *   
     * @throws \RuntimeException
     *   If the specified object is not found
     *   
     * @return mixed
     *   The result of the method call in question
     */
    public function callMethod($alias, $method, $arguments, $context = null)
    {
        $entry = $this->manifest->getEntryByAlias($alias);

        $method = $entry->getMethodByName($method);
        $arguments = $this->state->import($arguments);

        if ($method->isStatic) {
            $callable = array($entry->class, $method->name);
        } else {
            if (!is_object($context)) {
                throw new \RuntimeException('Object not found');
            }

            $callable = array($context, $method->name);
        }

        return call_user_func_array($callable, $arguments);
    }


    /**
     * Outputs an AngularJS module, which contains definitions of all the classes exported.
     * Mixing PHP and JavaScript is not a great idea, but this solution is rather elegant in
     * that it requires no JS libraries, as its output is 100% AngularJS. It also removes
     * the need for bootstrapping individual manifests, or delaying bootstrapping while a
     * JSON-fetched manifest is parsed. Everything is present upon normal AngularJS
     * bootstrapping, which is the best possible outcome.
     * 
     * @return string
     *   The actual AngularJS module
     */
    public function getAngularJSModule()
    {
        if ($this->minify) {
            $jsSrc = 'echobot-angularphp.min.js';
        } else {
            $jsSrc = 'echobot-angularphp.js';
        }

        $code = file_get_contents(__DIR__ . '/../js/' . $jsSrc);
        
        /**
         * Replace the placeholders for the manifest & URI with their proper
         * values.
         */
        $encodingOptions = 0;
        if (!$this->minify && defined('JSON_PRETTY_PRINT')) {
            $encodingOptions = JSON_PRETTY_PRINT;
        }

        $toReplace = array(
            '$MANIFEST$' => json_encode($this->manifest->export(), $encodingOptions),
            '$URI$' => $this->uri,
            '$MODULE$' => json_encode($this->moduleName ? $this->moduleName : $this->uri),
            '$DEBUG$' => json_encode($this->debug)
        );

        foreach ($toReplace as $what => $with) {
            $code = str_replace($what, $with, $code);
        }

        return $code;
    }
}
