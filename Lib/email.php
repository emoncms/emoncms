<?php

/* A simple email helper class.

   Builds the message, then hands it to one of three transports selected by
   settings['email']['transport']:

     smtp        SMTP relay via fsockopen, with authentication
     sendmail    the local /usr/sbin/sendmail binary
     mailersend  the MailerSend HTTP API

   Every call site builds a message the same way and reads the same
   array('success'=>bool, 'message'=>string) back, so which transport an install
   uses is a setting rather than a branch at each place an email is sent.
*/

class Email
{
    private $log;
    private $smtp_settings;
    private $email_settings;
    private $transport;
    private $from_email;
    private $from_name;
    private $to;
    private $cc;
    private $bcc;
    private $subject;
    private $body;
    private $content_type;
    private $attachments;
    private $boundary; // Added missing property

    function __construct($smtp_settings = null, $email_settings = null)
    {
        global $settings;
        $this->log = new EmonLogger(__FILE__);
        
        // Allow dependency injection for better testability
        $this->smtp_settings = $smtp_settings ?? ($settings['smtp'] ?? []);
        $this->email_settings = $email_settings ?? ($settings['email'] ?? []);
        $this->transport = $this->resolveTransport();

        // The from address lives in [email] so that it is shared by every
        // transport, and falls back to [smtp] so that installs configured
        // before [email] existed keep the address they already set.
        $this->from_email = $this->email_settings['from_email'] ?? ($this->smtp_settings['from_email'] ?? '');
        $this->from_name = $this->email_settings['from_name'] ?? ($this->smtp_settings['from_name'] ?? '');
        $this->to = '';
        $this->cc = '';
        $this->bcc = '';
        $this->subject = '';
        $this->body = '';
        $this->content_type = 'text/html';
        $this->attachments = [];
    }

    /**
     * Which transport to deliver through.
     *
     * An explicit settings['email']['transport'] always wins. With none set,
     * derive it from the pre-existing [smtp] block so that an install upgraded
     * from before this setting existed behaves exactly as it did: sendmail if
     * the sendmail flag was on, SMTP otherwise. Anything unrecognised falls
     * back to smtp with a note in the log rather than throwing, so a typo
     * cannot take email down silently.
     *
     * @return string  smtp|sendmail|mailersend
     */
    private function resolveTransport()
    {
        $transport = isset($this->email_settings['transport'])
            ? strtolower(trim($this->email_settings['transport'])) : '';

        if ($transport === '') {
            return !empty($this->smtp_settings['sendmail']) ? 'sendmail' : 'smtp';
        }

        if (in_array($transport, array('smtp', 'sendmail', 'mailersend'), true)) {
            return $transport;
        }

        $this->log->error("unknown settings['email']['transport'] '$transport', falling back to smtp");
        return 'smtp';
    }

    // The transport in use, for callers that want to log it
    function transport()
    {
        return $this->transport;
    }

    // Add email validation
    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Add header sanitization to prevent injection attacks
    private function sanitizeHeader($header)
    {
        return str_replace(["\r", "\n", "\0"], '', $header);
    }

    function check()
    {
        if (empty($this->from_email)) {
            $this->log->error("check() from_email not configured.");
            return false;
        }

        if ($this->transport === 'mailersend') {
            if (!function_exists('curl_init')) {
                $this->log->error("check() curl is not available, required by the mailersend transport.");
                return false;
            }
            if (empty($this->email_settings['mailersend_api_key'])) {
                $this->log->error("check() mailersend_api_key not configured.");
                return false;
            }
            return true;
        }

        if ($this->transport === 'sendmail') {
            // Nothing to check beyond the from address: the local binary either
            // opens or it does not, which sendViaSendmail() reports. This no
            // longer demands an SMTP host, which sendmail never used.
            return true;
        }

        if (!function_exists('fsockopen')) {
            $this->log->error("check() fsockopen() function is not available.");
            return false;
        }
        
        if (empty($this->smtp_settings['host'])) {
            $this->log->error("check() SMTP host not configured.");
            return false;
        }
        
        return true;
    }

    function from($from)
    {
        if (is_array($from)) {
            $email = key($from);
            $name = current($from);
        } else {
            $email = $from;
            $name = '';
        }
        
        // Validate email address
        if (!$this->validateEmail($email)) {
            throw new InvalidArgumentException("Invalid from email address: $email");
        }
        
        $this->from_email = $email;
        $this->from_name = $this->sanitizeHeader($name);
    }

