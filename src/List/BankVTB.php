<?php

namespace EvgeniyKish\IpotekaVtb\list;

use app\models\FilesData;
use EvgeniyKish\IpotekaVtb\Bank;
use EvgeniyKish\IpotekaVtb\Request;
use EvgeniyKish\IpotekaVtb\RequestFileBinary;

class BankVTB extends Bank
{

	private string $urlToken = 'https://epa-ift.vtb.ru:443/passport/oauth2/token';
	private string $urlOpenApi = 'https://test.api.vtb.ru:8443/';
	private string $partnerOrgId = 'fdab0f15-3421-404f-8367-6c093b109ae4';
	private array $field;
	public array $errors = [];
	public array $request = [];
	public string $questionnaireId;
	protected string $requestId;
	protected string $attachmentId;
	protected string $contentURL;
	protected string $typeDocument;

	public int $count = 0;

	public function __construct(protected string $client_id = '', string $client_secret = ''){

		$this->questionnaireId = md5(time());

		$this->field = [
			'grant_type' => 'client_credentials',
			'client_id' => $client_id.'%40ext.vtb.ru',
			'client_secret' => $client_secret,
		];

	}

	// Create Token
	public function getToken(){

		$field = urldecode(http_build_query($this->field, '', '&'));
		$this->token = $this->generationToken(new Request($this->urlToken, $field, ['Content-Type: application/x-www-form-urlencoded', 'Cookie: amlbcookie=01']));
		return json_decode($this->token, JSON_OBJECT_AS_ARRAY)['access_token'] ?? '';

	}

	// Create simple order
	public function createOrUpdateQuestionnaires($questionnaireId = null, $info){

		$this->questionnaireId = $questionnaireId ?? md5(time());


		$url = $this->urlOpenApi.'openapi/rb/IPO/partnerAPI/v1/'.$this->partnerOrgId.'/questionnaires/'.$this->questionnaireId;

		$info['phone'] = preg_replace('/[^0-9]/', '', $info['phone']);

		$phone = substr($info['phone'],1);

		$fields = [
			"clientEmail" => $info['email'],
			"clientFirstName" => explode(' ', $info['name'])[0],
			"clientLastName" => explode(' ', $info['name'])[1],
			"clientMiddleNames" => explode(' ', $info['name'])[2],
			"clientPhone" => $phone,
			"productGroupId" => "cm.LoanProductGroup.BuyingProperty",
			"requestType" => "tsc.RequestType.Lead",
			"requestedAmount" => $info['credit'],
			"comment" => $info['comment'] ?? '',
			"supportingData" => [
				"masterCikId" => "003"
			],
			"test" => true,
		];

		$header = [
			'X-IBM-Client-Id: '.$this->client_id,
			'Content-Type: application/json',
			'Accept-Encoding: deflate, br',
			'Authorization: Bearer '.$this->getToken()
		];

		$request = new Request($url, json_encode($fields), $header, 'PUT');
		$res = $request->exec();

		$this->request['createOrUpdateQuestionnaires']['request'][0] = $request;
		$this->request['createOrUpdateQuestionnaires']['result'][0] = json_decode($res, JSON_OBJECT_AS_ARRAY);


		if ($request->info() == 200 && !array_key_exists('error', json_decode($res, JSON_OBJECT_AS_ARRAY))){
			return $this->questionnaireId;
		}else{
			$this->errors['createOrUpdateQuestionnaires'][] = json_decode($res, JSON_OBJECT_AS_ARRAY);
			return false;
		}
	}

	// Create full order
	public function createFullQuestionnaires(){

		$url = $this->urlOpenApi.'openapi/rb/IPO/partnerAPI/v1/'.$this->partnerOrgId.'/full-questionnaires/'.$this->questionnaireId;

		$fields = [
			"creditApplication" => [
				"type" => "tsc.RequestType.MultiLead",
				"division" => "003",
				"realEstateRegion" => "cm.PropertyPurchaseRegion.MoscowRegion",
				"realEstateCost" => 3000000,
				"initialPayment" => 1000000,
				"requestedLoanAmount" => 2000000,
				"requestedLoanTerm" => 360,
				"division" => "003",
				"salesChannel" => "cm.ChannelKind.EXTCRM",
				"loanProductGroup" => "cm.LoanProductGroup.BuyingProperty",
				"customerOffer" => "cm.PersonMortgageOfferType.RKK2_0",
				"personalApplicants" => [
					[
						"externalId" => "dda80d7c-3f48-4678-b83f-feec7c7eb03f",
						"surname" => "Тест",
						"name" => "Тестов",
						"patronymic" => "Тестович",
						"birthDay" => "1980-01-01",
						"role" => "cm.CustomerLoanApplicationRole.Applicant",
						"profitFlag" => true,
						"citizenCountryCode" => "cm.Country.RUS",
						"personIdentityCards" => [
							"isActual" => true,
							"docType" => "cm.PersonDocumentKind.Main",
							"identityCardCode" => "cm.DocumentType.28",
							"department" => "ГУ МВД РОССИИ ПО Г.МОСКВЕ",
							"departmentCode" => "770-094",
							"dateOfIssue" => "2019-05-21",
							"number" => "123456",
							"series" => "4520"
						],
					]
				],
				"personContacts" => [
					[
						"contactCode" => "cm.PhoneType.MobilePersonal",
						"contactValue" => "9031111111"
					],
					[
						"contactCode" => "cm.EmailType.Personal",
						"contactValue" => "test@test.ru"
					]
				],
			],
			[
				"role" => "cm.CustomerLoanApplicationRole.Partner"

			]
		];

		$header = [
			'X-IBM-Client-Id: '.$this->client_id,
			'Content-Type: application/json',
			'Accept-Encoding: deflate, br',
			'Authorization: Bearer '.$this->getToken()
		];


		$request = new Request($url, json_encode($fields), $header, 'PUT');
		$res = $request->exec();

		$request['request'][] = $request;
		$request['result'][] = json_decode($res, JSON_OBJECT_AS_ARRAY);

		if ($request->info() == 200){
			return $this->questionnaireId;
		} else {
			return json_decode($request->exec(), JSON_OBJECT_AS_ARRAY);
		};

	}

