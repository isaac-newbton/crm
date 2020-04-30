<?php

namespace App\Controller;

use App\Doctrine\UuidEncoder;
use App\Entity\FacebookLeadgen;
use App\Repository\OrganizationRepository;
use App\Service\FacebookService;
use App\Service\OrganizationApiService;
use App\Service\OrganizationLeadService;
use App\Entity\Lead;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{

    /**
     * @Route("/webhook/facebook")
     *
     * This endpoint is a catch all from facebook
     */
    public function facebook(
        Request $request,
        OrganizationLeadService $organizationLeadService,
        OrganizationRepository $organizationRepository,
        FacebookService $fbService
    ){

        $challenge = $request->query->get('hub_challenge');
        $verify_token = $request->query->get('hub_verify_token');
        if(null!==$challenge && null!==$verify_token){
            if(isset($_ENV['FB_HUB_VERIFY_TOKEN']) && $verify_token===$_ENV['FB_HUB_VERIFY_TOKEN']){
                return new Response($challenge);
            }else{
                return new Response('Challenge verification failed', 401);
            }
        }

        $data = json_decode($request->getContent());

        if(!is_object($data) || empty($data)) return new Response('Empty request', 400);

        // we have a leadgen object

        if(!empty($data->entry)){

            $leadgens = [];
            foreach($data->entry as $entryItem){

                if(isset($entryItem->changes) && !empty($entryItem->changes)){
                    foreach($entryItem->changes as $change){
                        if('leadgen'===$change->field && isset($change->value) && !empty($change->value)){
                            $leadgens[] = $change->value;
                        }
                    }
                }
            }

            if(!empty($leadgens)){

                $entityManager = $this->getDoctrine()->getManager();
                foreach($leadgens as $leadgen){

                    if(isset($leadgen->leadgen_id)){

                        $fbLeadgen = new FacebookLeadgen();
                        $fbLeadgen->setLeadgenId($leadgen->leadgen_id);
                        if(isset($leadgen->page_id)){

                            $fbLeadgen->setFacebookPage($leadgen->page_id);

                            $organization = $organizationRepository->findOneBy(['facebookPage'=>$leadgen->page_id]);

                            if($organization){

                                $organization->addFacebookLeadgen($fbLeadgen);
                            }
                        }
                        $entityManager->persist($fbLeadgen);
                        $entityManager->flush();

                        /**
                         * @var Lead|bool
                         */
                        $result = $fbService->attemptLeadgenLead($fbLeadgen, $entityManager, $organizationRepository, $organizationLeadService);

                        return new JsonResponse(['result'=>$result], 200);
                    }
                }
            }
        }

        return new JsonResponse(['request' => $request->request->all()], 200);
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
        if (!$lead = $orgLeadService->createLeadFromArray($organization, array_merge($request->query->all(), ['_lead_source'=>'retreaver']), $this->getDoctrine()->getManager())) {
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
        if (!$lead = $orgLeadService->createLeadFromArray($organization, array_merge($request->request->all(), ['_lead_source'=>'custom']), $this->getDoctrine()->getManager())) {
            return new JsonResponse(['error' => 'lead creation failed'], 401);
        }
        return new JsonResponse(['lead' => $uuidEncoder->encode($lead->getUuid())], 200);
    }
}
