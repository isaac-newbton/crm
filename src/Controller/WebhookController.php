<?php

namespace App\Controller;

use App\Doctrine\UuidEncoder;
use App\Entity\FacebookLeadgen;
use App\Repository\OrganizationRepository;
use App\Service\FacebookService;
use App\Service\OrganizationApiService;
use App\Service\OrganizationLeadService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\Lead;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
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
    public function facebookAuth(
        Request $request,
        OrganizationLeadService $organizationLeadService,
        OrganizationRepository $organizationRepository,
        EntityManagerInterface $entityManagerInterface,
        FacebookService $fbService,
        Filesystem $filesystem
    ) {

        /**
         * todo: if we are recieving a leadgen object:
         * todo: token ($token) returned from previous step stored (somewhere)
         * todo: retreive the lead details and store in database - fallback to store leadgen object in database
         * todo: notify related org contacts after saving data
         */

        $logfilePath = 'facebook/webhook_log.txt';
        $filesystem->dumpFile($logfilePath, __LINE__);

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

        $filesystem->dumpFile($logfilePath, __LINE__);

        if(!is_object($data) || empty($data)) return new Response('Empty request', 400);

        // we have a leadgen object

        if(!empty($data->entry)){

            $filesystem->dumpFile($logfilePath, __LINE__ . PHP_EOL . var_export($data, true));

            $leadgens = [];
            foreach($data->entry as $entryItem){

                $filesystem->dumpFile($logfilePath, __LINE__ . PHP_EOL . var_export($entryItem, true));

                if(isset($entryItem['changes']) && !empty($entryItem['changes'])){
                    foreach($entryItem['changes'] as $change){
                        if('leadgen'===$change['field'] && isset($change['value']) && !empty($change['value'])){
                            $leadgens[] = $change['value'];
                        }
                    }
                }
            }

            $filesystem->dumpFile($logfilePath, __LINE__ . PHP_EOL . var_export($data, true));

            if(!empty($leadgens)){

                $filesystem->dumpFile($logfilePath, __LINE__ . PHP_EOL . var_export($leadgens, true));

                $entityManager = $this->getDoctrine()->getManager();
                foreach($leadgens as $leadgen){
                    if(isset($leadgen['leadgen_id']) && is_numeric($leadgen['leadgen_id'])){
                        $fbLeadgen = new FacebookLeadgen();
                        $fbLeadgen->setLeadgenId($leadgen['leadgen_id']);
                        if(isset($leadgen['page_id']) && is_numeric($leadgen['page_id'])){
                            $fbLeadgen->setFacebookPage($leadgen['page_id']);
                            if($organization = $organizationRepository->findOneBy(['facebookPage'=>$leadgen['page_id']])){
                                $organization->addFacebookLeadgen($fbLeadgen);
                                $entityManager->persist($organization);
                            }
                        }
                        $entityManager->persist($fbLeadgen);
                        $entityManager->flush();
                        $json = $fbService->attemptLeadgenLead($fbLeadgen, $entityManager, $organizationRepository);

                        $filesystem->dumpFile($logfilePath, __LINE__ . PHP_EOL . var_export($leadgen, true));

                        return new JsonResponse(['result'=>$json ? json_decode($json, true) : false]);
                    }
                }
            }
        }

        $filesystem->dumpFile($logfilePath, __LINE__);

        return new JsonResponse(['request' => $request->request->all()], 200);

        if ($data->object == 'page' && $data->entry[0]->changes[0]->field == 'leadgen') {
            $leadgen = get_object_vars($data->entry[0]->changes[0]->value);
            $page_id = $leadgen['page_id'];
            $lead_data = $leadgen; // default
            // try and find the page in the crm from this id
            try {

                $access_token = $fbService->getAccessToken();
                $app_secret = $_ENV['FB_APP_SECRET'];
                $app_id = $_ENV['FB_APP_ID'];
                $id = $leadgen['leadgen_id'];

                $api = Api::init($app_id, $app_secret, $access_token);
                $api->setLogger(new CurlLogger());

                $fields = array();
                $params = array();
                $resp = json_encode((new Lead($id))->getSelf(
                    $fields,
                    $params
                )->exportAllData(), JSON_PRETTY_PRINT);

                if ($resp) $lead_data = $resp->field_data;
            } catch (Exception $e) {
                // todo: handle this
                error_log($e->getMessage());
            }
            if ($organization = $organizationRepository->findOneBy(['facebookPage' => $page_id])) {
                $organizationLeadService->createLeadFromArray(
                    $organization,
                    $lead_data,
                    $entityManagerInterface
                );
            } else {
                return new JsonResponse('unable to find organization', 404);
            }
        }

        // }

        /**
         * todo: if we are auth:
         * todo: check if token exists (somewheere) and ping facebook to refresh
         * todo: user logins to facebook and authorizes app to manage pages
         */
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
     * @Route("/webhook/facebook/new_lead", name="webhook_facebook_new_lead")
     */
    public function facebook(Request $request, OrganizationRepository $orgRepository, OrganizationLeadService $orgLeadService, FacebookService $fbService)
    {
        $entry = $request->request->get('entry');
        if(!empty($entry)){
            $leadgens = [];
            foreach($entry as $entryItem){
                if(isset($entryItem['changes']) && !empty($entryItem['changes'])){
                    foreach($entryItem['changes'] as $change){
                        if('leadgen'===$change['field'] && isset($change['value']) && !empty($change['value'])){
                            $leadgens[] = $change['value'];
                        }
                    }
                }
            }

            if(!empty($leadgens)){
                $entityManager = $this->getDoctrine()->getManager();
                foreach($leadgens as $leadgen){
                    if(isset($leadgen['leadgen_id']) && is_numeric($leadgen['leadgen_id'])){
                        $fbLeadgen = new FacebookLeadgen();
                        $fbLeadgen->setLeadgenId($leadgen['leadgen_id']);
                        if(isset($leadgen['page_id']) && is_numeric($leadgen['page_id'])){
                            $fbLeadgen->setFacebookPage($leadgen['page_id']);
                            if($organization = $orgRepository->findOneBy(['facebookPage'=>$leadgen['page_id']])){
                                $organization->addFacebookLeadgen($fbLeadgen);
                                $entityManager->persist($organization);
                            }
                        }
                        $entityManager->persist($fbLeadgen);
                        $entityManager->flush();
                        $fbService->attemptLeadgenLead($fbLeadgen, $entityManager, $orgRepository);
                    }
                }
            }
        }
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
