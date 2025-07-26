<?php
/*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    This script is a test for sending emails from the Emoncms CLI.
    It uses the SMTP settings defined in the settings.ini file.
    To run this script, use the command:

    php scripts/examples/send_email_test.php

    Make sure to configure the SMTP settings in settings.ini before running.

    Add the following to your settings.ini file:

    [smtp]
    default_emailto = 'Your email to test'
    host = "smpt.emailprovider.com"
    port = 465
    from_email = 'hello@myemail.com'
    from_name = 'Emoncms'
    encryption = "ssl"
    username = "hello@myemail.com"
    password = "PASSWORD"

*/

// CLI only
if (php_sapi_name() !== 'cli') {
    echo "This script is for CLI use only.\n";
    die;
}

// Required 
define('EMONCMS_EXEC', 1);

// Change to the emoncms root directory
chdir(dirname(__FILE__)."/../../");

// Load email settings
require "process_settings.php";
require "Lib/EmonLogger.php";
$log = new EmonLogger(__FILE__);

$email = $settings['smtp']['default_emailto'];

require "Lib/email.php";
$emailer = new Email();
$emailer->to(array($email));
$emailer->subject("Email test example from Emoncms CLI script");
$emailer->body("<p>This is a test email sent from the Emoncms CLI script.</p><p>If you received this email, it means that the email configuration is working correctly.</p>");
$result = $emailer->send();
if (!$result['success']) {
    print "Email send returned error. emailto=" . $email . " message='" . $result['message'] . "'\n";
} else {
    print "Email sent successfully to $email\n";
}