<?php

  class ProcessArg {
    const VALUE = 0;
    const INPUTID = 1;
    const FEEDID = 2;
    const NONE = 3;
    const TEXT = 4;
    const SCHEDULEID = 5;
  }

  class DataType {
    const UNDEFINED = 0;
    const REALTIME = 1;
    const DAILY = 2;
    const HISTOGRAM = 3;
  }

  class Engine {
    const MYSQL = 0;
    const TIMESTORE = 1;     // Depreciated
    const PHPTIMESERIES = 2;
    const GRAPHITE = 3;      // Not included in core
    const PHPTIMESTORE = 4;  // Depreciated
    const PHPFINA = 5;
    const PHPFIWA = 6;
    const VIRTUALFEED = 7;   // Virtual feed, on demand post processing
    const MYSQLMEMORY = 8;   // Mysql with MEMORY tables on RAM. All data is lost on shutdown 
    const REDISBUFFER = 9;   // (internal use only) Redis Read/Write buffer, for low write mode
    const CASSANDRA = 10;    // Cassandra
    
    /**
     * returns array of all known engines
     *
     * @return array
     */
    static public function get_all () {
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
     * return true if given $engineid is a known
     *
     * @param [int] $engineid
     * @return boolean
     */
    static public function is_valid ($engineid) {
      return in_array($engineid, Engine::get_all());
    }
  }
