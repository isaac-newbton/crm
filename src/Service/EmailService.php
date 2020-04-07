<?php
namespace App\Service;

use Mailgun\Mailgun;

class EmailService{
	/**
	 * @var Mailgun|null
	 */
	protected $mailgun;

	public function __construct(){
		$this->mailgun = Mailgun::create($_ENV['MAILGUN_API_KEY']);
	}

	public function sendSingleHtml(string $to, string $subject, string $message, string $from){
		return $this->mailgun->messages()->send($_ENV['MAILGUN_DOMAIN'], [
			'from'=>$from,
			'to'=>$to,
			'subject'=>$subject,
			'html'=>$message
		]);
	}
}