<?php

namespace App\Service;

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\Lead as FbLead;
use Symfony\Component\Filesystem\Filesystem;

class FacebookService{

	protected $appId = '';
	protected $appSecret = '';
	protected $accessTokenPath = '';
	protected $accessToken = '';

	/**
	 * @var Filesystem|null
	 */
	protected $filesystem;

	public function __construct(){
		$this->appId = $_ENV['FB_APP_ID'];
		$this->appSecret = $_ENV['FB_APP_SECRET'];
		$this->accessTokenPath = $_ENV['FB_ACCESS_TOKEN_PATH'];
		$this->filesystem = new Filesystem();
		if(!$this->filesystem->exists($this->accessTokenPath)){
			$this->filesystem->dumpFile($this->accessTokenPath, 'ACCESS_TOKEN_MISSING');
		}
		$this->accessToken = file_get_contents($this->accessTokenPath);
	}

	public function getAccessTokenPath(){
		return $this->accessTokenPath;
	}

	public function getAccessToken(){
		return $this->accessToken;
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