	// Передать партнером метаданные по файлу в Банк - в ответ получить уникальный request_id и contentURL с прямой ссылкой в ФО
	public function transferFile($file, $typeDocument, $attachmentId){

		$this->typeDocument = $typeDocument;

		$this->attachmentId = $attachmentId;

		$url = $this->urlOpenApi.'openapi/cross/fsrv/v1/transfer_rq';

		$fields = [
			"processCode" => 19,
			"priority" => 10,
			"fileName" => basename($file),
			"fileLength" => filesize($file),
			"fileMD5" => md5_file($file)
		];

		$header = [
			'X-IBM-Client-Id: '.$this->client_id,
			'Content-Type: application/json',
			'Accept-Encoding: br, deflate, gzip',
			'Accept: application/json',
			'Authorization: Bearer '.$this->getToken()
		];

		$request = new Request($url, json_encode($fields), $header, 'POST');

		$result =  json_decode($request->exec(), JSON_OBJECT_AS_ARRAY);

		$this->request['transferFile']['request'][$this->count] = $request;
		$this->request['transferFile']['result'][$this->count] = $result;

		if ($request->info() == 200){
			$this->requestId = $result['requestId'];
			$this->contentURL = $result['contentURL'];

			if ($this->transferFileBinary($file)){
				if ($this->transferFileСonfim()){
					$this->transferFileAttachments();
				}
			} else {
				var_dump($this->transferFileBinary($file));
			}


			$this->count += 1;

		}else{
			$this->errors['transferFile'][] = $result;
		}


		return json_encode($this->errors);

	}

	// Партнером загрузить в Банк файл в формате binary по ссылке из contentURL
	public function transferFileBinary($file){

		$header = [
			'Content-Type: text/plain',
		];


		$file = [
			'size' => filesize($file),
			'binary' => fopen($file, 'rb')
		];

		$request = new RequestFileBinary($this->contentURL, $file, $header, 'PUT');

		$result =  json_decode($request->exec(), JSON_OBJECT_AS_ARRAY);


		$this->request['transferFileBinary']['request'][$this->count] = $request;
		$this->request['transferFileBinary']['result'][$this->count] = $result;

		if ($request->info() == 200){
			return true;
		}else{
			$this->errors['transferFileBinary'][] = $result;
			return false;
		}

	}

	// Партнером высвободить ресурсы и сообщить об успешной загрузке файла
	public function transferFileСonfim(){
		$url = $this->urlOpenApi.'openapi/cross/fsrv/v1/transfer_rq/'.$this->requestId.'/confirm_upload';

		$header = [
			'X-IBM-Client-Id: '.$this->client_id,
			'Content-Type: application/json',
			'Accept-Encoding: br, deflate',
			'Accept: application/json',
			'Authorization: Bearer '.$this->getToken()
		];

		$request = new Request($url, null, $header, 'PUT');

		$result = json_decode($request->exec(), JSON_OBJECT_AS_ARRAY);

		$this->request['transferFileConfim']['request'][$this->count] = $request;
		$this->request['transferFileConfim']['result'][$this->count] = $result;
		if ($request->info() == 201){
			return true;
		}else{
			$this->errors['transferFileСonfim'][] = $result;
			return false;
		}
	}

	// Партнером в ЛКП прислать метаданные  по загруженному файлу
	public function transferFileAttachments(){
		$url = $this->urlOpenApi.'openapi/rb/IPO/partnerAPI/v1/'.$this->partnerOrgId.'/questionnaires/'.$this->questionnaireId.'/attachments/'.$this->attachmentId;

		$header = [
			'X-IBM-Client-Id: '.$this->client_id,
			'Content-Type: application/json',
			'Accept-Encoding: br, deflate',
			'Accept: application/json',
			'Connection: Keep-Alive',
			'Authorization: Bearer '.$this->getToken()
		];


		$fields = [
			'requestId' => $this->requestId,
			'attachmentType' => $this->typeDocument,
		];

		$request = new Request($url, json_encode($fields), $header, 'PUT');

		$result = json_decode($request->exec(), JSON_OBJECT_AS_ARRAY);

		$this->request['transferFileAttachments']['request'][$this->count] = $request;
		$this->request['transferFileAttachments']['result'][$this->count] = $result;

		return true;

	}

