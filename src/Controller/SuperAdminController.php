<?php
namespace App\Controller;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
				$sent = $emailService->sendSingleHtml($to, "Test Email From {$_ENV['MAILGUN_DOMAIN']}", "<h1>Test</h1>This is a test email sent as <b>$from</b>.", $from);
			}
		}

		return $this->render('superadmin/email_test.html.twig', [
			'sent' => $sent ? var_export($sent, true) : false
		]);

	}

}