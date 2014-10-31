<?php

  class ProcessArg {
    const VALUE = 0;
    const INPUTID = 1;
    const FEEDID = 2;
    const NONE = 3;
    const TEXT = 4;
  }

  class DataType {
    const UNDEFINED = 0;
    const REALTIME = 1;
    const DAILY = 2;
    const HISTOGRAM = 3;
  }

  class Engine {
    const MYSQL = 0;
    const TIMESTORE = 1;
    const PHPTIMESERIES = 2;
    const GRAPHITE = 3;
    const PHPTIMESTORE = 4;
    const PHPFINA = 5;
    const PHPFIWA = 6;
  }
