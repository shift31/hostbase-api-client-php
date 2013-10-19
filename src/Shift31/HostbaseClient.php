<?php

namespace Shift31;

use Httpful\Httpful;
use Httpful\Request;
use Httpful\Response;

class HostbaseClient
{


	protected $uri;

	protected $username;

	protected $password;


	/**
	 * @param string $baseUrl
	 * @param string|null $username
	 * @param string|null $password
	 */
	public function __construct($baseUrl, $username = null, $password = null)
	{
		$this->uri = "$baseUrl/hosts";
		$this->username = $username;
		$this->password = $password;
	}


	/**
	 * @param string $query
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function search($query)
	{
		$response = Request::get("{$this->uri}?q=" . urlencode($query))->authenticateWith(
			$this->username, $this->password
		)->send();

		if ($response->hasErrors()) {
			throw new \Exception($response);
		} else {
			return $response->body;
		}
	}


	/**
	 * @param string|null $fqdn
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function show($fqdn = null)
	{
		$uri = $this->uri;

		if ($fqdn != null) {
			$uri .= "/$fqdn";
		}

		$response = Request::get($uri)->send();

		if ($response->hasErrors()) {
			throw new \Exception($response);
		} else {
			return $response->body;
		}
	}


	/**
	 * @param array $data
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function store(array $data)
	{
		$response = Request::post($this->uri)
			->authenticateWith($this->username, $this->password)
			->body(json_encode($data))
			->sendsType('application/json')
			->send();

		if ($response->hasErrors()) {
			throw new \Exception($response);
		} else {
			return $response->body;
		}
	}


	/**
	 * @param string $fqdn
	 * @param array  $data
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	public function update($fqdn, array $data)
	{
		$response = Request::put("{$this->uri}/$fqdn")
			->body(json_encode($data))
			->sendsType('application/json')
			->send();

		if ($response->hasErrors()) {
			throw new \Exception($response);
		} else {
			return $response->body;
		}
	}


	/**
	 * @param string $fqdn
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function destroy($fqdn)
	{
		$response = Request::delete("{$this->uri}/$fqdn")->send();

		if ($response->hasErrors()) {
			throw new \Exception($response);
		} else {
			return true;
		}
	}
}