    function to($to)
    {
        // Validate all email addresses
        $emails = is_array($to) ? $to : [$to];
        foreach ($emails as $email) {
            $cleanEmail = $this->extractEmail($email);
            if (!$this->validateEmail($cleanEmail)) {
                throw new InvalidArgumentException("Invalid email address: $email");
            }
        }
        $this->to = is_array($to) ? implode(', ', $to) : $to;
    }
    
    function cc($cc)
    {
        // Validate CC email addresses
        if (!empty($cc)) {
            $emails = is_array($cc) ? $cc : [$cc];
            foreach ($emails as $email) {
                $cleanEmail = $this->extractEmail($email);
                if (!$this->validateEmail($cleanEmail)) {
                    throw new InvalidArgumentException("Invalid CC email address: $email");
                }
            }
        }
        $this->cc = is_array($cc) ? implode(', ', $cc) : $cc;
    }
    
    function bcc($bcc)
    {
        // Validate BCC email addresses
        if (!empty($bcc)) {
            $emails = is_array($bcc) ? $bcc : [$bcc];
            foreach ($emails as $email) {
                $cleanEmail = $this->extractEmail($email);
                if (!$this->validateEmail($cleanEmail)) {
                    throw new InvalidArgumentException("Invalid BCC email address: $email");
                }
            }
        }
        $this->bcc = is_array($bcc) ? implode(', ', $bcc) : $bcc;
    }

    function subject($subject)
    {
        $this->subject = $this->sanitizeHeader($subject);
    }

    function body($body, $type = 'text/html')
    {
        $this->body = $body;
        $this->content_type = $type;
    }

    function attach($filepath, $contentType = null)
    {
        if (file_exists($filepath)) {
            $this->attachments[] = [
                'path' => $filepath,
                'type' => $contentType ?: mime_content_type($filepath),
                'name' => basename($filepath)
            ];
        }
    }

    function send()
    {
        if (!$this->check()) {
            return array('success'=>false, 'message'=>"Email configuration invalid, see the log for what is missing.");
        }

        try {
            if ($this->transport === 'mailersend') {
                return $this->sendViaMailersend();
            } else if ($this->transport === 'sendmail') {
                return $this->sendViaSendmail();
            } else {
                return $this->sendViaSMTP();
            }
        } catch (Exception $e) {
            $this->log->error("Email send failed: " . $e->getMessage());
            return array('success'=>false, 'message'=>"Failed to send email");
        }
    }

    /**
     * Deliver through the MailerSend HTTP API.
     *
     * Timeouts are the point of the explicit curl options: this runs inline in
     * requests a person is waiting on, registration and password reset among
     * them, so a MailerSend outage has to cost a bounded wait and a logged
     * failure rather than hanging the request until PHP gives up.
     *
     * The caller sees the same array('success'=>..., 'message'=>...) as the
     * other two transports. The response body is carried into the message
     * because MailerSend's 422 names the field it rejected, which is the
     * difference between a diagnosable failure and a mystery.
     */
    private function sendViaMailersend()
    {
        $payload = $this->mailersendPayload();
        if ($payload === false) {
            return array('success'=>false, 'message'=>"No recipient");
        }

        $api_key = $this->email_settings['mailersend_api_key'];

        $ch = curl_init("https://api.mailersend.com/v1/email");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "X-Requested-With: XMLHttpRequest",
            "Authorization: Bearer ".$api_key
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        // Generous enough for the CSV attachments the export scripts send,
        // short enough that a stalled API does not hold a PHP worker for long.
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        // no curl_close(): deprecated since PHP 8.0, the handle frees itself
        unset($ch);

        if ($status >= 200 && $status < 300) {
            return array('success'=>true, 'message'=>"", 'status'=>$status);
        }

        if ($status === 0) {
            $message = "MailerSend unreachable: ".($curl_error !== '' ? $curl_error : "no response");
        } else {
            $message = "MailerSend returned HTTP $status: ".substr((string) $response, 0, 300);
        }

        // status is carried out so a caller sending in bulk can tell a rate
        // limit, which is worth waiting out, from a rejected message, which is
        // not. Absent for the other transports, which have no HTTP status.
        $this->log->error($message);
        return array('success'=>false, 'message'=>$message, 'status'=>$status);
    }

