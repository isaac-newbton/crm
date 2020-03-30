<?php

namespace App\Service;

use App\Entity\Organization;

class OrganizationApiService{

	public function keyIsValid(Organization $organization, string $key){
		$organizationApis = $organization->getOrganizationApis();
		if(!empty($organizationApis)){
			foreach($organizationApis as $api){
				if($key===$api->getApiKey) return true;
			}
		}
		return false;
	}

}