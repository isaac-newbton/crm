<?php

namespace App\Service;

use App\Doctrine\UuidEncoder;
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

	/**
	 * @var UuidEncoder|null;
	 */
	protected $uuidEncoder;

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
		$this->uuidEncoder = new UuidEncoder();
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

	public function attemptLeadgenLead(FacebookLeadgen $fbLeadgen, EntityManagerInterface $entityManager, OrganizationRepository $orgRepository, OrganizationLeadService $orgLeadService){
		$result = false;
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
					if($data && !empty($data)){
						$json = json_encode($data);
						$array = json_decode($json, true);
						$fbLeadgen->setResult($array);
						if(isset($array['field_data']) && !empty($array['field_data'])){
							$fields = [];
							foreach($array['field_data'] as $fieldData){
								if(isset($fieldData['name']) && isset($fieldData['values']) && !empty($fieldData['values'])){
									$fields[$fieldData['name']] = implode(',', $fieldData['values']);
								}
							}
							$newLead = $orgLeadService->createLeadFromArray($organization, array_merge($fields, [
								'_fb_leadgen_id'=>$fbLeadgen->getLeadgenId()
							]), $entityManager);
						}
						$fbLeadgen->setCompleted(new \DateTime());
					}
				}catch(AuthorizationException $e){
					$fbLeadgen->setResult(['error'=>$e->getMessage()]);
					$result = ['error'=>$e->getMessage()];
				}catch(ServerException $e){
					$fbLeadgen->setResult(['error'=>$e->getMessage()]);
					$result = ['error'=>$e->getMessage()];
				}
			}
			$entityManager->persist($fbLeadgen);
			$entityManager->flush();
		}
		return $newLead ? ['lead'=>$this->uuidEncoder->encode($newLead->getUuid())] : $result;
	}
}