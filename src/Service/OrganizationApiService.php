<?php

namespace App\Service;

use App\Doctrine\UuidEncoder;
use App\Entity\Organization;
use App\Entity\OrganizationApi;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class OrganizationApiService{

	public function keyIsValid(Organization $organization, string $key){
		$organizationApis = $organization->getOrganizationApis();
		if(!empty($organizationApis)){
			foreach($organizationApis as $api){
				if($key===$api->getApiKey()) return $api;
			}
		}
		return false;
	}

	public function generate(Organization $organization, EntityManagerInterface $manager, UuidEncoder $encoder){
		do {
			$key = substr($encoder->encode(Uuid::uuid4()), 0, 23);
		} while ($this->keyIsValid($organization, $key));
		$orgApi = new OrganizationApi();
		$orgApi->setApiKey($key);
		$orgApi->setName('Key generated on ' . date('Y-m-d H:i:s'));
		$organization->addOrganizationApi($orgApi);
		$manager->persist($organization);
		$manager->persist($orgApi);
		$manager->flush();
		return $orgApi;
	}

}