    // The request body, split out from the call so it can be inspected on its
    // own. Returns false when there is no recipient to send to.
    private function mailersendPayload()
    {
        $to = $this->parseEmails($this->to);
        if (empty($to)) return false;

        $from = array('email'=>$this->from_email);
        if (!empty($this->from_name)) $from['name'] = $this->from_name;

        $payload = array(
            'from' => $from,
            'to' => $this->mailersendRecipients($to),
            'subject' => $this->subject,
            'html' => $this->body,
            // A text part is not optional in practice: HTML only mail scores
            // badly with spam filters and is unreadable in text only clients.
            'text' => $this->plainTextBody()
        );

        $cc = $this->parseEmails($this->cc);
        if (!empty($cc)) $payload['cc'] = $this->mailersendRecipients($cc);
        $bcc = $this->parseEmails($this->bcc);
        if (!empty($bcc)) $payload['bcc'] = $this->mailersendRecipients($bcc);

        foreach ($this->attachments as $attachment) {
            $content = @file_get_contents($attachment['path']);
            if ($content === false) {
                $this->log->warn("could not read attachment ".$attachment['path']);
                continue;
            }
            $payload['attachments'][] = array(
                'content' => base64_encode($content),
                'filename' => $attachment['name'],
                'disposition' => 'attachment'
            );
        }

        return $payload;
    }

    private function mailersendRecipients($emails)
    {
        $recipients = array();
        foreach ($emails as $email) {
            $recipients[] = array('email'=>$email);
        }
        return $recipients;
    }

    // Plain text alternative derived from the HTML body. Entities are decoded
    // so the text part reads as "you & me" rather than "you &amp; me".
    private function plainTextBody()
    {
        if ($this->content_type !== 'text/html') return $this->body;

        $text = preg_replace('/<br\s*\/?>/i', "\n", $this->body);
        $text = preg_replace('/<\/p>/i', "\n\n", $text);
        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
    }
    
    private function sendViaSendmail()
    {
        $headers = $this->buildHeaders();
        $body = $this->buildBody();
        
        $sendmail_path = '/usr/sbin/sendmail -bs';
        $mail_content = $headers . "\r\n" . $body;
        
        $process = popen($sendmail_path, 'w');
        if (!$process) {
            return array('success'=>false, 'message'=>"Could not open sendmail process.");
        }
        
        fwrite($process, $mail_content);
        $result = pclose($process);
        
        if ($result === 0) {
            return array('success'=>true, 'message'=>"");
        } else {
            return array('success'=>false, 'message'=>"Sendmail returned error code: $result");
        }
    }

    // Improved SMTP response reading for multi-line responses
    private function readSMTPResponse($smtp)
    {
        $response = '';
        do {
            $line = fgets($smtp, 515);
            if ($line === false) {
                throw new Exception("Failed to read SMTP response");
            }
            $response .= $line;
            // Continue reading if line starts with "xxx-" (multi-line response)
        } while (strlen($line) >= 4 && $line[3] === '-');
        
        return trim($response);
    }

    // Improved socket cleanup
    private function closeSMTP($smtp, $message = "")
    {
        if (is_resource($smtp)) {
            @fwrite($smtp, "QUIT\r\n");
            @fclose($smtp);
        }
        if ($message) {
            throw new Exception($message);
        }
    }
    
