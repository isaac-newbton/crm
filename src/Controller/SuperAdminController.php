<?php
namespace App\Controller;

use App\Service\EmailService;
use App\Service\FacebookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SuperAdminController extends AbstractController{

	/**
	 * @Route("/super/email", name="super_email")
	 */
	public function emailTest(Request $request, EmailService $emailService){

		$sent = false;

		if($request->isMethod('post')){
			$from = $request->request->get('from') . "@{$_ENV['MAILGUN_DOMAIN']}";
			$to = $request->request->get('to');
			if(filter_var($from, FILTER_VALIDATE_EMAIL) && filter_var($to, FILTER_VALIDATE_EMAIL)){
				$title = "Test Email From {$_ENV['MAILGUN_DOMAIN']}";
				$message = $this->renderView('email/base.html.twig', [
					'title'=>$title
				]);
				$sent = $emailService->sendSingleHtml($to, $title, $message, $from);
			}
		}

		return $this->render('superadmin/email_test.html.twig', [
			'sent' => $sent ? var_export($sent, true) : false
		]);

	}

	/**
	 * @Route("/super/facebook_access", name="super_facebook_access")
	 */
	public function fbAccess(FacebookService $fbService){
		return new Response("<html><body>Path = " . $fbService->getAccessTokenPath() . "<br>Token = " . $fbService->getAccessToken() . "</body></html>", 200);
	}

}