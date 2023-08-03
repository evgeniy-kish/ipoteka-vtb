<?php

namespace EvgeniyKish\IpotekaVtb;

class Request{

	/**
	 * Request constructor.
	 * @param string $url
	 * @param array $fields
	 * @param array $header
	 */


	protected $curl = null;
	protected $httpCode = null;


	public function __construct(protected string $url = '', protected $fields = null, protected array $header = [], protected string $typeRequest = 'POST'){
		$this->curl = curl_init();

		curl_setopt_array($this->curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $typeRequest,
			CURLOPT_POSTFIELDS => $fields,
			CURLOPT_HTTPHEADER => $this->header
		]);

	}

	public function exec()
	{
		$res = curl_exec($this->curl);
		$this->httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE );
		curl_close($this->curl);
		return $res;
	}

	public function info ()
	{
		return $this->httpCode;
	}


}