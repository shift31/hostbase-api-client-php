<?php

namespace Shift31;

use Httpful\Handlers\JsonHandler;
use Httpful\Httpful;
use Httpful\Mime;
use Httpful\Request;
use Httpful\Response;

/**
 * Class HostbaseClient
 * @package Shift31
 *
 * @todo switch to using Guzzle
 */
class HostbaseClient
{
    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $resource;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var null|string
     */
    protected $username;

    /**
     * @var null|string
     */
    protected $password;


    /**
     * @param string $baseUrl
     * @param string $resource
     * @param string|null $username
     * @param string|null $password
     */
    public function __construct($baseUrl, $resource = 'hosts', $username = null, $password = null)
    {
        $this->baseUrl = $baseUrl;
        $this->resource = $resource;
        $this->setUri();
        $this->username = $username;
        $this->password = $password;
    }


    protected function setUri()
    {
        $this->uri = "{$this->baseUrl}/{$this->resource}";
    }


    /**
     * @param $resource
     *
     * @return HostbaseClient $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        $this->setUri();

        return $this;
    }


    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }


    /**
     * @return $this
     */
    public function decodeJsonAsArray()
    {
        // Example overriding the default JSON handler with one that encodes the response as an array
        Httpful::register(Mime::JSON, new JsonHandler(array('decode_as_array' => true)));

        return $this;
    }


    /**
     * @param string $query
     * @param int $limit
     * @param bool $showData
     *
     * @param null $include
     * @param null $exclude
     * @return array
     */
    public function search($query, $limit = 10000, $showData = true, $include = null, $exclude = null)
    {
        $showData = $showData === true ? 1 : 0;

        $uri = "{$this->uri}?q=" . urlencode($query) . "&size=$limit" . "&showData=$showData";

        if ($include) {
            $uri .= "&include=$include";
        } elseif($exclude) {
            $uri .= "&exclude=$exclude";
        }

        $response = Request::get($uri)
            ->authenticateWith($this->username, $this->password
            )->send();

        return $this->processResponse($response);
    }


    /**
     * @param string|null $id
     *
     * @param null $include
     * @param null $exclude
     * @return \stdClass
     */
    public function show($id = null, $include = null, $exclude = null)
    {
        $uri = $this->uri;

        if ($id != null) {
            $this->mangleCidrNotation($id);
            $uri .= "/$id";
        }

        if ($include) {
            $uri .= "?include=$include";
        } elseif($exclude) {
            $uri .= "?exclude=$exclude";
        }

        $response = Request::get($uri)->authenticateWith($this->username, $this->password)->send();

        return $this->processResponse($response);
    }


    /**
     * @param mixed $data
     *
     * @return \stdClass
     * @throws \Exception
     */
    public function store($data)
    {
        $response = Request::post($this->uri)
            ->authenticateWith($this->username, $this->password)
            ->body(json_encode($data))
            ->sendsType('application/json')
            ->send();

        return $this->processResponse($response);
    }


    /**
     * @param string $id
     * @param mixed $data
     *
     * @return \stdClass
     * @throws \Exception
     */
    public function update($id, $data)
    {
        $this->mangleCidrNotation($id);

        $response = Request::put("{$this->uri}/$id")
            ->authenticateWith($this->username, $this->password)
            ->body(json_encode($data))
            ->sendsType('application/json')
            ->send();

        return $this->processResponse($response);
    }


    /**
     * @param string $id
     *
     * @return bool
     * @throws \Exception
     */
    public function destroy($id)
    {
        $this->mangleCidrNotation($id);

        $response = Request::delete("{$this->uri}/$id")->authenticateWith(
            $this->username, $this->password
        )->send();

        if ($response instanceof Response && $response->hasErrors()) {
            throw new \Exception($this->getErrorMessage($response));
        } else {
            return true;
        }
    }


    /**
     * @param Response $response
     *
     * @return mixed
     * @throws \Exception
     */
    protected function processResponse(Response $response)
    {
        if ($response->hasErrors()) {
            throw new \Exception($this->getErrorMessage($response));
        }

        return is_array($response->body) ? $response->body['data'] : $response->body->data;
    }


    /**
     * @param Response $response
     *
     * @return mixed
     */
    protected function getErrorMessage(Response $response)
    {
        if ($response->hasBody()) {
            if (is_array($response->body)) {
                return isset($response->body['error']['message']) ? $response->body['error']['message'] : $response->raw_body;
            } else {
                return isset($response->body->error->message) ? $response->body->error->message : $response->raw_body;
            }
        } else {
            return $response->raw_body;
        }
    }


    /**
     * @param $id
     */
    protected function mangleCidrNotation(&$id)
    {
        if ($this->getResource() == 'subnets') $id = str_replace('/', '_', $id);
    }
}