<?php

namespace App\Service;

use App\Entity\FacebookLeadgen;
use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use FacebookAds\Api;
use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Http\Exception\ServerException;
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

	/**
	 * @var Facebook|null
	 */
	protected $facebook;

	public function __construct(){
		$this->appId = $_ENV['FB_APP_ID'];
		$this->appSecret = $_ENV['FB_APP_SECRET'];
		$this->accessTokenPath = $_ENV['FB_ACCESS_TOKEN_PATH'];
		$this->filesystem = new Filesystem();
		if(!$this->filesystem->exists($this->accessTokenPath)){
			$this->filesystem->dumpFile($this->accessTokenPath, 'ACCESS_TOKEN_MISSING');
		}
		$this->accessToken = file_get_contents($this->accessTokenPath);
		$this->facebook = new Facebook([
			'app_id'=>$_ENV['FB_APP_ID'],
			'app_secret'=>$_ENV['FB_APP_SECRET'],
			'default_graph_version'=>'v'.$_ENV['FB_GRAPH_VERSION']
		]);
	}

	public function getFacebook(){
		return $this->facebook;
	}

	public function getAccounts(){
		try{
			return $this->facebook->get('/me/accounts?limit=600', $this->accessToken);
		}catch(FacebookResponseException $e){
			echo "Graph Error: " . $e->getMessage();
		}catch(FacebookSDKException $e){
			echo "SDK Error: " . $e->getMessage();
		}
		return null;
	}

	public function getAccessTokenPath(){
		return $this->accessTokenPath;
	}

	public function getAccessToken(){
		if($this->filesystem->exists($this->accessTokenPath)){
			$this->accessToken = file_get_contents($this->accessTokenPath);
		}
		return $this->accessToken;
	}

	public function setAccessToken(string $accessToken){
		$this->accessToken = $accessToken;
		$this->filesystem->dumpFile($this->accessTokenPath, $accessToken);
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

	public function subscribeToLeadgen(Organization $organization){
		if($organization->getFacebookPage()){
			if($organization->getFacebookPageAccessToken()){
				return $this->facebook->post(
					"/{$organization->getFacebookPage()}/subscribed_apps",
					[
						'subscribed_fields'=>'leadgen'
					],
					$organization->getFacebookPageAccessToken()
				);
			}
		}
		return false;
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
				try{
					$data = $fbLead->getSelf($fields, $params)->exportAllData();
					$json = json_encode($data, JSON_PRETTY_PRINT);
				}catch(AuthorizationException $e){
					$json = $e->getMessage();
				}catch(ServerException $e){
					$json = $e->getMessage();
				}
			}
			$entityManager->persist($fbLeadgen);
			$entityManager->flush();
		}
		if(isset($json)){
			$this->filesystem->dumpFile('facebook/leadgen_data.txt', $json);
		}else{
			$this->filesystem->dumpFile('facebook/leadgen_data_fail.txt', json_encode($fbLeadgen));
		}
		return $json ?? false;
	}
}