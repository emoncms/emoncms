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
  }
