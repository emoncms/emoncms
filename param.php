<?php

/*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Param
{
    private $route;
    private $user;
    private $params = array();
    
    // Associative array to make search fast
    private $allowed_apis = array("input/post","input/bulk");
    
    public $sha256base64_response = false;

    public function __construct($route, $user)
    {
        $this->route = $route;
        $this->user = $user;
        $this->load();
    }

    public function load()
    {
        $this->params = array();
        
        foreach ($_GET as $key => $val) {
            if (is_array($val)) {
                $val = array_map("stripslashes", $val);
            } else {
                $val = stripslashes($val);
            }
            $this->params[$key] = $val;
        }
        foreach ($_POST as $key => $val) {
            if (is_array($val)) {
                $val = array_map("stripslashes", $val);
            } else {
                $val = stripslashes($val);
            }
            $this->params[$key] = $val;
        }
        
        // Temporary restriction on allowed api's for encrypted method
        $allowed_apis = array_flip($this->allowed_apis);
        $api = $this->route->controller."/".$this->route->action;
        if (!isset($allowed_apis[$api])) {
            return false;
        }
        
        // Decode encrypted parameters
        
        if (isset($_SERVER["CONTENT_TYPE"]) && $_SERVER["CONTENT_TYPE"]=="aes128cbc") {
            // Fetch authorization header
            if (!isset($_SERVER["HTTP_AUTHORIZATION"])) {
                echo "missing authorization header";
                die;
            }
            $authorization = explode(":", $_SERVER["HTTP_AUTHORIZATION"]);
            if (count($authorization)!=2) {
                echo "authorization header format should be userid:hmac";
                die;
            }
            $userid = $authorization[0];
            $hmac1 = $authorization[1];
            
            // Fetch user
            $apikey = $this->user->get_apikey_write($userid);
            if ($apikey===false) {
                echo "User not found";
                die;
            }

            // Fetch encrypted data from POST body
            $base64EncryptedData = file_get_contents('php://input');
            if ($base64EncryptedData=="") {
                echo "no content in post body";
                die;
            }

            // The base64 is converted from "URL safe" code to standard base64 (RFC2045 etc),
            // then it is decoded into the binary encrypted data
            $encryptedData = base64_decode(str_replace(array('-','_'), array('+','/'), $base64EncryptedData));

            // The binary encrypted data is decrypted using the apikey.
            // Note that the first 16 bytes of the encrypted data string are the IV and
            // the actual data follows
            $dataString = @openssl_decrypt(substr($encryptedData, 16), 'AES-128-CBC', hex2bin($apikey), OPENSSL_RAW_DATA, substr($encryptedData, 0, 16));
            
            // HMAC generated from decoded data
            $hmac2 = hash_hmac('sha256', $dataString, hex2bin($apikey));
            
            if (!hash_equals($hmac1, $hmac2)) {
                echo "invalid data";
                die;
            }
            
            global $session; // USE OF GLOBAL HERE!
            $session["write"] = true;
            $session["read"] = true;
            $session["userid"] = $userid;
            
            foreach (explode('&', $dataString) as $chunk) {
                $param = explode("=", $chunk);
                if (count($param)==2) {
                    $key = $param[0];
                    $val = $param[1];
                    $this->params[$key] = $val;
                }
            }
            
            $this->sha256base64_response = str_replace(array('+','/'), array('-','_'), base64_encode(hash("sha256", $dataString, true)));
        }
    }
    
    public function val($index)
    {
        if (isset($this->params[$index])) {
            return $this->params[$index];
        } else {
            return null;
        }
    }

    public function exists($index)
    {
        if (isset($this->params[$index])) {
            return true;
        } else {
            return false;
        }
    }
}
