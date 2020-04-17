<?php

namespace App\Controller;

use App\Doctrine\UuidEncoder;
use App\Entity\Organization;
use App\Entity\OrganizationApi;
use App\Repository\OrganizationApiRepository;
use App\Repository\OrganizationRepository;
use App\Service\OrganizationApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrganizationApiController extends AbstractController
{
	/**
	 * @Route("/admin/organization/{encodedUuid}/api", name="organization_api_list")
	 */
	public function list(string $encodedUuid, OrganizationRepository $orgRepository)
	{
		$organization = $orgRepository->findOneByEncodedUuid($encodedUuid);
		if (!$organization) return $this->redirectToRoute('organization_home');
		if (0 == count($organization->getOrganizationApis())) return $this->redirectToRoute('organization_create_api', ['encodedUuid' => $encodedUuid]);
		return $this->render(
			'admin/organization/api/list.html.twig',
			[
				'organization' => $organization
			]
		);
	}

	/**
	 * @Route("/admin/organization/{encodedUuid}/api/create", name="organization_create_api")
	 */
	public function create(Request $request, string $encodedUuid, OrganizationRepository $orgRepository, UuidEncoder $encoder, OrganizationApiService $apiService)
	{
		/**
		 * @var Organization|null
		 */
		$organization = $orgRepository->findOneByEncodedUuid($encodedUuid);
		if (!$organization) return $this->redirectToRoute('organization_home');
		$manager = $this->getDoctrine()->getManager();
		$orgApi = $apiService->generate($organization, $manager, $encoder);
		$name = $request->request->get('name');
		if (null != $name) {
			$orgApi->setName($name);
			$manager->persist($orgApi);
			$manager->flush();
		}
		return $this->redirectToRoute('organization_api_list', ['encodedUuid' => $encodedUuid]);
	}

	/**
	 * @Route("/admin/organization/{encodedUuid}/api/delete/{apiKey}", name="organization_delete_api")
	 */
	public function delete(string $encodedUuid, string $apiKey, OrganizationRepository $orgRepository, OrganizationApiService $apiService)
	{
		/**
		 * @var Organization|null
		 */
		$organization = $orgRepository->findOneByEncodedUuid($encodedUuid);
		if (!$organization) return $this->redirectToRoute('organization_home');
		if ($orgApi = $apiService->keyIsValid($organization, $apiKey)) {
			$manager = $this->getDoctrine()->getManager();
			$manager->remove($orgApi);
			$manager->flush();
		}
		return $this->redirectToRoute('organization_api_list', ['encodedUuid' => $encodedUuid]);
	}
}
