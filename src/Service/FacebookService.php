<?php

namespace App\Service;

use App\Entity\FacebookLeadgen;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Facebook\Facebook;
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

	public function getFacebook(){
		return new Facebook([
			'app_id'=>$_ENV['FB_APP_ID'],
			'app_secret'=>$_ENV['FB_APP_SECRET'],
			'default_graph_version'=>$_ENV['FB_GRAPH_VERSION']
		]);
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

	public function attemptLeadgenLead(FacebookLeadgen $fbLeadgen, EntityManagerInterface $entityManager, OrganizationRepository $orgRepository){
		if(null==$fbLeadgen->getCompleted()){
			$fbLeadgen->setAttempts($fbLeadgen->getAttempts() + 1);
			if(null==$fbLeadgen->getOrganization()){
				//attempt to match with an org via page_id
				if(is_numeric($fbLeadgen->getFacebookPage())){
					if($foundOrg = $orgRepository->findOneBy(['facebookPage'=>$fbLeadgen->getFacebookPage()])){
						$foundOrg->addFacebookLeadgen($fbLeadgen);
						$entityManager->persist($foundOrg);
					}
				}
			}
			if(null!=$organization = $fbLeadgen->getOrganization()){
				$api = Api::init($this->appId, $this->appSecret, $this->accessToken);
				$api->setLogger(new CurlLogger());
				$fields = [];
				$params = [];
				$fbLead = new FbLead($fbLeadgen->getLeadgenId());
				$data = $fbLead->getSelf($fields, $params)->exportAllData();
				$json = json_encode($data, JSON_PRETTY_PRINT);
			}
			$entityManager->persist($fbLeadgen);
			$entityManager->flush();
		}
		if(isset($json)){
			$this->filesystem->dumpFile('leadgen_data.txt', $json);
		}
		return $json ?? false;
	}
}