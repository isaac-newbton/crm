<?php

namespace App\Service;

use App\Entity\Lead;
use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;

class OrganizationLeadService{
	/**
	 * @var EmailService|null
	 */
	protected $emailService;

	/**
	 * @var Environment|null
	 */
	protected $twig;

	public function __construct(EmailService $emailService, Environment $twig){
		$this->emailService = $emailService;
		$this->twig = $twig;
	}

	public function createLeadFromArray(Organization $organization, array $fields, EntityManagerInterface $entityManager){
		if(empty($fields)) return false;
		$lead = new Lead();
		$capitalizedFields = [];
		foreach($fields as $key=>$value){
			$capitalizedKey = ucwords(trim(str_replace('_', ' ', $key)));
			$capitalizedFields[$capitalizedKey] = $value;
		}
		$lead->setFields($capitalizedFields);
		$organization->addLead($lead);
		$entityManager->persist($lead);
		$entityManager->persist($organization);
		$entityManager->flush();
		$this->sendNewLeadNotifications($lead);
		return $lead;
	}

	public function contactEmailsForOrganization(Organization $organization){
		$contacts = $organization->getContacts();
		$emails = [];
		foreach($contacts as $contact){
			if(filter_var($contact->getEmail(), FILTER_VALIDATE_EMAIL) && $contact->getNotifyViaEmail()){
				$emails[] = $contact->getEmail();
			}
		}
		return $emails;
	}

	public function contactMobileNumbersForOrganization(Organization $organization){
		$contacts = $organization->getContacts();
		$numbers = [];
		foreach($contacts as $contact){
			if($contact->getMobilePhone() && $contact->getNotifyViaMobile()){
				$numbers[] = $contact->getMobilePhone();
			}
		}
		return $numbers;
	}

	public function sendNewLeadNotifications(Lead $lead){
		$emails = $this->contactEmailsForOrganization($lead->getOrganization());
		$mobileNumbers = $this->contactMobileNumbersForOrganization($lead->getOrganization());
		if(!empty($emails)){
			$leadFields = $lead->getFields();
			$from = "{$_ENV['MAILGUN_DEFAULT_EMAIL_USER']}@{$_ENV['MAILGUN_DOMAIN']}";
			$emailTitle = 'New' . ((isset($leadFields['_lead_source']) && 'retreaver'===$leadFields['_lead_source']) ? ' Phone' : '') . " Lead from {$_ENV['APP_NAME']}";
			if(filter_var($from, FILTER_VALIDATE_EMAIL)){
				foreach($emails as $email){
					if(filter_var($email, FILTER_VALIDATE_EMAIL)){
						$this->emailService->sendSingleHtml($email, $emailTitle, $this->twig->render('email/notification/new_lead.html.twig', [
							'title'=>$emailTitle,
							'lead'=>$lead
						]), $from);
					}
				}
			}
		}
		if(!empty($mobileNumbers)){
			foreach($mobileNumbers as $mobileNumber){
				#TODO send a text message to {mobileNumber} that gives smae info as email/notification/new_lead.html.twig
			}
		}
		return true;
	}
}