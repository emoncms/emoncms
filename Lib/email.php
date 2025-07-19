<?php

/* A simple email helper class using SMTP via fsockopen
   
   This provides more reliable email delivery than mail() function
   and supports SMTP authentication.
*/

class Email
{
    private $log;
    private $smtp_settings;
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

    function __construct($smtp_settings = null)
    {
        global $settings;
        $this->log = new EmonLogger(__FILE__);
        
        // Allow dependency injection for better testability
        $this->smtp_settings = $smtp_settings ?? ($settings['smtp'] ?? []);
        $this->from_email = $this->smtp_settings['from_email'] ?? '';
        $this->from_name = $this->smtp_settings['from_name'] ?? '';
        $this->to = '';
        $this->cc = '';
        $this->bcc = '';
        $this->subject = '';
        $this->body = '';
        $this->content_type = 'text/html';
        $this->attachments = [];
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
            return array('success'=>false, 'message'=>"SMTP configuration invalid.");
        }

        try {
            // Use sendmail if configured
            if (!empty($this->smtp_settings['sendmail'])) {
                return $this->sendViaSendmail();
            } else {
                return $this->sendViaSMTP();
            }
        } catch (Exception $e) {
            $this->log->error("Email send failed: " . $e->getMessage());
            return array('success'=>false, 'message'=>"Failed to send email");
        }
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