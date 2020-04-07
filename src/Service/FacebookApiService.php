<?php

namespace App\Service;

use FacebookAds\Object\User;
use FacebookAds\Object\Page;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;

class FacebookApiService
{

	/**
	 * Fetch a lead from facebook by lead id
	 */
	public function fetchLeadById(string $id)
	{
		$access_token = '<ACCESS_TOKEN>';
		$app_secret = '<APP_SECRET>';
		$app_id = '<APP_ID>';
		// $id = '<ID>';

		$api = Api::init($app_id, $app_secret, $access_token);
		$api->setLogger(new CurlLogger());

		$fields = array();
		$params = array();
		echo json_encode((new User($id))->getAccounts(
			$fields,
			$params
		)->getResponse()->getContent(), JSON_PRETTY_PRINT);
	}
}
