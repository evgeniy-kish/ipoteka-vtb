<?php


namespace EvgeniyKish\IpotekaVtb;

use EvgeniyKish\IpotekaVtb\interfaceBank;

class Bank implements interfaceBank
{

	protected string $token;

	public function __construct(protected string $client_id = '', protected string $client_secret = ''){

	}

	public function generationToken(Request $request): string{

		return $request->exec();

	}


	public function getToken(){

		return $token;

	}


//	public function createFullQuestionnaires($params): string;
//
//	public function updateFullQuestionnaires($params): string;
//
//	// Передача партнером метаданные по файлу в Банк
//	public function transfer($params): array;
//
//	// Передача партнером метаданные по файлу в Банк
//	public function transfer($params): array;

}