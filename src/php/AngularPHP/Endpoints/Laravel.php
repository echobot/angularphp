<?php

namespace Echobot\AngularPHP\Endpoints;

use \Echobot\AngularPHP\Request;

/**
 * A class for creating a Laravel route to access an Echobot\AngularPHP object
 *
 * This can be created by calling Echobot\AngularPHP::createLaravelEndpoint('/url')
 * 
 * @author    Dave Kingdon <kingdon@echobot.de>
 * @copyright 2014 Echobot Media Technologies GmbH
 */
class Laravel extends \Echobot\AngularPHP
{
    /**
     * Creates a Laravel route, returning a closure which passes the raw input to AngularPHP and returns the response
     */
    protected function init()
    {
        $route = func_get_arg(0);

        $this->setErrorHandler(function (\Exception $e, Request $r) {
            \Log::error($e, $r->toArray());
        });

        $endpoint = $this;
        \Route::any($route, function () use ($endpoint) {

            $path = '/' . \Request::path();
            $referrer = \Request::header('referer');
            $host = \Request::header('host');

            if (($origin = \Request::header('Origin')) && count($this->corsHosts)) {
                $this->setCorsOrigin($origin);
            }

            /**
             * If being called remotely, add the domain name to the URI
             */
            if (strlen($referrer) && parse_url($referrer, PHP_URL_HOST) != $host) {
                $uri = '//' . $host . $path;
            } else {
                $uri = $path;
            }

            $request = new Request(\Request::json()->all());

            $response = $endpoint->setUri($uri)->execute($request, \Request::getMethod());

            return \Response::make($response->content, $response->code, $response->headers)
                ->header('Content-Type', $response->contentType);
        });
    }
}
