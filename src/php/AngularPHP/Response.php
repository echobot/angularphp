<?php

namespace Echobot\AngularPHP;

/**
 * A class to hold the desired response from a call to AngularPHP
 * It is designed to be easily interpreted by any backend.
 *
 * @author    Dave Kingdon <kingdon@echobot.de>
 * @copyright 2014 Echobot Media Technologies GmbH
 */
class Response
{
    /**
     * The HTTP status code to return
     * @var integer
     */
    public $code = 200;

    /**
     * An array of headers to send, stored in a hash of name => value
     * @var array
     */
    public $headers = array();

    /**
     * The Content-Type header, stored separately for easy modification
     * @var string
     */
    public $contentType = 'application/json; charset=UTF-8';

    /**
     * The content, or body, of the response
     * @var string
     */
    public $content = '';


    /**
     * Add a header to the response
     * 
     * @param string $name
     *   The name of the header to add
     *   
     * @param string $value
     *   The value of the header
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }
}
