<?php

// Remember me implementation, thanks to Gabriel Birke's example here:
// https://github.com/gbirke/rememberme

class Rememberme {

    private $mysqli;

    /**
     * Cookie settings
     * @var string
     */
    private $cookieName = "PHP_EMONCMS";

    private $path = '/';

    private $domain = "";

    private $secure = false;

    private $httpOnly = true;

    /**
     * @var int Number of seconds in the future the cookie and storage will expire
     */
    private $expireTime =  604800; // 1 week

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

    /**
     * Additional salt to add more entropy when the tokens are stored as hashes.
     * @var type
     */
    private $salt = "";

    const TRIPLET_FOUND     =  1,
                TRIPLET_NOT_FOUND =  0,
                TRIPLET_INVALID   = -1;


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
        switch($this->findTriplet($cookieValues[0], $cookieValues[1].$this->salt, $cookieValues[2].$this->salt)) {
            case self::TRIPLET_FOUND:
                $expire = time() + $this->expireTime;
                $newToken = $this->createToken();

                // remove old triplet before creating new one, otherwise since the salt is defaulted to "" it would create
                // a new triplet with the same persistentToken in DB which will cause the next findTriplet to fail (finding the incorrect one) and remove the cookie again.
                $this->cleanTriplet($cookieValues[0], $cookieValues[2]);

                // create new cookie and register values in db - refresh token
                $this->storeTriplet($cookieValues[0], $newToken.$this->salt, $cookieValues[2].$this->salt, $expire);
                setcookie($this->cookieName,implode("|",array($cookieValues[0],$newToken, $cookieValues[2])),$expire,$this->path,"",false,true);

                $loginResult = $cookieValues[0];
                break;
            case self::TRIPLET_INVALID:
                setcookie($this->cookieName,"",time() - $this->expireTime,$this->path,"",false,true);
                $this->lastLoginTokenWasInvalid = true;
                if($this->cleanStoredTokensOnInvalidResult) {
                    $this->cleanAllTriplets($cookieValues[0]);
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
        $state = $this->findTriplet($cookieValues[0], $cookieValues[1].$this->salt, $cookieValues[2].$this->salt);
        return $state == self::TRIPLET_FOUND;
    }

    public function createCookie($userid)
    {
        $newToken = $this->createToken();
        $newPersistentToken = $this->createToken();
        $expire = time() + $this->expireTime;
        $this->storeTriplet($userid, $newToken, $newPersistentToken, $expire);
        setcookie($this->cookieName,implode("|",array($userid,$newToken.$this->salt,$newPersistentToken.$this->salt)),$expire,$this->path,"",false,true);
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
        $cookieValues = explode("|", $_COOKIE[$this->cookieName], 3);

        setcookie($this->cookieName,"",time() - $this->expireTime,$this->path,"",false,true);
        unset($_COOKIE[$this->cookieName]);

        if(!$clearFromStorage) {
                return true;
        }

        if(count($cookieValues) < 3) {
            return false;
        }
        $this->cleanTriplet($cookieValues[0], $cookieValues[2].$this->salt);
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
        $cookieValues = explode("|", $_COOKIE[$this->cookieName], 3);

        if(count($cookieValues) < 3) {
            return array();
        }

        return $cookieValues;
    }

    // Storage

    private function findTriplet($userid, $token, $persistentToken) {
        // We don't store the sha1 as binary values because otherwise we could not use
        // proper XML test data
        $sql = "SELECT IF(SHA1('$token') = token, 1, -1) AS token_match " .
                     "FROM rememberme WHERE userid = '$userid' " .
                     "AND persistentToken = SHA1('$persistentToken') LIMIT 1 ";
        $result = $this->mysqli->query($sql);
        $row = $result->fetch_array();

        if(!$row['token_match']) {
            return self::TRIPLET_NOT_FOUND;
        }
        elseif ($row['token_match'] == 1) {
            return self::TRIPLET_FOUND;
        }
        else {
            return self::TRIPLET_INVALID;
        }
    }

    private function storeTriplet($userid, $token, $persistentToken, $expire=0)
    {
            $date = date("Y-m-d H:i:s", $expire);
            $this->mysqli->query("INSERT INTO rememberme (userid, token, persistentToken, expire) VALUES ('$userid', SHA1('$token'), SHA1('$persistentToken'), '$date')");
    }

    private function cleanTriplet($userid, $persistentToken)
    {
            $this->mysqli->query("DELETE FROM rememberme WHERE userid = '$userid'  AND persistentToken = SHA1('$persistentToken')");
    }

    private function cleanAllTriplets($userid)
    {
            $this->mysqli->query("DELETE FROM rememberme WHERE userid = '$userid'");
    }

}
