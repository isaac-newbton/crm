<?php

namespace App\Controller;

use App\Doctrine\UuidEncoder;
use App\Repository\OrganizationRepository;
use App\Service\OrganizationApiService;
use App\Service\OrganizationLeadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    /**
     * @Route("/webhook/facebook/new_lead/{orgEncodedUuid}/{orgApiKey}", name="webhook_facebook_new_lead")
     */
    public function facebook(Request $request, string $orgEncodedUuid, string $orgApiKey, OrganizationRepository $orgRepository, OrganizationApiService $orgApiService, OrganizationLeadService $orgLeadService, UuidEncoder $uuidEncoder){
        return new JsonResponse(['request'=>$request->request->all()], 200);
    }

    /**
     * @Route("/webhook/retreaver/new_call_lead/{orgEncodedUuid}/{orgApiKey}", name="webhook_retreaver_new_call_lead")
     */
    public function retreaver(Request $request, string $orgEncodedUuid, string $orgApiKey, OrganizationRepository $orgRepository, OrganizationApiService $orgApiService, OrganizationLeadService $orgLeadService, UuidEncoder $uuidEncoder){
        if(!$organization = $orgRepository->findOneByEncodedUuid($orgEncodedUuid)){
            return new JsonResponse(['error'=>'invalid organization id'], 401);
        }
        if(!$orgApiService->keyIsValid($organization, $orgApiKey)){
            return new JsonResponse(['error'=>'invalid key'], 401);
        }
        if(!$lead = $orgLeadService->createLeadFromArray($organization, $request->query->all(), $this->getDoctrine()->getManager())){
            return new JsonResponse(['error'=>'lead creation failed'], 401);
        }
        return new JsonResponse(['lead'=>$uuidEncoder->encode($lead->getUuid())], 200);
    }

    /**
     * @Route("/webhook/custom/new_lead/{orgEncodedUuid}/{orgApiKey}", name="webhook_new_lead")
     */
    public function custom(Request $request, string $orgEncodedUuid, string $orgApiKey, OrganizationRepository $orgRepository, OrganizationApiService $orgApiService, OrganizationLeadService $orgLeadService, UuidEncoder $uuidEncoder){
        if(!$organization = $orgRepository->findOneByEncodedUuid($orgEncodedUuid)){
            return new JsonResponse(['error'=>'invalid organization id'], 401);
        }
        if(!$orgApiService->keyIsValid($organization, $orgApiKey)){
            return new JsonResponse(['error'=>'invalid key'], 401);
        }
        if(!$lead = $orgLeadService->createLeadFromArray($organization, $request->request->all(), $this->getDoctrine()->getManager())){
            return new JsonResponse(['error'=>'lead creation failed'], 401);
        }
        return new JsonResponse(['lead'=>$uuidEncoder->encode($lead->getUuid())], 200);
    }
}
