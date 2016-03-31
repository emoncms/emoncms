<?php

class Rememberme {

    private $mysqli;

    // Cookie settings
    private $cookieName = "EMONCMS_REMEMBERME";
    private $path = '/';
    private $domain = "";
    private $secure = false;
    private $httpOnly = false;

    // Number of seconds in the future the cookie and storage will expire
    private $expireTime =  7776000; // 3 months

    // If the return from the storage was TOKEN_INVALID, this is set to true
    protected $lastLoginTokenWasInvalid = false;

    // If the login token was invalid, delete all login tokens of this user
    protected $cleanStoredTokensOnInvalidResult = true;

    // Additional salt to add more entropy when the tokens are stored as hashes
    private $salt = "";

    const TOKEN_VALID    =  1,
          TOKEN_INVALID  =  0,
          TOKEN_EXPIRED  = -1;

    public function __construct($mysqli)
    {
            $this->mysqli = $mysqli;
    }

    /**
     * Check Credentials from cookie
     * @return bool|string False if login was not successful, credential string if it was successful
     */
    public function login() {
        $cookieValues = $this->getCookieValues();
        if(!$cookieValues) {
            return false;
        }
        $loginResult = false;
        switch($this->findToken($cookieValues[0], $cookieValues[1].$this->salt)) {
            case self::TOKEN_VALID:
                $expire = time() + $this->expireTime;
                // update expire date
                $this->updateTokenExpire($cookieValues[0], $cookieValues[1].$this->salt, $expire);
                setcookie($this->cookieName,implode("|",array($cookieValues[0],$cookieValues[1].$this->salt)),$expire,$this->path,$this->domain,$this->secure,$this->httpOnly);

                $loginResult = $cookieValues[0];
                break;
            case self::TOKEN_INVALID:
                // Invalid tokens are impossible on normal use, can be an hack attemp so remove all token for that user.
                setcookie($this->cookieName,"",time() - $this->expireTime,$this->path,$this->domain,$this->secure,$this->httpOnly);
                $this->lastLoginTokenWasInvalid = true;
                if($this->cleanStoredTokensOnInvalidResult) {
                    $this->deleteTokenUser($cookieValues[0]);
                }
                break;
        }
        return $loginResult;
    }

    public function cookieIsValid($userid) {
        $cookieValues = $this->getCookieValues();
        if(!$cookieValues) {
            return false;
        }
        $state = $this->findToken($cookieValues[0], $cookieValues[1].$this->salt);
        return $state == self::TOKEN_VALID;
    }

    public function createCookie($userid)
    {
        $newToken = $this->createToken();
        $expire = time() + $this->expireTime;
        $this->storeToken($userid, $newToken.$this->salt, $expire);
        setcookie($this->cookieName,implode("|",array($userid,$newToken)),$expire,$this->path,$this->domain,$this->secure,$this->httpOnly);
    }

    /**
     * Expire the rememberme cookie, unset $_COOKIE[$this->cookieName] value and
     * remove current login triplet from storage.
     *
     * @param boolean $clearFromStorage
     * @return boolean
     */
    public function clearCookie($clearFromStorage=true) {
        if(empty($_COOKIE[$this->cookieName]))
            return false;
        $cookieValues = explode("|", $_COOKIE[$this->cookieName], 2);

        setcookie($this->cookieName,"",time() - $this->expireTime,$this->path,$this->domain,$this->secure,$this->httpOnly);
        unset($_COOKIE[$this->cookieName]);

        if(!$clearFromStorage) {
                return true;
        }

        if(count($cookieValues) < 2) {
            return false;
        }
        $this->deleteToken($cookieValues[0], $cookieValues[1].$this->salt);
        return true;
    }

    public function getCookieName() {
        return $this->cookieName;
    }

    public function loginTokenWasInvalid() {
        return $this->lastLoginTokenWasInvalid;
    }

    /**
     * Create a pseudo-random token.
     *
     * The token is pseudo-random. If you need better security, read from /dev/urandom
     */
    private function createToken() {
        return md5(uniqid(mt_rand(), true));
    }

    private function getCookieValues()
    {
        // Cookie was not sent with incoming request
        if(empty($_COOKIE[$this->cookieName])) {
            return array();
        }
        $cookieValues = explode("|", $_COOKIE[$this->cookieName], 2);

        if(count($cookieValues) < 2) {
            return array();
        }

        return $cookieValues;
    }

    // Storage
    private function findToken($userid, $token) {
        // We don't store the sha1 as binary values because otherwise we could not use
        // proper XML test data
        $now = date("Y-m-d H:i:s", time());
        $sql = "SELECT IF('$now' > expire, 1, -1) AS token_expired " .
               "FROM rememberme WHERE userid='$userid' and SHA1('$token') = token";
                     
        $result = $this->mysqli->query($sql);
        $row = $result->fetch_array();
        if(count($result) != 1) {
            return self::TOKEN_INVALID;
        }
        elseif ($row['token_expired'] == 1) {
            return self::TOKEN_EXPIRED;
        }
        else {
            return self::TOKEN_VALID;
        }
    }

    private function storeToken($userid, $token, $expire) {
            $date = date("Y-m-d H:i:s", $expire);
            $this->mysqli->query("INSERT INTO rememberme (userid, token, expire) VALUES ('$userid', SHA1('$token'), '$date')");
    }

    private function updateTokenExpire($userid, $token, $expire) {
            $date = date("Y-m-d H:i:s", $expire);
            $this->mysqli->query("UPDATE rememberme SET expire='$date' WHERE userid='$userid' AND token=SHA1('$token')");
    }
    
    private function deleteToken($userid,$token) {
            $this->mysqli->query("DELETE FROM rememberme WHERE userid='$userid' AND token=SHA1('$token')");
    }

    private function deleteTokenUser($userid) {
            $this->mysqli->query("DELETE FROM rememberme WHERE userid='$userid'");
    }

}
