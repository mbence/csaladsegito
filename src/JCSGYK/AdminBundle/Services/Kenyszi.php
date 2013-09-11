<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Kenyszi Service
 */
class Kenyszi
{
    /** Service container */
    private $container;
    /** Kenyszi server url */
    private $serverUrl;

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
        $this->serverUrl = $this->container->getParameter('kenyszi_url');

    }

    public function run()
    {
        $request = 'Hello kenyszi!';
        $res = $this->doRequest($this->serverUrl, $request, "Content-Type: application/soap+xml\r\n");

        return $res;
    }

    /**
     * Sends a POST request and returns the response
     * @param string $url Server Url
     * @param string $data Data to send, it will be base64_encoded
     * @param string $optional_headers
     * @return string Server response
     * @throws Exception on errors
     */
    private function doRequest($url, $data, $optional_headers = null)
    {
        ini_set('track_errors', 1);

        $params = array('http' => array(
            'method' => 'POST',
            'content' => base64_encode($data)
        ));
        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            throw new Exception("Problem with $url, $php_errormsg");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            throw new Exception("Problem reading data from $url, $php_errormsg");
        }

        return $response;
    }


}