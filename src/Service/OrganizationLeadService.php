<?php

namespace App\Service;

use App\Entity\Lead;
use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;

class OrganizationLeadService{

	public function createLeadFromArray(Organization $organization, array $fields, EntityManagerInterface $entityManager){
		if(empty($fields)) return false;
		$lead = new Lead();
		$lead->setFields($fields);
		$organization->addLead($lead);
		$entityManager->persist($lead);
		$entityManager->persist($organization);
		$entityManager->flush();
		return $lead;
	}
}