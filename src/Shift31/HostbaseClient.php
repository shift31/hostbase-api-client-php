<?php

namespace Shift31;

use Httpful\Handlers\JsonHandler;
use Httpful\Httpful;
use Httpful\Mime;
use Httpful\Request;
use Httpful\Response;

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
	 * @param string      $baseUrl
	 * @param string      $resource
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
	 * @param int    $limit
	 * @param bool   $showData
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function search($query, $limit = 10000, $showData = true)
	{
		$showData = $showData === true ? 1 : 0;

		$response = Request::get("{$this->uri}?q=" . urlencode($query) . "&size=$limit" . "&showData=$showData")
			->authenticateWith($this->username, $this->password
		)->send();

		if ($response instanceof Response && $response->hasErrors()) {
			throw new \Exception($response);
		} else {
			return $response->body;
		}
	}


	/**
	 * @param string|null $id
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function show($id = null)
	{
		$uri = $this->uri;

		if ($id != null) {
			$this->fixSubnetIdForHttp($id);
			$uri .= "/$id";
		}

		$response = Request::get($uri)->authenticateWith($this->username, $this->password)->send();

		if ($response instanceof Response && $response->hasErrors()) {
			throw new \Exception($response);
		} else {
			return $response->body;
		}
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

		if ($response instanceof Response && $response->hasErrors()) {
			throw new \Exception($response);
		} else {
			return $response->body;
		}
	}


	/**
	 * @param string $id
	 * @param mixed  $data
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function update($id, $data)
	{
		$this->fixSubnetIdForHttp($id);

		$response = Request::put("{$this->uri}/$id")
			->authenticateWith($this->username, $this->password)
			->body(json_encode($data))
			->sendsType('application/json')
			->send();

		if ($response instanceof Response && $response->hasErrors()) {
			throw new \Exception($response);
		} else {
			return $response->body;
		}
	}


	/**
	 * @param string $id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function destroy($id)
	{
		$this->fixSubnetIdForHttp($id);

		$response = Request::delete("{$this->uri}/$id")->authenticateWith(
			$this->username, $this->password
		)->send();

		if ($response instanceof Response && $response->hasErrors()) {
			throw new \Exception($response);
		} else {
			return true;
		}
	}

	protected function fixSubnetIdForHttp(&$id)
	{
		if ($this->getResource() == 'subnets') $id = str_replace('/', '_', $id);
	}
}