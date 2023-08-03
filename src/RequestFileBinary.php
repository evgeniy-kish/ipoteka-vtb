<?php

namespace EvgeniyKish\IpotekaVtb;

class RequestFileBinary{

	/**
	 * Request constructor.
	 * @param string $url
	 * @param array $fields
	 * @param array $header
	 */


	protected $curl = null;
	protected $httpCode = null;


	public function __construct(protected string $url = '', protected $file = null, protected array $header = [], protected string $typeRequest = 'POST'){
		$this->curl = curl_init();

	    curl_setopt_array($this->curl, [
		    CURLOPT_URL => $url,
		    CURLOPT_PUT => true,
		    CURLOPT_CUSTOMREQUEST => $typeRequest,
		    CURLOPT_INFILESIZE => $file['size'],
		    CURLOPT_INFILE => $file['binary'],
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_SSL_VERIFYPEER => false,
		    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		    CURLOPT_HTTPHEADER => $header,
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