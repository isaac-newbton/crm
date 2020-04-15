<?php

namespace App\Controller;

use App\Doctrine\UuidEncoder;
use App\Repository\OrganizationRepository;
use App\Service\OrganizationApiService;
use App\Service\OrganizationLeadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{

    /**
     * @Route("/facebook/webhook")
     * 
     * This endpoint is a catch all from facebook
     */
    public function facebookAuth(Request $request, OrganizationLeadService $organizationLeadService, OrganizationRepository $organizationRepository, EntityManagerInterface $entityManagerInterface)
    {

        /** 
         * todo: if we are recieving a leadgen object:
         * todo: token ($token) returned from previous step stored (somewhere)
         * todo: retreive the lead details and store in database - fallback to store leadgen object in database
         * todo: notify related org contacts after saving data
         */
        $data = json_decode($request->getContent());
        if ($data->object == 'page' && $data->entry[0]->changes[0]->field == 'leadgen') {
            $leadgen = get_object_vars($data->entry[0]->changes[0]->value);
            $page_id = $leadgen['page_id'];
            // todo try and find the page in the crm from this id
        }

        $organizationLeadService->createLeadFromArray(
            $organizationRepository->findOneByEncodedUuid('3hSX7N1OSn6zyt9XgjqGvH'), // hardcded for now
            $leadgen, // fallback to storing leadgen until we can retreive lead details
            $entityManagerInterface
        );
        // }

        /**
         * todo: if we are auth:
         * todo: check if token exists (somewheere) and ping facebook to refresh
         * todo: user logins to facebook and authorizes app to manage pages
         */
        $challenge = $request->query->get('hub_challenge');
        $verify_token = $request->query->get('hub_verify_token');
        if ($verify_token === 'funnelkake-crm') {
            // error_log('request: ' . $request);
            // error_log('token matches');
            // error_log('challenge: ' . $challenge);
            return new Response($challenge);
        } else {
            // error_log('token mismatch');
            // error_log('request: ' . $request);
            return new JsonResponse('error');
        }
    }

    /**
     * @Route("/facebook/auth")
     */
    public function fbAuthorizeApp(Request $request)
    {
        return $this->render('admin/organization/facebook.auth.twig');
    }

    /**
     * @Route("/webhook/verify_connection/{orgEncodedUuid}/{orgApiKey}", name="webhook_verify_connection", methods={"GET"})
     */
    public function verify(Request $request, string $orgEncodedUuid, string $orgApiKey, OrganizationRepository $orgRepository, OrganizationApiService $orgApiService)
    {
        if (!$organization = $orgRepository->findOneByEncodedUuid($orgEncodedUuid)) {
            return new JsonResponse(['error' => 'invalid organization id'], 401);
        }
        if (!$orgApiService->keyIsValid($organization, $orgApiKey)) {
            return new JsonResponse(['error' => 'invalid key'], 401);
        }
        return new JsonResponse(['success' => true], 200);
    }

    /**
     * @Route("/webhook/facebook/new_lead/{orgEncodedUuid}/{orgApiKey}", name="webhook_facebook_new_lead")
     */
    public function facebook(Request $request, string $orgEncodedUuid, string $orgApiKey, OrganizationRepository $orgRepository, OrganizationApiService $orgApiService, OrganizationLeadService $orgLeadService, UuidEncoder $uuidEncoder)
    {
        /**
         * todo: webhooks get sent here and contain a lead_id - request lead details by providing stored token
         * todo: if we have auth to fetch the lead: store the lead details in the database
         * todo: else, store the leadgen object to fetch the details later
         */
        return new JsonResponse(['request' => $request->request->all()], 200);
    }

    /**
     * @Route("/webhook/retreaver/new_call_lead/{orgEncodedUuid}/{orgApiKey}", name="webhook_retreaver_new_call_lead")
     */
    public function retreaver(Request $request, string $orgEncodedUuid, string $orgApiKey, OrganizationRepository $orgRepository, OrganizationApiService $orgApiService, OrganizationLeadService $orgLeadService, UuidEncoder $uuidEncoder)
    {
        if (!$organization = $orgRepository->findOneByEncodedUuid($orgEncodedUuid)) {
            return new JsonResponse(['error' => 'invalid organization id'], 401);
        }
        if (!$orgApiService->keyIsValid($organization, $orgApiKey)) {
            return new JsonResponse(['error' => 'invalid key'], 401);
        }
        if (!$lead = $orgLeadService->createLeadFromArray($organization, $request->query->all(), $this->getDoctrine()->getManager())) {
            return new JsonResponse(['error' => 'lead creation failed'], 401);
        }
        return new JsonResponse(['lead' => $uuidEncoder->encode($lead->getUuid())], 200);
    }

    /**
     * @Route("/webhook/custom/new_lead/{orgEncodedUuid}/{orgApiKey}", name="webhook_new_lead", methods={"POST"})
     */
    public function custom(Request $request, string $orgEncodedUuid, string $orgApiKey, OrganizationRepository $orgRepository, OrganizationApiService $orgApiService, OrganizationLeadService $orgLeadService, UuidEncoder $uuidEncoder)
    {
        if (!$organization = $orgRepository->findOneByEncodedUuid($orgEncodedUuid)) {
            return new JsonResponse(['error' => 'invalid organization id'], 401);
        }
        if (!$orgApiService->keyIsValid($organization, $orgApiKey)) {
            return new JsonResponse(['error' => 'invalid key'], 401);
        }
        if (!$lead = $orgLeadService->createLeadFromArray($organization, $request->request->all(), $this->getDoctrine()->getManager())) {
            return new JsonResponse(['error' => 'lead creation failed'], 401);
        }
        return new JsonResponse(['lead' => $uuidEncoder->encode($lead->getUuid())], 200);
    }
}
