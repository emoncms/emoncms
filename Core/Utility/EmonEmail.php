<?php
require_once 'swift_required.php';

class EmonEmail {
/**
 * Email subject
 *
 * @var string
 */
	protected $_subject = 'EmonCMS Email';

	protected $_message = null;

	protected $_to = null;

	protected $_from = null;

	public function __construct() {
		$this->_from = Configure::read('Smtp.from');
	}

	public function subject($subject = null) {
		if ($subject !== null) {
			$this->_subject = $subject;
		}

		return $this->_subject;
	}

	public function message($subject = null) {
		if ($message !== null) {
			$this->_message = $message;
		}

		return $this->_message;
	}

	public function to($to) {
		if ($to !== null) {
			$this->_to = $to;
		}

		return $this->_to;
	}

	public function from($from = null) {
		if ($from !== null) {
			$this->_from = $from;
		}

		return $this->_from;
	}
	
	public function send($message = null) {
        $transport = Swift_SmtpTransport::newInstance()
            ->setHost(Configure::read('Smtp.host'))
            ->setPort(Configure::read('Smtp.port') ?: 26)
            ->setUsername(Configure::read('Smtp.username'))
            ->setPassword(Configure::read('Smtp.password'));

		if ($message === null) {
			$message = $this->message() ?: 'EmonCMS Mail';
		}

        $message = Swift_Message::newInstance()
          ->setSubject($this->_subject)
          ->setFrom($this->_from)
          ->setTo(array($this->_to))
          ->setBody($message);
        return Swift_Mailer::newInstance($transport)->send($message);
	}
}