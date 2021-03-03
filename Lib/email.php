<?php

/* A simple helper class for email functions

   Compatible with SwiftMailer v5.4.8
   Installation:

   git -C /opt/emoncms/modules clone -b 'v5.4.8' --single-branch https://github.com/swiftmailer/swiftmailer.git

   Copy SMTP settings section from default-settings.ini to settings.ini and ammend as necessary

*/

class Email
{
    private $log;
    private $have_swift;
    private $message;

    function __construct()
    {
        global $settings, $linked_modules_dir;
        $this->log = new EmonLogger(__FILE__);

        $this->message = null;
        // include SwiftMailer. path from a PEAR install,
        $this->have_swift = @include_once("swift_required.php");
        // path from module lib
        if (!$this->have_swift) {
            $this->have_swift = @include_once("$linked_modules_dir/swiftmailer/lib/swift_required.php");
        }
        if ($this->have_swift) {
            $this->message = Swift_Message::newInstance();
            
            $from = array();
            $from[$settings['smtp']['from_email']] = $settings['smtp']['from_name'];
            $this->message->setFrom($from);
        }
    }

    function check()
    {
        if (!$this->have_swift) {
            $this->log->error("check() Could not find SwiftMailer, email functions are ignored.");
            return false;
        }
        return true;
    }

    function from($from)
    {
        if ($this->check()) {
            $this->message->setFrom($from);
        }
    }

    function to($to)
    {
        if ($this->check()) {
            $this->message->setTo($to);
        }
    }
    
    function cc($cc)
    {
        if ($this->check()) {
            $this->message->setCc($cc);
        }
    }
    function bcc($bcc)
    {
        if ($this->check()) {
            $this->message->setBcc($bcc);
        }
    }

    function subject($subject)
    {
        if ($this->check()) {
            $this->message->setSubject($subject);
        }
    }

    function body($body, $type = 'text/html')
    {
        if ($this->check()) {
            $this->message->setBody($body, $type);
        }
    }

    function attach($filepath, $contentType = null)
    {
        if ($this->check()) {
            $this->message->attach(Swift_Attachment::fromPath($filepath, $contentType));
        }
    }

    function send()
    {
        global $settings;
        if ($this->check()) {
            try {
                $transport = Swift_SmtpTransport::newInstance($settings['smtp']['host'], $settings['smtp']['port']);
                if (isset($settings['smtp']['encryption']) && $settings['smtp']['encryption']) {
                    $transport->setEncryption($settings['smtp']['encryption']);
                }
                if (isset($settings['smtp']['username'])) {
                    $transport->setUsername($settings['smtp']['username']);
                }
                if (isset($settings['smtp']['password'])) {
                    $transport->setPassword($settings['smtp']['password']);
                }

                $mailer = Swift_Mailer::newInstance($transport);
                $mailer->send($this->message);
            } catch (Exception $e) {
                return array('success'=>false, 'message'=>$e->getMessage());
            }
            return array('success'=>true, 'message'=>"");
        } else {
            return array('success'=>false, 'message'=>"Could not find SwiftMailer, email not sent.");
        }
    }
}
