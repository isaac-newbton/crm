<?php

namespace App\Controller;

use App\Repository\FacebookLeadgenRepository;
use App\Repository\OrganizationRepository;
use App\Service\FacebookService;
use Facebook\Exceptions\FacebookAuthenticationException;
use Facebook\Exceptions\FacebookAuthorizationException;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookServerException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FacebookController extends AbstractController{
	#https://developers.facebook.com/docs/php/howto/example_facebook_login
	/**
	 * @Route("/facebook/login", name="facebook_login")
	 */
	public function login(FacebookService $fbService){
		$facebook = $fbService->getFacebook();
		$helper = $facebook->getRedirectLoginHelper();
		$permissions = ['manage_pages', 'leads_retrieval'];
		$callbackUrl = htmlspecialchars($this->generateUrl('facebook_login_callback', [], UrlGeneratorInterface::ABSOLUTE_URL));
		$loginUrl = $helper->getLoginUrl($callbackUrl, $permissions);

		try{
			if($accountsResponse = $fbService->getAccounts()) $accounts = $accountsResponse->getDecodedBody();
		}catch(FacebookSDKException $e){

		}

		return $this->render('admin/facebook/login.html.twig', [
			'loginUrl'=>$loginUrl,
			'fbError'=>isset($e) ? $e->getMessage() : false,
			'accounts'=>isset($accounts) ? $accounts['data'] : false
		]);
	}

	/**
	 * @Route("/facebook/login_callback", name="facebook_login_callback")
	 */
	public function loginCallback(FacebookService $fbService){
		$facebook = $fbService->getFacebook();
		$helper = $facebook->getRedirectLoginHelper();

		try{
			$accessToken = $helper->getAccessToken();
		}catch(FacebookResponseException $e){
			echo "Graph Error: " . $e->getMessage();
			exit;
		}catch(FacebookSDKException $e){
			echo "SDK Error: " . $e->getMessage();
			exit;
		}

		if(!isset($accessToken)){
			if($helper->getError()){
				header('HTTP/1.0 401 Unauthorized');
				echo "Error: " . $helper->getError() . "\n";
				echo "Error Code: " . $helper->getErrorCode() . "\n";
				echo "Error Reason: " . $helper->getErrorReason() . "\n";
				echo "Error Description: " . $helper->getErrorDescription() . "\n";
			}else{
				header('HTTP/1.0 400 Bad Request');
				echo 'Bad request';
			}
			exit;
		}

		$accessTokenValue = $accessToken->getValue();
		$oAuth2Client = $facebook->getOAuth2Client();
		$tokenMetadata = $oAuth2Client->debugToken($accessToken);

		$tokenMetadata->validateAppId($_ENV['FB_APP_ID']);
		$tokenMetadata->validateExpiration();

		if(!$accessToken->isLongLived()){
			try{
				$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
			}catch(FacebookSDKException $e){
				echo "Error getting long-lived token: " . $e->getMessage();
				exit;
			}
		}

		$_SESSION['fb_access_token'] = (string)$accessToken;
		$fbService->setAccessToken((string)$accessToken);

		return $this->redirectToRoute('facebook_login');
	}

	/**
	 * @Route("/facebook/leadgen", name="facebook_leadgen_list")
	 */
	public function leadgen(FacebookLeadgenRepository $leadgenRepository){
		$leadgens = $leadgenRepository->findBy(['completed'=>null], ['dt'=>'DESC']);
		return $this->render('admin/facebook/leadgen/list.html.twig', [
			'leadgens'=>$leadgens
		]);
	}

	/**
	 * @Route("/facebook/leadgen/{leadgenId}", name="facebook_leadgen_view")
	 */
	public function leadgenList(string $leadgenId, FacebookLeadgenRepository $leadgenRepository, OrganizationRepository $orgRepository){
		$leadgens = $leadgenRepository->findBy(['leadgenId'=>$leadgenId]);
		if(empty($leadgens)) $this->redirectToRoute('facebook_leadgen_list');
		return $this->render('admin/facebook/leadgen/view.html.twig', [
			'leadgenId'=>$leadgenId,
			'leadgens'=>$leadgens,
			'organizations'=>$orgRepository->findAll()
		]);
	}

	/**
	 * @Route("/facebook/leadgen/{leadgenId}/attempt", name="facebook_leadgen_attempt", methods={"POST"})
	 */
	public function attempt(Request $request, string $leadgenId, FacebookLeadgenRepository $leadgenRepository, OrganizationRepository $orgRepository, FacebookService $fbService){
		$leadgens = $leadgenRepository->findBy(['leadgenId'=>$leadgenId]);
		if(empty($leadgens)) $this->redirectToRoute('facebook_leadgen_list');
		$organization = $orgRepository->findOneByEncodedUuid($request->request->get('organization'));
		$entityManager = $this->getDoctrine()->getManager();
		$results = [];
		foreach($leadgens as $leadgen){
			$leadgen->setOrganization($organization);
			$leadgen->setFacebookPage($organization->getFacebookPage());
			$entityManager->persist($leadgen);
			$entityManager->flush();
			$results[] = $fbService->attemptLeadgenLead($leadgen, $entityManager, $orgRepository);
		}
		return $this->render('admin/facebook/leadgen/attempt.html.twig', [
			'leadgenId'=>$leadgenId,
			'leadgens'=>$leadgens,
			'results'=>$results
		]);
	}
}