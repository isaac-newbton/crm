<?php
namespace App\Controller;

use App\Doctrine\UuidEncoder;
use App\Entity\Organization;
use App\Entity\OrganizationContact;
use App\Repository\OrganizationContactRepository;
use App\Repository\OrganizationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController{
	/**
	 * @Route("/admin/organization/{encodedUuid}/contact", name="organization_contact_list")
	 */
	public function list(string $encodedUuid, OrganizationRepository $orgRepository){
		$organization = $orgRepository->findOneByEncodedUuid($encodedUuid);
		if(!$organization) return $this->redirectToRoute('organization_home');
		if(0==count($organization->getContacts())) return $this->redirectToRoute('organization_add_contact', ['encodedUuid'=>$encodedUuid]);
		return $this->render(
			'admin/organization/contact/list.html.twig',
			[
				'organization'=>$organization
			]
		);
	}

	/**
	 * @Route("/admin/organization/{encodedUuid}/contact/add", name="organization_add_contact")
	 */
	public function add(string $encodedUuid, OrganizationRepository $orgRepository){
		$organization = $orgRepository->findOneByEncodedUuid($encodedUuid);
		if(!$organization) return $this->redirectToRoute('organization_home');
		return $this->render(
			'admin/organization/contact/add.html.twig',
			[
				'organization'=>$organization
			]
		);
	}

	/**
	 * @Route("/admin/organization/contact/delete/{encodedUuid}", name="organization_delete_contact")
	 */
	public function delete(string $encodedUuid, OrganizationContactRepository $contactRepository, UuidEncoder $encoder){
		if($contact = $contactRepository->findOneByEncodedUuid($encodedUuid)){
			$organization = $contact->getOrganization();
			$manager = $this->getDoctrine()->getManager();
			$manager->remove($contact);
			$manager->flush();
		}
		return $this->redirectToRoute('organization_contact_list', ['encodedUuid'=>isset($organization) ? $encoder->encode($organization->getUuid()) : '0']);
	}

	/**
	 * @Route("/admin/organization/{encodedUuid}/contact/create", name="organization_create_contact")
	 */
	public function create(Request $request, string $encodedUuid, OrganizationRepository $orgRepository, UuidEncoder $encoder){
		/**
		 * @var Organization|null
		 */
		$organization = $orgRepository->findOneByEncodedUuid($encodedUuid);
		if(!$organization) return $this->redirectToRoute('organization_home');
		$name = $request->request->get('name');
		$title = $request->request->get('title');
		$email = $request->request->get('email');
		$mobile = $request->request->get('mobile');
		$work = $request->request->get('work');
		$home = $request->request->get('home');
		$primary = $request->request->get('primary', false);
		$notify_email = $request->request->get('notify_email', false);
		$notify_mobile = $request->request->get('notify_mobile', false);
		if(!empty($name) && !empty($email)){
			$contact = new OrganizationContact();
			$contact->setName($name);
			$contact->setJobTitle($title);
			$contact->setEmail($email);
			$contact->setMobilePhone($mobile);
			$contact->setWorkPhone($work);
			$contact->setHomePhone($home);
			$contact->setIsPrimary($primary);
			$contact->setNotifyViaEmail($notify_email);
			$contact->setNotifyViaMobile($notify_mobile);
			$organization->addContact($contact);
			$manager = $this->getDoctrine()->getManager();
			$manager->persist($organization);
			$manager->persist($contact);
			$manager->flush();
		}
		return $this->redirectToRoute('organization_contact_list', ['encodedUuid'=>isset($organization) ? $encoder->encode($organization->getUuid()) : '0']);
	}
}