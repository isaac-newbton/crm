<?php
namespace App\Service;

use Mailgun\Mailgun;

class EmailService{
	/**
	 * @var Mailgun|null
	 */
	protected $mailgun;

	/**
	 * @var array|null
	 */
	protected $emailOverrides;

	public function __construct(){
		$this->mailgun = Mailgun::create($_ENV['MAILGUN_API_KEY']);
		if(isset($_ENV['EMAIL_NOTIFICATION_OVERRIDE']) && !empty($_ENV['EMAIL_NOTIFICATION_OVERRIDE'])) $this->emailOverrides = explode(',', $_ENV['EMAIL_NOTIFICATION_OVERRIDE']);
	}

	public function sendSingleHtml(string $to, string $subject, string $message, string $from){
		if(is_array($this->emailOverrides) && !empty($this->emailOverrides)){
			$message = "<p><i>Notice: This message was intended to send to <b>$to</b>, but this service is in test mode; all emails will instead be sent to: <b>" . implode(', ', $this->emailOverrides) . "</b></i></p><p>Original Message:</p>$message";
			$to = $this->emailOverrides;
			$subject = "FKCRM Test Mode: $subject";
		}
		return $this->mailgun->messages()->send($_ENV['MAILGUN_DOMAIN'], [
			'from'=>$from,
			'to'=>$to,
			'subject'=>$subject,
			'html'=>$message
		]);
	}
}