    private function sendViaSMTP()
    {
        $host = $this->smtp_settings['host'];
        $port = $this->smtp_settings['port'] ?? 25;
        $username = $this->smtp_settings['username'] ?? '';
        $password = $this->smtp_settings['password'] ?? '';
        $encryption = $this->smtp_settings['encryption'] ?? '';
        $timeout = $this->smtp_settings['timeout'] ?? 30;
        
        $smtp = null;
        
        try {
            // Create socket with proper SSL handling
            $context = stream_context_create();
            if ($encryption === 'ssl') {
                $host = 'ssl://' . $host;
                $port = $port ?: 465;
            }
            
            $smtp = fsockopen($host, $port, $errno, $errstr, $timeout);
            if (!$smtp) {
                throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
            }

            // Set timeout for socket operations
            stream_set_timeout($smtp, $timeout);
            
            // Read server greeting
            $response = $this->readSMTPResponse($smtp);
            if (substr($response, 0, 3) !== '220') {
                $this->closeSMTP($smtp, "SMTP server error: " . substr($response, 0, 50));
            }
            
            // Send EHLO
            fwrite($smtp, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
            $response = $this->readSMTPResponse($smtp);
            
            // Start TLS if required
            if ($encryption === 'tls') {
                fwrite($smtp, "STARTTLS\r\n");
                $response = $this->readSMTPResponse($smtp);
                if (substr($response, 0, 3) !== '220') {
                    $this->closeSMTP($smtp, "STARTTLS failed");
                }
                
                stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // Send EHLO again after TLS
                fwrite($smtp, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
                $response = $this->readSMTPResponse($smtp);
            }
            
            // Authenticate if credentials provided
            if ($username && $password) {
                fwrite($smtp, "AUTH LOGIN\r\n");
                $response = $this->readSMTPResponse($smtp);
                if (substr($response, 0, 3) !== '334') {
                    $this->closeSMTP($smtp, "AUTH LOGIN failed");
                }
                
                fwrite($smtp, base64_encode($username) . "\r\n");
                $response = $this->readSMTPResponse($smtp);
                if (substr($response, 0, 3) !== '334') {
                    $this->closeSMTP($smtp, "Username authentication failed");
                }
                
                fwrite($smtp, base64_encode($password) . "\r\n");
                $response = $this->readSMTPResponse($smtp);
                if (substr($response, 0, 3) !== '235') {
                    $this->closeSMTP($smtp, "Password authentication failed");
                }
            }
            
            // Send MAIL FROM
            fwrite($smtp, "MAIL FROM: <{$this->from_email}>\r\n");
            $response = $this->readSMTPResponse($smtp);
            if (substr($response, 0, 3) !== '250') {
                $this->closeSMTP($smtp, "MAIL FROM failed");
            }
            
            // Send RCPT TO
            $recipients = array_merge(
                $this->parseEmails($this->to),
                $this->parseEmails($this->cc),
                $this->parseEmails($this->bcc)
            );
            
            foreach ($recipients as $recipient) {
                fwrite($smtp, "RCPT TO: <{$recipient}>\r\n");
                $response = $this->readSMTPResponse($smtp);
                if (substr($response, 0, 3) !== '250') {
                    $this->closeSMTP($smtp, "RCPT TO failed for recipient");
                }
            }
            
            // Send DATA
            fwrite($smtp, "DATA\r\n");
            $response = $this->readSMTPResponse($smtp);
            if (substr($response, 0, 3) !== '354') {
                $this->closeSMTP($smtp, "DATA command failed");
            }
            
            // Send headers and body
            $headers = $this->buildHeaders();
            $body = $this->buildBody();
            
            fwrite($smtp, $headers . "\r\n" . $body . "\r\n.\r\n");
            $response = $this->readSMTPResponse($smtp);
            if (substr($response, 0, 3) !== '250') {
                $this->closeSMTP($smtp, "Message sending failed");
            }
            
            // Send QUIT and close
            $this->closeSMTP($smtp);
            
            return array('success'=>true, 'message'=>"Email sent successfully");
            
        } catch (Exception $e) {
            if ($smtp) {
                $this->closeSMTP($smtp);
            }
            throw $e; // Re-throw to be caught by send() method
        }
    }
    
    private function buildHeaders()
    {
        $headers = [];
        
        // From header
        if ($this->from_name) {
            $headers[] = "From: {$this->from_name} <{$this->from_email}>";
        } else {
            $headers[] = "From: {$this->from_email}";
        }
        
        // To, CC, BCC
        if ($this->to) $headers[] = "To: {$this->to}";
        if ($this->cc) $headers[] = "Cc: {$this->cc}";
        // Note: BCC is not included in headers
        
        // Subject
        $headers[] = "Subject: {$this->subject}";
        
        // Date
        $headers[] = "Date: " . date('r');
        
        // Message ID
        $headers[] = "Message-ID: <" . md5(uniqid(time())) . "@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">";
        
        // MIME headers
        $headers[] = "MIME-Version: 1.0";
        
        if (empty($this->attachments)) {
            $headers[] = "Content-Type: {$this->content_type}; charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: 8bit";
        } else {
            $boundary = md5(time());
            $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
            $this->boundary = $boundary;
        }
        
        return implode("\r\n", $headers);
    }
    
    private function buildBody()
    {
        if (empty($this->attachments)) {
            return $this->body;
        }
        
        $boundary = $this->boundary;
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: {$this->content_type}; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $this->body . "\r\n";
        
        foreach ($this->attachments as $attachment) {
            $content = base64_encode(file_get_contents($attachment['path']));
            
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: {$attachment['type']}; name=\"{$attachment['name']}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n\r\n";
            $body .= chunk_split($content) . "\r\n";
        }
        
        $body .= "--{$boundary}--\r\n";
        
        return $body;
    }

    // Helper method to extract email from "Name <email>" format
    private function extractEmail($emailString)
    {
        if (preg_match('/<([^>]+)>/', $emailString, $matches)) {
            return trim($matches[1]);
        }
        return trim($emailString);
    }
    
    private function parseEmails($emailString)
    {
        if (empty($emailString)) return [];
        
        $emails = explode(',', $emailString);
        $result = [];
        
        foreach ($emails as $email) {
            $email = trim($email);
            // Extract email from "Name <email@domain.com>" format
            if (preg_match('/<([^>]+)>/', $email, $matches)) {
                $result[] = $matches[1];
            } else {
                $result[] = $email;
            }
        }
        
        return $result;
    }
}