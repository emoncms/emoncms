<?php

class Rememberme {

    private $mysqli;
    private $log;
  
    /**
     * Cookie settings
     * @var string
     */
    private $cookieName = "EMONCMS_REMEMBERME";
    private $path = '/';
    private $domain = "";
    private $secure = false;
    private $httpOnly = false;

    // Number of seconds in the future the cookie and storage will expire
    private $expireTime = 7776000; // 90 days

    /**
     * If the return from the storage was Rememberme_Storage_StorageInterface::TRIPLET_INVALID,
     * this is set to true
     *
     * @var bool
     */
    protected $lastLoginTokenWasInvalid = false;

    /**
     * If the login token was invalid, delete all login tokens of this user
     *
     * @var type
     */
    protected $cleanStoredTokensOnInvalidResult = true;

    const TRIPLET_FOUND     =  1,
          TRIPLET_NOT_FOUND =  0,
          TRIPLET_INVALID   = -1;

    // ---------------------------------------------------------------------------------------------------------
    public function __construct($mysqli)
    {
            $this->mysqli = $mysqli;
            $this->log = new EmonLogger(__FILE__);
    }

    // ---------------------------------------------------------------------------------------------------------
    public function setCookie($content,$expire) 
    {
        $this->log->info("setCookie: $content $expire");
        
        setcookie($this->cookieName,$content,$expire,$this->path,$this->domain,$this->secure,$this->httpOnly);
        
        // Double check cookie saved correctly
        if (isset($_COOKIE[$this->cookieName]) && $_COOKIE[$this->cookieName]!=$content) {
            // $this->log->warn("setCookie error cookie=".$_COOKIE[$this->cookieName]." content=".$content);
            // return false;
        }
        
        return true;
    }

    // ---------------------------------------------------------------------------------------------------------
    // Check Credentials from cookie
    // @return bool|string False if login was not successful, credential string if it was successful
    // ---------------------------------------------------------------------------------------------------------
    public function login() {
        $this->log->info("login");
        if (!$cookieValues = $this->getCookieValues()) {
            // If the cookie is invalid
            // the only thing to do is clear the cookie
            
            // Only clear the cookie if there is content in it
            if (isset($_COOKIE[$this->cookieName]) && $_COOKIE[$this->cookieName]!="") {
                // Set cookie blank and force to expire
                $this->setCookie("",time()-$this->expireTime);
                unset($_COOKIE[$this->cookieName]);
            }
            
            $this->lastLoginTokenWasInvalid = true;
            return false;
        }

        $loginResult = false;
        switch($this->findTriplet($cookieValues)) {
            case self::TRIPLET_FOUND:
                // remove old triplet before creating new one, otherwise since the salt is defaulted to "" it would create
                // a new triplet with the same persistentToken in DB which will cause the next findTriplet to fail (finding the incorrect one) and remove the cookie again.
                $this->cleanTriplet($cookieValues);

                // create new cookie and register values in db - refresh token
                $cookieValues->token = $this->createToken();
                $expire = time() + $this->expireTime;
                if ($this->storeTriplet($cookieValues, $expire)) {
                
                    if (!$this->setCookie(implode("|",array($cookieValues->userid,$cookieValues->token,$cookieValues->persistentToken)),$expire)) {
                        // this should never happen
                        $this->log->warn("login, errors setting cookie");
                    }
                    $loginResult = $cookieValues->userid;
                } else {
                    $loginResult = false;
                }
                break;
            case self::TRIPLET_INVALID:
                $this->setCookie("",time()-$this->expireTime);
                $this->lastLoginTokenWasInvalid = true;
                if($this->cleanStoredTokensOnInvalidResult) {
                    $this->cleanAllTriplets($cookieValues->userid);
                }
                break;
            case self::TRIPLET_NOT_FOUND:
                // Only clear the cookie if there is content in it
                if (isset($_COOKIE[$this->cookieName]) && $_COOKIE[$this->cookieName]!="") {
                    // Set cookie blank and force to expire
                    $this->setCookie("",time()-$this->expireTime);
                    unset($_COOKIE[$this->cookieName]);
                }
                break;
        }
        return $loginResult;
    }

    // ---------------------------------------------------------------------------------------------------------
    public function cookieIsValid($userid) {
        $this->log->info("cookieIsValid");
        $userid = (int) $userid;
        
        // Fetch cookie values, if result false cookie is not valid
        if (!$cookieValues = $this->getCookieValues()) return false;
        
        // If we have a valid cookie, check for database match
        $state = $this->findTriplet($cookieValues);
        
        if ($state === self::TRIPLET_FOUND) return true;
        return false;
    }