	// Отправить заявку ЛКП в работу
	public function questionnairesSubmit($questionnaireId){

		$this->questionnaireId = $questionnaireId;

		$url = $this->urlOpenApi.'openapi/rb/IPO/partnerAPI/v1/'.$this->partnerOrgId.'/questionnaires/'.$this->questionnaireId.'/submit';

		$header = [
			'X-IBM-Client-Id: '.$this->client_id,
			'Authorization: Bearer '.$this->getToken(),
			'Content-Type: application/json',
			'Connection: Keep-Alive',
			'Accept-Encoding: deflate, br'
		];

		$request = new Request($url, '{}', $header, 'POST');

		$result = json_decode($request->exec(), JSON_OBJECT_AS_ARRAY);

		$this->request['questionnairesSubmit']['request'][0] = $request;
		$this->request['questionnairesSubmit']['result'][0] = $result;

		if ($request->info() == 200){
			return json_encode($result);
		}else{
			$this->errors[] = $result;
			return false;
		}
	}

	// Получить заявку ЛКП по ID
	public function getQuestionnairesStatus($questionnaireId){

		$this->questionnaireId = $questionnaireId;

		$url = $this->urlOpenApi.'openapi/rb/IPO/partnerAPI/v1/'.$this->partnerOrgId.'/questionnaires/'.$this->questionnaireId;

		$header = [
			'X-IBM-Client-Id: '.$this->client_id,
			'Authorization: Bearer '.$this->getToken(),
			'Connection: Keep-Alive',
			'Accept-Encoding: deflate, br'
		];

		$request = new Request($url, null, $header, 'GET');

		$result = json_decode($request->exec(), JSON_OBJECT_AS_ARRAY);

		$attach = [];
		$status = '';
		$statusFile = '';

		if (isset($result['value']['attachments']) && $result['value']['attachments']){
			foreach ($result['value']['attachments'] as $attachments){

				if (isset($attachments['status'])){
					switch ($attachments['status']){
						case 'ERROR' : $statusFile = 'Файл не прошел проверку'; break;
						case 'PROCESS' : $statusFile = 'Файл в обработки'; break;
						case 'SUCCESS' : $statusFile = 'Файл прикреплен к заявке';  break;
					}
				}

				$attach[] = [
					'acceptanceResult' => $statusFile,
					'status' => $attachments['status'],
					'id' => $attachments['id'],
				];



//				if ($attachments['acceptanceResult'] != 'Success'){
//
//					$file = FilesData::find()->where(['id' => $attachments['id']])->one();
//
//					$attach[] = [
//						'acceptanceResult' => $attachments['acceptanceResult'],
//						'file' => $file->comment,
//						'id' => $attachments['id'],
//					];
//				}
			}
		}

		if (isset($result['value']['stateId'])){

			switch ($result['value']['stateId']){
				case 'cm.LoanApplicationStatus.DecisionProcessing': $status = 'Заявка на рассмотрении'; break;
				case 'cm.LoanApplicationStatus.Approved': $status = 'Кредит одобрен'; break;
				case 'tsc.AppStatus.ObjectInProgress': $status = 'Документы на рассмотрении'; break;
				case 'tsc.AppStatus.ObjectApproved': $status = 'Согласование сделки и подготовка документации'; break;
				case 'cm.LoanApplicationStatus.Issued': $status = 'Кредит выдан'; break;
				case 'tsc.AppStatus.ReworkClient': $status = 'Причина возврата на доработку указана в комментариях'; break;
				case 'tsc.AppStatus.ReworkObject': $status = 'Причина возврата на доработку указана в комментариях'; break;
				case 'cm.LoanApplicationStatus.ApplicationFilling': $status = 'Черновик'; break;
				default: $status = $result['value']['stateId'];
			}
		}


		$result['value']['attachments'] = $attach;
		$result['value']['stateSlug'] = $result['value']['stateId'] ?? '-';
		$result['value']['stateId'] = $status;

		return $result;
	}

	// Получить заявки ЛКП, изменившиеся за интервал времени
	public function getAllQuestionnairesStatus(){
		$url = $this->urlOpenApi.'openapi/rb/IPO/partnerAPI/v1/'.$this->partnerOrgId.'/questionnaires';

		$header = [
			'X-IBM-Client-Id: '.$this->client_id,
			'Authorization: Bearer '.$this->getToken(),
			'Connection: Keep-Alive',
			'Accept-Encoding: deflate, br'
		];

		$request = new Request($url, null, $header, 'GET');

		$result = json_decode($request->exec());

		return json_encode($result);
	}


}