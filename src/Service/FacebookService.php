<?php

namespace App\Service;

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\Lead as FbLead;

class FacebookService{

	protected $appId = '';
	protected $appSecret = '';
	protected $accessToken = '';

	public function __construct(){
		$this->appId = $_ENV['FB_APP_ID'];
		$this->appSecret = $_ENV['FB_APP_SECRET'];
		$this->accessToken = $_ENV['FB_ACCESS_TOKEN'];
	}

	#https://developers.facebook.com/docs/marketing-api/guides/lead-ads/retrieving#webhooks
	public function leadgenFields(string $leadgenId){
		$api = Api::init($this->appId, $this->appSecret, $this->accessToken);
		$api->setLogger(new CurlLogger());
		$fields = [];
		$params = [];
		$fbLead = new FbLead();
		return json_encode(
			$fbLead->getSelf($fields, $params)->exportAllData()
		);
	}
}