    // ---------------------------------------------------------------------------------------------------------
    // createCookie called from user_model, login function
    // @param int $userid
    // @return boolean
    // ---------------------------------------------------------------------------------------------------------
    public function createCookie($userid)
    {
        $this->log->info("createCookie");
        
        $cookieValues = new stdClass();
        $cookieValues->userid = (int) $userid;
        $cookieValues->token = $this->createToken();
        $cookieValues->persistentToken = $this->createToken();
        
        $expire = time() + $this->expireTime;
        
        if (!$this->storeTriplet($cookieValues, $expire)) {
            // Failure to save entry to database, will result in message to user defined in user_model
            return false;
        }
        
        if (!$this->setCookie(implode("|",array($cookieValues->userid,$cookieValues->token,$cookieValues->persistentToken)),$expire)) {
            // Failure to set cookie, will result in message to user defined in user_model
            return false;
        }
        
        return true;
    }

    // ---------------------------------------------------------------------------------------------------------
    // Clear cookie
    // called from user_model
    // result is currently unused
    // ---------------------------------------------------------------------------------------------------------
    public function clearCookie() {
        $this->log->info("clearCookie");
        
        // fetch and validate cookie
        $cookieValues = $this->getCookieValues();
        
        // Only clear the cookie if there is content in it
        if (isset($_COOKIE[$this->cookieName]) && $_COOKIE[$this->cookieName]!="") {
            // Set cookie blank and force to expire
            $this->setCookie("",time()-$this->expireTime);
            unset($_COOKIE[$this->cookieName]);
        }
        
        // If original cookie was invalid exit
        if (!$cookieValues) return false;
        
        $this->log->info("clearCookie call to cleanTriplet");
        if (!$this->cleanTriplet($cookieValues)) return false;
        return true;
    }

    public function getCookieName() {
        return $this->cookieName;
    }

    public function loginTokenWasInvalid() {
        return $this->lastLoginTokenWasInvalid;
    }

    // ---------------------------------------------------------------------------------------------------------
    // Create a pseudo-random token.
    // ---------------------------------------------------------------------------------------------------------
    private function createToken() {
            return md5(uniqid(mt_rand(), true));
    }

    // ---------------------------------------------------------------------------------------------------------
    private function getCookieValues()
    {
        // Cookie was not sent with incoming request
        if(!isset($_COOKIE[$this->cookieName])) {
            $this->log->info("getCookieValues: not present");
            return false;
        }
        
        if ($_COOKIE[$this->cookieName]=="") {
            return false;
        }
        
        // $this->log->info($this->cookieName." ".json_encode($_COOKIE));
        
        $cookieValueArray = explode("|", $_COOKIE[$this->cookieName], 3);

        if(count($cookieValueArray) != 3) {
            $this->log->warn("getCookieValues: cookie must contain 3 parts: ".count($cookieValueArray));
            return false;
        }
        
        // $this->log->info("getCookieValues: ".json_encode($cookieValueArray));

        // Validate
        if (intval($cookieValueArray[0])!=$cookieValueArray[0]) {
            $this->log->warn("getCookieValues: userid is not an integer");
            return false;
        }
        if (preg_replace('/[^\w\s]/','',$cookieValueArray[1])!=$cookieValueArray[1]) {
            $this->log->warn("getCookieValues: token is not alphanumeric");
            return false;
        }
        if (preg_replace('/[^\w\s]/','',$cookieValueArray[2])!=$cookieValueArray[2]) {
            $this->log->warn("getCookieValues: token is not alphanumeric");
            return false;
        }
        
        // Create cookie value object
        $cookieValues = new stdClass();
        $cookieValues->userid = (int) $cookieValueArray[0];
        $cookieValues->token = $cookieValueArray[1];
        $cookieValues->persistentToken = $cookieValueArray[2];
        
        return $cookieValues;
    }
 
