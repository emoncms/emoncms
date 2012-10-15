<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function send_basic_mail($to, $subject, $body)
{
  echo "Sending mail".$to.$subject;
  include "Includes/mail_settings.php";

  $headers = 'From: $mail_from' . "\r\n" .
  'Reply-To: nospam@zzzzz.com' . "\r\n" .
  'X-Mailer: PHP/' . phpversion();

  mail($to, $subject, $body, $headers);
}

function send_mail($to, $subject, $body)
{
  require_once "Mail.php";

  include "Includes/mail_settings.php";

  $headers = array(
    'From' => $mail_from,
    'To' => $to,
    'Subject' => $subject,
    'MIME-Version' => "1.0",
    'Content-type' => "text/html;charset=iso-8859-1"
  );
  $smtp = Mail::factory('smtp', array(
    'host' => $mail_host,
    'auth' => true,
    'username' => $mail_username,
    'password' => $mail_password
  ));

  $mail = $smtp -> send($to, $headers, $body);

  if (PEAR::isError($mail))
  {
    $output = ("<p>" . $mail -> getMessage() . "</p>");
  }
  else
  {
    $output = "<p>" . _("Message successfully sent!") . "</p>";
  }

  return $output;
}
?>
