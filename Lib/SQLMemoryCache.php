<?php

// CREATE TABLE `cache` (`key` varchar(32) NOT NULL PRIMARY KEY, `val` varchar(256) NOT NULL DEFAULT '') ENGINE=MEMORY;
/*
$mysqli = new mysqli("localhost","emoncms","emoncms","emoncms",3306);
$cache = new SQLMemoryCache($mysqli);

$cache->set("test","hello world");
print $cache->get("test");

$cache->hMSet("test",array("name"=>"power1","time"=>123,"value"=>100));
$cache->hMSet("test",array("time"=>234,"value"=>100));

print json_encode($cache->hGetAll("test"));

$cache->sAdd("amem2",100);
$cache->sAdd("amem2",120);
print $cache->get("amem2");
print json_encode($cache->sMembers("amem2"));
*/

class SQLMemoryCache
{
    private $pipe = false;
    private $pipe_results = array();
    
    public function __construct($mysqli)
    {
        // $this->log = new EmonLogger(__FILE__);
        $this->mysqli = $mysqli;
    }
    
    public function set($key,$val) {
        $stmt = $this->mysqli->prepare("INSERT INTO cache SET `key` = ?, `val` = ? ON DUPLICATE KEY UPDATE `val` = ?");
        $stmt->bind_param("sss",$key,$val,$val);
        $stmt->execute();
        $stmt->close();
    }
    
    public function get($key) {
        $stmt = $this->mysqli->prepare("SELECT val FROM cache WHERE `key` = ? LIMIT 1");
        $stmt->bind_param("s",$key);
        $stmt->execute();
        $stmt->bind_result($val);
        $result = $stmt->fetch();
        $stmt->close();
        if ($result) {
            return $val;
        } else {
            return null;
        }
    }
    
    public function del($key) {
        $stmt = $this->mysqli->prepare("DELETE FROM cache WHERE `key` = ?");
        $stmt->bind_param("s",$key);
        $stmt->execute();
        $stmt->close();
    }
    
    public function exists($key) {
        $stmt = $this->mysqli->prepare("SELECT val FROM cache WHERE `key` = ? LIMIT 1");
        $stmt->bind_param("s",$key);
        $stmt->execute();
        $stmt->bind_result($val);
        $result = $stmt->fetch();
        $stmt->close();
        if ($result) {
            return true;
        } else {
            return false;
        } 
    }
    
    public function sMembers($key) {
        $csv = $this->get($key);
        if ($csv!=null && strlen($csv)) {
            $members = explode(",",$csv);
        } else {
            $members = array();
        }
        for ($i=0; $i<count($members); $i++) {
            if (is_numeric($members[$i])) {
                $members[$i] = 1*$members[$i];
            }
        }
        return $members;
    }
    
    public function sAdd($key,$value) {
        $members = $this->sMembers($key);
        if (!in_array($value,$members)) {
            $members[] = $value;
        }
        $this->set($key,implode(",",$members));
    }
    
    public function srem($key,$value) {
        $members = $this->sMembers($key);
        $new = array();
        for ($i=0; $i<count($members); $i++) {
            if ($members[$i]!=$value) {
                $new[] = $members[$i];
            }
        }
        $this->set($key,implode(",",$new));
    }
    
    public function multi() {
        $this->pipe = true;
        $this->pipe_results = array();
        return $this;
    }
    
    public function exec() {
        $result = $this->pipe_results;
        $this->pipe_results = array();
        $this->pipe = false;
        return $result;
    }

    public function hMSet($key,$properties) {
        $existing = $this->hGetAll($key);
        $new = array_merge($existing,$properties);
        $val = json_encode($new);
        $this->set($key,$val);
    }

    public function hMGet($key,$properties) {
        $existing = $this->hGetAll($key);
        $out = array();
        foreach ($properties as $prop) {
            if (isset($existing[$prop])) {
                $out[$prop] = $existing[$prop];
            } else {
                $out[$prop] = null;
            }
        }
        return $out;
    }
    
    public function hExists($key,$field) {
        $array = $this->hGetAll($key);
        if (isset($array[$field])) {
            return true;
        } else {
            return false;
        } 
    }
    
    public function hget($key,$field) {
        $array = $this->hGetAll($key);
        if (isset($array[$field])) {
            return $array[$field];
        } else {
            return null;
        }
    }
    
    public function hset($key,$field,$value) {
        $this->hMSet($key,array($field=>$value));
    }
    
    public function hGetAll($key) {
        $json = $this->get($key);
        if ($json!=null) {
            $array = json_decode($json, true);
            if (!is_array($array)) $array = array();
        } else {
            $array = array();
        }
        
        if ($this->pipe) {
            $this->pipe_results[] = $array;
        } else {
            return $array;
        }
    }
}