    // ---------------------------------------------------------------------------------------------------------
    private function findTriplet($cookieValues) {
        //$this->log->info("findTriplet");
        
        if (!$stmt = $stmt = $this->mysqli->prepare("SELECT token FROM rememberme WHERE userid=? AND persistentToken=? LIMIT 1")) {
            $this->log->warn("findTriplet schema fail");
            return self::TRIPLET_NOT_FOUND;
        }
        
        $sha1_persistentToken = sha1($cookieValues->persistentToken);
        $stmt->bind_param("is",$cookieValues->userid,$sha1_persistentToken);
        if (!$stmt->execute()) {
            $this->log->warn("findTriplet sql fail");
        }
        $stmt->bind_result($sha1_token);
        $stmt->fetch();
        $stmt->close();
        
        // sha1 of token match: triplet found
        if ($sha1_token==sha1($cookieValues->token)) {
            $this->log->info("findTriplet TRIPLET_FOUND");
            return self::TRIPLET_FOUND;
            
        // false will occur when there are no entries
        } else if ($sha1_token==false) {
            $this->log->info("findTriplet TRIPLET_NOT_FOUND");
            return self::TRIPLET_NOT_FOUND;
        
        // token does not match query token
        } else {
            $this->log->info("findTriplet TRIPLET_INVALID");
            return self::TRIPLET_INVALID;
        }
    }

    // ---------------------------------------------------------------------------------------------------------
    // $cookieValues has been validated
    // called from login and createCookie
    // ---------------------------------------------------------------------------------------------------------
    private function storeTriplet($cookieValues, $expire=0)
    {
        $date = date("Y-m-d H:i:s", $expire);
               
        if (!$stmt = $this->mysqli->prepare("INSERT INTO rememberme (userid, token, persistentToken, expire) VALUES (?,?,?,?)")) {
            $this->log->warn("storeTriplet schema fail");
            return false;
        }
        
        $sha1_token = sha1($cookieValues->token);
        $sha1_persistentToken = sha1($cookieValues->persistentToken);
        
        $stmt->bind_param("isss",$cookieValues->userid,$sha1_token,$sha1_persistentToken,$date);
        if ($stmt->execute()) {
            return true;
        } else {
            $this->log->warn("storeTriplet sql fail");
            return false;
        }
        $stmt->close();
    }

    // ---------------------------------------------------------------------------------------------------------
    // Clean entry of particular cookie
    // $cookieValues have been validated
    // called from login and clearCookie
    // ---------------------------------------------------------------------------------------------------------
    private function cleanTriplet($cookieValues)
    {
        if (!$stmt = $this->mysqli->prepare("DELETE FROM rememberme WHERE userid=? AND persistentToken=?")) {
            $this->log->warn("cleanTriplet schema fail");
            return false;
        }
        
        $sha1_persistentToken = sha1($cookieValues->persistentToken);
        $stmt->bind_param("is",$cookieValues->userid,$sha1_persistentToken);
        if ($stmt->execute()) {
            $this->log->info("cleanTriplet success");
            $this->cleanExpiredTriplets($cookieValues->userid);
            return true;
        } else {
            $this->log->warn("cleanTriplet sql fail");
            return false;
        }
    }

    // ---------------------------------------------------------------------------------------------------------
    // Delete all entries for a given user
    // $userid has been validated
    // called from login
    // ---------------------------------------------------------------------------------------------------------
    private function cleanAllTriplets($userid)
    {
        $this->log->info("cleanAllTriplets");
        
        $stmt = $this->mysqli->prepare("DELETE FROM rememberme WHERE userid=?");
        $stmt->bind_param("i",$userid);
        
        if ($stmt->execute()) {
            return true;
        } else {
            $this->log->warn("cleanAllTriplets sql fail");
            return false;
        }
    }

    // ---------------------------------------------------------------------------------------------------------
    // Scans through all entries for a given user to check if they have expired
    // ---------------------------------------------------------------------------------------------------------
    private function cleanExpiredTriplets($userid)
    {
        $date = date("Y-m-d H:i:s", time());
        
        $stmt = $this->mysqli->prepare("SELECT expire FROM rememberme WHERE userid=?");
        $stmt->bind_param("i",$userid);
        $stmt->execute();
        $stmt->bind_result($expire);
        
        $expire_list = array();
        while ($stmt->fetch()) $expire_list[] = $expire;
        $stmt->close();
        
        $overdue_count = 0;
        foreach ($expire_list as $expire)
        {
            $seconds_overdue = time() - strtotime($expire);
            if ($seconds_overdue>0) {
                $overdue_count++;
                $stmt = $this->mysqli->prepare("DELETE FROM rememberme WHERE userid=? AND expire=?");
                $stmt->bind_param("is",$userid,$expire);
                if (!$stmt->execute()) {
                    $this->log->warn("could not delete expired triplet $userid $expire");
                }
                $stmt->close();
            }
        }
        if ($overdue_count>0) $this->log->info("Deleted $overdue_count expired");
    }
}
