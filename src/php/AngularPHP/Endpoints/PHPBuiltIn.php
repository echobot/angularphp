<?php

namespace Echobot\AngularPHP\Endpoints;

use \Echobot\AngularPHP\Request;

/**     
 * @author    Dave Kingdon <kingdon@echobot.de>
 * @copyright 2014 Echobot Media Technologies GmbH
 */
class PHPBuiltIn extends \Echobot\AngularPHP
{

    /**
     * Utility function to check whether the request is coming from a remote
     * site
     * 
     * @return boolean
     *   Whether the request is from a remote site
     */
    private function isRemote()
    {
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return false;
        }

        $referrerHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $serverHost = parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST);
        return $referrerHost != $serverHost;
    }


    /**
     * Sets up the URI to create the module on, based on whether it's being 
     * accessed locally or not.
     */
    protected function init()
    {
        if ($this->isRemote()) {
            $uri = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $uri = $_SERVER['REQUEST_URI'];
        }

        $this->setUri($uri);
    }


    /**
     * Calls the execute method, passing the raw post data, after setting up CORS
     */
    public function run()
    {
        $headers = apache_request_headers();

        if (count($this->corsHosts)) {
            $origin = false;

            if (isset($headers['Origin'])) {
                $origin = $headers['Origin'];
            } elseif (isset($headers['Referer'])) {
                $parts = parse_url($headers['Referer']);
                $origin = sprintf('%s://%s', isset($parts['scheme']) ? $parts['scheme'] : 'http', $parts['host']);
            }

            if ($origin) {
                $this->setCorsOrigin($origin);
            }
        }

        $contents = file_get_contents('php://input');

        $request = new Request($contents);

        $response = $this->execute($request);

        if ($response->code != 200) {
            header('HTTP/1.0 ' . $response->code, true, $response->code);
        }

        if (isset($response->contentType)) {
            header('Content-Type: ' . $response->contentType);
        }

        if (isset($response->headers)) {
            foreach ($response->headers as $header => $value) {
                header($header . ': ' . $value);
            }
        }

        if (isset($response->content)) {
            echo $response->content;
        }


    }
}
