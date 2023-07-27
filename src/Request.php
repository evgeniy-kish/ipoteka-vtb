<?php

namespace inc\Bank;

class Request{

	/**
	 * Request constructor.
	 * @param string $url
	 * @param array $fields
	 * @param array $header
	 */


	protected $curl = null;
	protected $httpCode = null;


	public function __construct(protected string $url = '', protected array $fields = [], protected array $header = [], protected string $typeRequest = 'POST'){
		$this->curl = curl_init();

		curl_setopt_array($this->curl, array(
			CURLOPT_URL => $this->url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $this->typeRequest,
			CURLOPT_POSTFIELDS => json_encode($fields),
			CURLOPT_HTTPHEADER => $this->header
		));
	}

	public function exec()
	{
		$res = curl_exec($this->curl);
		$this->httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
		curl_close($this->curl);
		return $res;
	}

	public function info ()
	{
		return $this->httpCode;
	}


}