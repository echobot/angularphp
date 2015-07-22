<?php

namespace Echobot\AngularPHP;

/**
 * A class to hold the desired request to AngularPHP
 * It is designed to be easily created by any backend.
 *
 * @author    Dave Kingdon <kingdon@echobot.de>
 * @copyright 2014 Echobot Media Technologies GmbH
 */
class Request
{
    /**
     * The name of the action for AngularPHP to perform
     * @var [type]
     */
    public $action;

    /**
     * The data to be passed to AngularPHP
     * @var array
     */
    public $payload = array();


    /**
     * Turns a passed request into a request recognisable by AngularPHP
     * 
     * @param string|object|array $rawRequest
     *   The raw request
     *
     * @throws \Exception
     *   If the passed raw request string is unparseable as JSON
     */
    public function __construct($rawRequest)
    {
        if (is_string($rawRequest)) {
            if (strlen($rawRequest)) {
                $rawRequest = @json_decode($rawRequest, true);
                if (is_null($rawRequest)) {
                    throw new \Exception('Invalid request');
                }
            } else {
                $rawRequest = array();
            }
        } else {
            $rawRequest = (array) $rawRequest;
        }

        if (!isset($rawRequest['action'])) {
            $this->action = null;
        } else {
            $this->action = $rawRequest['action'];
        }

        if (isset($rawRequest['payload'])) {
            $this->payload = $rawRequest['payload'];
        }
    }


    /**
     * Returns an array representation of the request
     *
     * @return array
     *   The representation of the request
     */
    public function toArray()
    {
        return array(
            'action' => $this->action,
            'payload' => $this->payload
        );
    }
}
