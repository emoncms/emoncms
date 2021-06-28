<?php

class ProcessArg
{
    const VALUE = 0;
    const INPUTID = 1;
    const FEEDID = 2;
    const NONE = 3;
    const TEXT = 4;
    const SCHEDULEID = 5;
}

class DataType
{
    const UNDEFINED = 0;
    const REALTIME = 1;
    const DAILY = 2;
    const HISTOGRAM = 3;
}

class Engine
{
    const MYSQL = 0;
    const TIMESTORE = 1;     // Deprecated
    const PHPTIMESERIES = 2;
    const GRAPHITE = 3;      // Not included in core
    const PHPTIMESTORE = 4;  // Deprecated
    const PHPFINA = 5;
    const PHPFIWA = 6;       // Deprecated
    const VIRTUALFEED = 7;   // Virtual feed, on demand post processing
    const MYSQLMEMORY = 8;   // Mysql with MEMORY tables on RAM. All data is lost on shutdown
    const REDISBUFFER = 9;   // (internal use only) Redis Read/Write buffer, for low write mode
    const CASSANDRA = 10;    // Cassandra
    
    /**
     * returns array of all known engines
     *
     * @return array
     */
    public static function get_all()
    {
        return array(
        'MYSQL' => Engine::MYSQL,
        'TIMESTORE' => Engine::TIMESTORE,
        'PHPTIMESERIES' => Engine::PHPTIMESERIES,
        'GRAPHITE' => Engine::GRAPHITE,
        'PHPTIMESTORE' => Engine::PHPTIMESTORE,
        'PHPFINA' => Engine::PHPFINA,
        'PHPFIWA' => Engine::PHPFIWA,
        'VIRTUALFEED' => Engine::VIRTUALFEED,
        'MYSQLMEMORY' => Engine::MYSQLMEMORY,
        'REDISBUFFER' => Engine::REDISBUFFER,
        'CASSANDRA' => Engine::CASSANDRA
        );
    }
    
    /**
     * returns array of all known engines with descriptions
     *
     * @return array
     */
    public static function get_all_descriptive()
    {
        return array(
            array("id"=>Engine::PHPFINA,"description"=>"Emoncms Fixed Interval TimeSeries"),
            array("id"=>Engine::PHPTIMESERIES,"description"=>"Emoncms Variable Interval TimeSeries"),
            array("id"=>Engine::MYSQL,"description"=>"MYSQL TimeSeries"),
            array("id"=>Engine::MYSQLMEMORY,"description"=>"MYSQL Memory (RAM data lost on power off)"),
            array("id"=>Engine::CASSANDRA,"description"=>"CASSANDRA TimeSeries")
        );
    }

    /**
     * returns array of available intervals for fixed interval timeseries engines
     *
     * @return array
     */ 
    public static function available_intervals() 
    {
        return array(
            array("interval"=>10, "description"=>"10s"),
            array("interval"=>15, "description"=>"15s"),
            array("interval"=>20, "description"=>"20s"),
            array("interval"=>30, "description"=>"30s"),
            array("interval"=>60, "description"=>"60s"),
            array("interval"=>120, "description"=>"2m"),
            array("interval"=>180, "description"=>"3m"),
            array("interval"=>300, "description"=>"5m"),
            array("interval"=>600, "description"=>"10m"),
            array("interval"=>900, "description"=>"15m"),
            array("interval"=>1200, "description"=>"20m"),
            array("interval"=>1800, "description"=>"30m"),
            array("interval"=>3600, "description"=>"1h"),
            array("interval"=>7200, "description"=>"2h"),
            array("interval"=>10800, "description"=>"3h"),
            array("interval"=>14400, "description"=>"4h"),
            array("interval"=>18000, "description"=>"5h"),
            array("interval"=>21600, "description"=>"6h"),
            array("interval"=>43200, "description"=>"12h"),
            array("interval"=>86400, "description"=>"1d")
        );
    }
     
     
    /**
     * return true if given $engineid is a known
     *
     * @param [int] $engineid
     * @return boolean
     */
    public static function is_valid($engineid)
    {
        return in_array($engineid, Engine::get_all());
    }
}
