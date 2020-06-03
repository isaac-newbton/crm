<?php

namespace App\Controller;

use App\Doctrine\UuidEncoder;
use App\Entity\Lead;
use App\Entity\LeadRating;
use App\Repository\LeadRatingRepository;
use App\Repository\LeadRepository;
use App\Repository\OrganizationRepository;
use App\Service\EmailService;
use App\Service\FacebookService;
use App\Service\OrganizationLeadService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SuperAdminController extends AbstractController
{

	/**
	 * @Route("/super/email", name="super_email")
	 */
	public function emailTest(Request $request, EmailService $emailService)
	{

		$sent = false;

		if ($request->isMethod('post')) {
			$from = $request->request->get('from') . "@{$_ENV['MAILGUN_DOMAIN']}";
			$to = $request->request->get('to');
			if (filter_var($from, FILTER_VALIDATE_EMAIL) && filter_var($to, FILTER_VALIDATE_EMAIL)) {
				$title = "Test Email From {$_ENV['MAILGUN_DOMAIN']}";
				$message = $this->renderView('email/base.html.twig', [
					'title' => $title
				]);
				$sent = $emailService->sendSingleHtml($to, $title, $message, $from);
			}
		}

		return $this->render('superadmin/email_test.html.twig', [
			'sent' => $sent ? var_export($sent, true) : false
		]);
	}

	/**
	 * @Route("/super/resend/{encodedLeadUuid}", name="resend_notification")
	 */
	public function resendNotification(string $encodedLeadUuid, LeadRepository $leadRepository, UuidEncoder $encoder, OrganizationLeadService $orgLeadService)
	{
		/**
		 * @var Lead|null
		 */
		$lead = $leadRepository->findOneByEncodedUuid($encodedLeadUuid);
		if ($lead) {
			$orgLeadService->sendNewLeadNotifications($lead);
			return $this->redirectToRoute('organization_leads', ['encodedUuid' => $encoder->encode($lead->getOrganization()->getUuid())]);
		}
		return $this->redirectToRoute('super_email');
	}

	/**
	 * @Route("/super/facebook_access", name="super_facebook_access")
	 */
	public function fbAccess(FacebookService $fbService)
	{
		return new Response("<html><body>Path = " . $fbService->getAccessTokenPath() . "<br>Token = " . $fbService->getAccessToken() . "</body></html>", 200);
	}

	/**
	 * @Route("/super/lead_ratings", name="super_lead_ratings")
	 */
	public function updateLeadRatings(Request $request)
	{
		$entityManager = $this->getDoctrine()->getManager(); // will probably always need this so we instanciate

		// handle delete
		if ($request->isMethod('post') && $id = $request->request->get('id')) {
			$entityManager->remove($entityManager->getRepository(LeadRating::class)->find($id));
			$entityManager->flush();
			return $this->redirect($request->getUri()); // this clears the form field after submission
		}

		// create the add form
		$leadRating = new LeadRating();
		$addForm = $this->createFormBuilder($leadRating)
			->add('label', TextType::class, ['label' => 'Rating Label'])
			->add('submit', SubmitType::class)
			->getForm();

		$addForm->handleRequest($request);
		if ($addForm->isSubmitted() && $addForm->isValid()) {
			$leadRating = $addForm->getData();
			$entityManager->persist($leadRating);
			$entityManager->flush();

			return $this->redirect($request->getUri()); // this clears the form field after submission
		}

		$leadRatings = $this->getDoctrine()->getRepository(LeadRating::class)->findAll();
		return $this->render("superadmin/lead_ratings.html.twig", [
			"leadRatings" => $leadRatings,
			"addForm" => $addForm->createView(),
		]);
	}

	/**
	 * @route("/super/{encodedLeadUuid}/internal_rating/update", name="update_internal_lead_rating", methods={"POST"})
	 * ! this is a temporary implementation and should be removed in a future update!
	 */
	public function updateInternalLeadRating(
		Request $request,
		string $encodedLeadUuid,
		LoggerInterface $loggerInterface,
		LeadRepository $leadRepository,
		LeadRatingRepository $leadRatingRepository,
		OrganizationRepository $organizationRepository,
		UuidEncoder $encoder
	) {

		$loggerInterface->warning('route:update_internal_lead_rating will be removed in future updates - be sure to adjust accordingly!');

		$lead = $leadRepository->findOneByEncodedUuid($encodedLeadUuid);
		$leadRating = $leadRatingRepository->find($request->request->get('leadRating'));
		if ($lead && $leadRating) {
			$lead->setInternalRating($leadRating);

			$entityManager = $this->getDoctrine()->getManager();
			$entityManager->persist($lead);
			$entityManager->flush();
		}
		return $this->redirectToRoute('organization_leads', ["encodedUuid" => $encoder->encode($lead->getOrganization()->getUuid())]);
	}
}
