<?php

namespace App\Controller;

use App\Doctrine\UuidEncoder;
use App\Entity\LeadRating;
use App\Entity\Organization;
use App\Form\LeadType;
use App\Repository\OrganizationRepository;
use App\Service\FacebookService;
use Facebook\Exceptions\FacebookSDKException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrganizationController extends AbstractController
{
	/**
	 * @Route("/admin/organization", name="organization_home")
	 */
	public function home(OrganizationRepository $orgRepository)
	{
		$organizations = $orgRepository->findBy([], ['name' => 'ASC']);
		if (!$organizations) return $this->redirectToRoute('organization_add');
		return $this->render(
			'admin/organization/home.html.twig',
			[
				'organizations' => $organizations
			]
		);
	}

	/**
	 * @Route("/admin/organization/add", name="organization_add")
	 */
	public function add()
	{
		return $this->render(
			'admin/organization/add.html.twig'
		);
	}

	/**
	 * @Route("/admin/organization/edit/{encodedUuid}", name="organization_edit")
	 */
	public function edit(string $encodedUuid, OrganizationRepository $orgRepository)
	{
		$organization = $orgRepository->findOneByEncodedUuid($encodedUuid);
		if (!$organization) return $this->redirectToRoute('organization_home');
		return $this->render(
			'admin/organization/edit.html.twig',
			[
				'organization' => $organization
			]
		);
	}

	/**
	 * @Route("/admin/organization/delete/{encodedUuid}", name="organization_delete")
	 */
	public function delete(string $encodedUuid, OrganizationRepository $orgRepository)
	{
		if ($organization = $orgRepository->findOneByEncodedUuid($encodedUuid)) {
			$manager = $this->getDoctrine()->getManager();
			$manager->remove($organization);
			$manager->flush();
		}
		return $this->redirectToRoute('organization_home', ['_fragment' => $organization ? $organization->getId() : '0']);
	}

	/**
	 * @Route("/admin/organization/create", name="organization_create")
	 */
	public function create(Request $request)
	{
		$name = $request->request->get('name');
		$facebookPage = $request->request->get('facebookPage');
		if (!empty($name)) {
			$organization = new Organization();
			$organization->setName($name);
			$organization->setFacebookPage($facebookPage);
			$manager = $this->getDoctrine()->getManager();
			$manager->persist($organization);
			$manager->flush();
		}
		return $this->redirectToRoute('organization_home');
	}

	/**
	 * @Route("/admin/organization/update/{encodedUuid}", name="organization_update")
	 */
	public function update(Request $request, string $encodedUuid, OrganizationRepository $orgRepository)
	{
		if ($organization = $orgRepository->findOneByEncodedUuid($encodedUuid)) {
			$name = $request->request->get('name');
			if (!empty($name)) {
				$organization->setName($name);
				$manager = $this->getDoctrine()->getManager();
				$manager->persist($organization);
				$manager->flush();
			}
		}
		return $this->redirectToRoute('organization_home', ['_fragment' => $organization ? $organization->getId() : '0']);
	}

	/**
	 * @Route("/admin/organization/{encodedUuid}/leads", name="organization_leads")
	 * ?todo : restrect by user organization
	 */
	public function organizationLeads(string $encodedUuid, OrganizationRepository $organizationRepository)
	{
		if ($organization = $organizationRepository->findOneByEncodedUuid($encodedUuid)) {


			$leadRatings = $this->getDoctrine()->getRepository(LeadRating::class)->findAll();
			return $this->render('admin/organization/leads.html.twig', [
				'organization' => $organization,
				'leadRatings' => $leadRatings,
			]);
		}
	}

	/**
	 * @Route("/admin/organization/{encodedUuid}/facebook", name="organization_facebook")
	 */
	public function facebook(string $encodedUuid, OrganizationRepository $orgRepository, FacebookService $fbService)
	{
		$organization = $orgRepository->findOneByEncodedUuid($encodedUuid);
		if (!$organization) return $this->redirectToRoute('organization_home');

		if ($accountsResponse = $fbService->getAccounts()) {
			$accounts = $accountsResponse->getDecodedBody();
			usort($accounts['data'], function ($a, $b) {
				if ($a['name'] === $b['name']) return 0;
				return ($a['name'] < $b['name']) ? -1 : 1;
			});
		}

		return $this->render(
			'admin/organization/facebook.html.twig',
			[
				'organization' => $organization,
				'globalAccessToken' => $fbService->getAccessToken(),
				'accounts' => isset($accounts) ? $accounts['data'] : false
			]
		);
	}

	/**
	 * @Route("/admin/organization/{encodedUuid}/update/facebook", name="organization_update_facebook")
	 */
	public function updateFacebook(Request $request, string $encodedUuid, OrganizationRepository $orgRepository, FacebookService $fbService)
	{
		if ($organization = $orgRepository->findOneByEncodedUuid($encodedUuid)) {
			$facebook = explode(',', $request->request->get('facebook'));
			if (2 == count($facebook) && '' != trim($facebook[0]) && '' != trim($facebook[1])) {
				$fbPage = $facebook[0];
				$fbAccessToken = $facebook[1];
			} else {
				$fbPage = null;
				$fbAccessToken = null;
			}
			$organization->setFacebookPage($fbPage);
			$organization->setFacebookPageAccessToken($fbAccessToken);
			$manager = $this->getDoctrine()->getManager();
			$manager->persist($organization);
			$manager->flush();
		}
		return $this->redirectToRoute('organization_home', [
			'_fragment' => $organization ? $organization->getId() : '0',
			'leadgen' => ($organization && $fbService->subscribeToLeadgen($organization)) ? 'subscribed' : 'not_subscribed'
		]);
	}
}
