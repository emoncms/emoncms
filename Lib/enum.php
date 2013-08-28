<?php

  class ProcessArg {
    const VALUE = 0;
    const INPUTID = 1;
    const FEEDID = 2;
    const NONE = 3;
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
  }
