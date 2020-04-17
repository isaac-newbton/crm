<?php

namespace App\Controller;

use App\Service\FacebookService;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FacebookController extends AbstractController{
	#https://developers.facebook.com/docs/php/howto/example_facebook_login
	/**
	 * @Route("/facebook/login", name="facebook_login")
	 */
	public function login(FacebookService $fbService){
		session_start();
		$facebook = $fbService->getFacebook();
		$helper = $facebook->getRedirectLoginHelper();
		$permissions = ['manage_pages'];
		$callbackUrl = htmlspecialchars($this->generateUrl('facebook_login_callback', [], UrlGeneratorInterface::ABSOLUTE_URL));
		$loginUrl = $helper->getLoginUrl($callbackUrl, $permissions);
		return $this->render('', [
			'loginUrl'=>$loginUrl
		]);
	}

	/**
	 * @Route("/facebook/login_callback", name="facebook_login_callback")
	 */
	public function loginCallback(FacebookService $fbService){
		session_start();
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
	}
}