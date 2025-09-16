<?php

/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.
 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project: http://openenergymonitor.org
 */

// no direct access
use Mosquitto\Client;

defined('EMONCMS_EXEC') or die('Restricted access');

// This is core Process list module
class Process_ProcessList
{
    private $mysqli;
    private $input;
    private $feed;
    private $timezone;

    private $proc_initialvalue;  // save the input value at beginning of the processes list execution
    private $proc_skip_next;     // skip execution of next process in process list
    private $proc_goto;          // goto step in process list

    private $log;
    private $mqtt = false;

    private $data_cache = array();

    // Module required constructor, receives parent as reference
    public function __construct($parent)
    {
        $this->mysqli = &$parent->mysqli;
        $this->input = &$parent->input;
        $this->feed = &$parent->feed;
        $this->timezone = &$parent->timezone;
        $this->proc_initialvalue = &$parent->proc_initialvalue;
        $this->proc_skip_next = &$parent->proc_skip_next;
        $this->proc_goto = &$parent->proc_goto;

        $this->log = new EmonLogger(__FILE__);

        // Load MQTT if enabled
        // Publish value to MQTT topic, see: http://openenergymonitor.org/emon/node/5943
        global $settings, $log;

        if ($settings['mqtt']['enabled'] && !$this->mqtt) {
            // @see: https://github.com/emoncms/emoncms/blob/master/docs/RaspberryPi/MQTT.md
            if (class_exists(Client::class)) {
                /*
                    new Mosquitto\Client($id,$cleanSession)
                    $id (string) – The client ID. If omitted or null, one will be generated at random.
                    $cleanSession (boolean) – Set to true to instruct the broker to clean all messages and subscriptions on disconnect. Must be true if the $id parameter is null.
                 */
                $mqtt_client = new Mosquitto\Client(null, true);

                $mqtt_client->onDisconnect(function ($responseCode) use ($log) {
                    if ($responseCode > 0) {
                        $log->info('unexpected disconnect from mqtt server');
                    }
                });

                $this->mqtt = $mqtt_client;
            }
        }
    }

    public function process_list()
    {

        textdomain("process_messages");

        return array(
            array(
                "id_num" => 1,
                "name" => tr("Log to feed"),
                "short" => "log",
                "function" => "log_to_feed",
                "args" => array(
                    array(
                        "type" => ProcessArg::FEEDID,
                        "engines" => array(Engine::PHPFINA, Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY, Engine::CASSANDRA)
                    ),
                ),
                "group" => tr("Main"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>This processor logs to a timeseries feed which can then be used to explore historic data. This is recommended for logging power, temperature, humidity, voltage and current data.</p><p><b>Feed engine:</b><ul><li><b>Emoncms Fixed Interval TimeSeries (PHPFina)</b> is the recommended feed engine, it is a fixed interval timeseries engine.</li><li><b>Emoncms Variable Interval TimeSeries (PHPTimeseries)</b> is for data posted at a non regular interval.</li></ul></p><p><b>Feed interval:</b> When selecting the feed interval select an interval that is the same as, or longer than the update rate that is set in your monitoring equipment. Setting the interval rate to be shorter than the update rate of the equipment causes un-needed disk space to be used up.</p>")
            ),
            array(
                "id_num" => 2,
                "name" => tr("x"),
                "short" => "x",
                "function" => "scale",
                "args" => array(
                    array(
                        "type" => ProcessArg::VALUE,
                        "default" => 1
                    ),
                ),
                "group" => tr("Calibration"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>Multiplies current value by given constant. This can be useful for calibrating a particular variable on the web rather than by reprogramming hardware.</p>")
            ),
            array(
                "id_num" => 3,
                "name" => tr("+"),
                "short" => "+",
                "function" => "offset",
                "args" => array(
                    array(
                        "type" => ProcessArg::VALUE,
                        "default" => 0
                    ),
                ),
                "group" => tr("Calibration"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>Offset current value by given value. This can again be useful for calibrating a particular variable on the web rather than by reprogramming hardware.</p>")
            ),
            array(
                "id_num" => 4,
                "name" => tr("Power to kWh"),
                "short" => "kwh",
                "function" => "power_to_kwh",
                "args" => array(
                    array(
                        "type" => ProcessArg::FEEDID,
                        "engines" => array(Engine::PHPFINA, Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY)
                    ),
                ),
                "unit" => "kWh",
                "group" => tr("Main"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Convert a power value in Watts to a cumulative kWh feed.<br><br><b>Visualisation tip:</b> Feeds created with this input processor can be used to generate daily kWh data using the BarGraph visualisation with the delta property set to 1. See <a href='https://guide.openenergymonitor.org/setup/daily-kwh/' target='_blank' rel='noopener'>Guide: Daily kWh</a><br><br>")
            ),
            array(
                "id_num" => 5,
                "name" => tr("Power to kWh/d"),
                "short" => "kwhd",
                "argtype" => ProcessArg::FEEDID,
                "function" => "power_to_kwhd",
                "unit" => "kWhd",
                "group" => tr("Power & Energy"),
                "engines" => array(Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Convert a power value in Watts to a feed that contains an entry for the total energy used each day (kWh/d)</p>")
            ),
            array(
                "id_num" => 6,
                "name" => tr("x input"),
                "short" => "x inp",
                "argtype" => ProcessArg::INPUTID,
                "function" => "times_input",
                "group" => tr("Input"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Multiplies the current value with the last value from other input as selected from the input list.</p>")
            ),
            array(
                "id_num" => 7,
                "name" => tr("Input on-time"),
                "short" => "ontime",
                "argtype" => ProcessArg::FEEDID,
                "function" => "input_ontime",
                "group" => tr("Input"),
                "engines" => array(Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Counts the amount of time that an input is high in each day and logs the result to a feed. Created for counting the number of hours a solar hot water pump is on each day</p>")
            ),
            array(
                "id_num" => 8,
                "name" => tr("Wh increments to kWh/d"),
                "short" => "whinckwhd",
                "argtype" => ProcessArg::FEEDID,
                "function" => "whinc_to_kwhd",
                "unit" => "kWhd",
                "group" => tr("Power & Energy"),
                "engines" => array(Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Accumulate Wh measurements into kWh/d.<p><b>Input</b>: energy increments in Wh.</p>")
            ),
            array(
                "deleted" => true,
                "id_num" => 9,
                "name" => tr("kWh to kWh/d (OLD)"),
                "short" => "kwhkwhdold",
                "argtype" => ProcessArg::FEEDID,
                "function" => "kwh_to_kwhd_old",
                "unit" => "kWhd",
                "group" => tr("Deleted"),
                "engines" => array(Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => ""
            ),
            array(
                "id_num" => 10,
                "name" => tr("Update feed at day"),
                "short" => "update",
                "argtype" => ProcessArg::FEEDID,
                "function" => "update_feed_data",
                "group" => tr("Input"),
                "engines" => array(Engine::MYSQL, Engine::MYSQLMEMORY),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Updates or inserts daily value on the specified time (given by the JSON time parameter from the API) of the specified feed</p>")
            ),
            array(
                "id_num" => 11,
                "name" => tr("+ input"),
                "short" => "+ inp",
                "argtype" => ProcessArg::INPUTID,
                "function" => "add_input",
                "group" => tr("Input"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Adds the current value with the last value from other input as selected from the input list. The result is passed back for further processing by the next processor in the processing list.</p>")
            ),
            array(
                "id_num" => 12,
                "name" => tr("/ input"),
                "short" => "/ inp",
                "argtype" => ProcessArg::INPUTID,
                "function" => "divide_input",
                "group" => tr("Input"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Divides the current value with the last value from other input as selected from the input list. The result is passed back for further processing by the next processor in the processing list.</p>")
            ),
            array(
                "id_num" => 13,
                "name" => tr("Phaseshift"),
                "short" => "phaseshift",
                "argtype" => ProcessArg::VALUE,
                "function" => "phaseshift",
                "group" => tr("Deleted"),
                "deleted" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => ""
            ),
            array(
                "id_num" => 14,
                "name" => tr("Accumulator"),
                "short" => "accumulate",
                "argtype" => ProcessArg::FEEDID,
                "function" => "accumulator",
                "group" => tr("Misc"),
                "engines" => array(Engine::PHPFINA, Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Output feed accumulates by input value</p>")
            ),
            array(
                "id_num" => 15,
                "name" => tr("Rate of change"),
                "short" => "rate",
                "argtype" => ProcessArg::FEEDID,
                "function" => "ratechange",
                "group" => tr("Misc"),
                "engines" => array(Engine::PHPFINA, Engine::PHPTIMESERIES),
                "requireredis" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Output feed is the difference between the current value and the last</p>")
            ),
            array(
                "id_num" => 16,
                "name" => tr("Histogram"),
                "short" => "hist",
                "argtype" => ProcessArg::FEEDID,
                "function" => "histogram",
                "group" => tr("Deleted"),
                "deleted" => true,
                "engines" => array(Engine::MYSQL, Engine::MYSQLMEMORY),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => ""
            ),
            array(
                "id_num" => 17,
                "name" => tr("Daily Average"),
                "short" => "mean",
                "argtype" => ProcessArg::FEEDID,
                "function" => "average",
                "group" => tr("Deleted"),
                "deleted" => true,
                "engines" => array(Engine::PHPTIMESERIES),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => ""
            ),
            array(
                "id_num" => 18,
                "name" => tr("Heat flux"),
                "short" => "flux",
                "argtype" => ProcessArg::FEEDID,
                "function" => "heat_flux",
                "group" => tr("Deleted"),
                "deleted" => true,
                "engines" => array(Engine::PHPFINA, Engine::PHPTIMESERIES),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => ""
            ),
            array(
                "id_num" => 19,
                "name" => tr("Power gained to kWh/d"),
                "short" => "pwrgain",
                "argtype" => ProcessArg::FEEDID,
                "function" => "power_acc_to_kwhd",
                "unit" => "kWhd",
                "group" => tr("Deleted"),
                "deleted" => true,
                "engines" => array(Engine::PHPTIMESERIES),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => ""
            ),
            array(
                "id_num" => 20,
                "name" => tr("Total pulse count to pulse increment"),
                "short" => "pulsdiff",
                "argtype" => ProcessArg::FEEDID,
                "function" => "pulse_diff",
                "group" => tr("Pulse"),
                "engines" => array(Engine::PHPFINA, Engine::PHPTIMESERIES),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Returns the number of pulses incremented since the last update for a input that is a cumulative pulse count. i.e If the input updates from 23400 to 23410 the result will be an incremenet of 10.</p>")
            ),
            array(
                "id_num" => 21,
                "name" => tr("kWh to Power"),
                "short" => "kwhpwr",
                "argtype" => ProcessArg::FEEDID,
                "function" => "kwh_to_power",
                "unit" => "W",
                "group" => tr("Power & Energy"),
                "engines" => array(Engine::PHPFINA, Engine::PHPTIMESERIES),
                "requireredis" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Convert accumulating kWh to instantaneous power</p>")
            ),
            array(
                "id_num" => 22,
                "name" => tr("- input"),
                "short" => "- inp",
                "argtype" => ProcessArg::INPUTID,
                "function" => "subtract_input",
                "group" => tr("Input"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Subtracts from the current value the last value from other input as selected from the input list.</p>")
            ),
            array(
                "id_num" => 23,
                "name" => tr("kWh to kWh/d"),
                "short" => "kwhkwhd",
                "argtype" => ProcessArg::FEEDID,
                "function" => "kwh_to_kwhd",
                "unit" => "kWhd",
                "group" => tr("Power & Energy"),
                "engines" => array(Engine::PHPTIMESERIES),
                "requireredis" => true,
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Upsert kWh to a daily value.</p>")
            ),
            array(
                "id_num" => 24,
                "name" => tr("Allow positive"),
                "short" => "> 0",
                "argtype" => ProcessArg::NONE,
                "function" => "allowpositive",
                "group" => tr("Limits"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>Negative values are zeroed for further processing by the next processor in the processing list.</p>")
            ),
            array(
                "id_num" => 25,
                "name" => tr("Allow negative"),
                "short" => "< 0",
                "argtype" => ProcessArg::NONE,
                "function" => "allownegative",
                "group" => tr("Limits"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>Positive values are zeroed for further processing by the next processor in the processing list.</p>")
            ),
            array(
                "id_num" => 26,
                "name" => tr("Signed to unsigned"),
                "short" => "unsign",
                "argtype" => ProcessArg::NONE,
                "function" => "signed2unsigned",
                "unit" => "unsign",
                "group" => tr("Misc"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>Convert a number that was interpreted as a 16 bit signed number to an unsigned number.</p>")
            ),
            array(
                "id_num" => 27,
                "name" => tr("Max daily value"),
                "short" => "max",
                "argtype" => ProcessArg::FEEDID,
                "function" => "max_value",
                "group" => tr("Misc"),
                "engines" => array(Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Maximal daily value. Upserts on the selected daily feed the highest value reached each day.</p>")
            ),
            array(
                "id_num" => 28,
                "name" => tr("Min daily value"),
                "short" => "min",
                "argtype" => ProcessArg::FEEDID,
                "function" => "min_value",
                "group" => tr("Misc"),
                "engines" => array(Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Minimal daily value. Upserts on the selected daily feed the lowest value reached each day.</p>")
            ),
            array(
                "id_num" => 29,
                "name" => tr("+ feed"),
                "short" => "+ feed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "add_feed",
                "group" => tr("Feed"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Adds the current value with the last value from a feed as selected from the feed list.</p>")
            ),
            array(
                "id_num" => 30,
                "name" => tr("- feed"),
                "short" => "- feed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "sub_feed",
                "group" => tr("Feed"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Subtracts from the current value the last value from a feed as selected from the feed list.</p>")
            ),
            array(
                "id_num" => 31,
                "name" => tr("* feed"),
                "short" => "x feed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "multiply_by_feed",
                "group" => tr("Feed"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Multiplies the current value with the last value from a feed as selected from the feed list.</p>")
            ),
            array(
                "id_num" => 32,
                "name" => tr("/ feed"),
                "short" => "/ feed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "divide_by_feed",
                "group" => tr("Feed"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Divides the current value by the last value from a feed as selected from the feed list.</p>")
            ),
            array(
                "id_num" => 33,
                "name" => tr("Reset to ZERO"),
                "short" => "0",
                "argtype" => ProcessArg::NONE,
                "function" => "reset2zero",
                "group" => tr("Misc"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>The value \"0\" is passed back for further processing by the next processor in the processing list.</p>")
            ),
            array(
                "id_num" => 34,
                "name" => tr("Wh Accumulator"),
                "short" => "whacc",
                "argtype" => ProcessArg::FEEDID,
                "function" => "wh_accumulator",
                "unit" => "Wh",
                "group" => tr("Main"),
                "engines" => array(Engine::PHPFINA, Engine::PHPTIMESERIES),
                "requireredis" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("Use with emontx, emonth or emonpi pulsecount or an emontx running firmware <i>emonTxV3_4_continuous_kwhtotals</i> sending cumulative watt hours.<br><br>This processor ensures that when the emontx is reset the watt hour count in emoncms does not reset, it also checks filter's out spikes in energy use that are larger than a max power threshold set in the processor, assuming these are error's, the max power threshold is set to 60 kW. <br><br><b>Visualisation tip:</b> Feeds created with this input processor can be used to generate daily kWh data using the BarGraph visualisation with the delta property set to 1 and scale set to 0.001. See: <a href='https://guide.openenergymonitor.org/setup/daily-kwh/' target='_blank' rel='noopener'>Guide: Daily kWh</a><br><br>")
            ),
            array(
                "id_num" => 35,
                "name" => tr("Publish to MQTT via Redis"),
                "short" => "mqtt",
                "argtype" => ProcessArg::TEXT,
                "function" => "publish_to_mqtt",
                "group" => tr("Misc"),
                "nochange" => true,
                "requireredis" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Publishes value to REDIS for phpmqtt_input.php to publish the values to MQTT topic e.g. 'home/power/kitchen'</p>")
            ),
            array(
                "id_num" => 36,
                "name" => tr("Reset to NULL"),
                "short" => "null",
                "argtype" => ProcessArg::NONE,
                "function" => "reset2null",
                "group" => tr("Misc"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>Value is set to NULL.</p><p>Useful for conditional process to work on.</p>")
            ),
            array(
                "id_num" => 37,
                "name" => tr("Reset to Original"),
                "short" => "ori",
                "argtype" => ProcessArg::NONE,
                "function" => "reset2original",
                "group" => tr("Misc"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>The value is set to the original value at the start of the process list.</p>")
            ),
            array(
                "id_num" => 42,
                "name" => tr("If ZERO, skip next"),
                "short" => "0? skip",
                "argtype" => ProcessArg::NONE,
                "function" => "if_zero_skip",
                "group" => tr("Conditional"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value from last process is ZERO, process execution will skip execution of next process in list.</p>")
            ),
            array(
                "id_num" => 43,
                "name" => tr("If !ZERO, skip next"),
                "short" => "!0? skip",
                "argtype" => ProcessArg::NONE,
                "function" => "if_not_zero_skip",
                "group" => tr("Conditional"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value from last process is NOT ZERO, process execution will skip execution of next process in list.</p>")
            ),
            array(
                "id_num" => 44,
                "name" => tr("If NULL, skip next"),
                "short" => "N? skip",
                "argtype" => ProcessArg::NONE,
                "function" => "if_null_skip",
                "group" => tr("Conditional"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value from last process is NULL, process execution will skip execution of next process in list.</p>")
            ),
            array(
                "id_num" => 45,
                "name" => tr("If !NULL, skip next"),
                "short" => "!N? skip",
                "argtype" => ProcessArg::NONE,
                "function" => "if_not_null_skip",
                "group" => tr("Conditional"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value from last process is NOT NULL, process execution will skip execution of next process in list.</p>")
            ),
            array(
                "id_num" => 46,
                "name" => tr("If >, skip next"),
                "short" => ">? skip",
                "argtype" => ProcessArg::VALUE,
                "function" => "if_gt_skip",
                "group" => tr("Conditional - User value"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value from last process is greater than the specified value, process execution will skip execution of next process in list.</p>")
            ),
            array(
                "id_num" => 47,
                "name" => tr("If >=, skip next"),
                "short" => ">=? skip",
                "argtype" => ProcessArg::VALUE,
                "function" => "if_gt_equal_skip",
                "group" => tr("Conditional - User value"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value from last process is greater or equal to the specified value, process execution will skip execution of next process in list.</p>")
            ),
            array(
                "id_num" => 48,
                "name" => tr("If <, skip next"),
                "short" => "<? skip",
                "argtype" => ProcessArg::VALUE,
                "function" => "if_lt_skip",
                "group" => tr("Conditional - User value"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value from last process is lower than the specified value, process execution will skip execution of next process in list.</p>")
            ),
            array(
                "id_num" => 49,
                "name" => tr("If <=, skip next"),
                "short" => "<=? skip",
                "argtype" => ProcessArg::VALUE,
                "function" => "if_lt_equal_skip",
                "group" => tr("Conditional - User value"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value from last process is lower or equal to the specified value, process execution will skip execution of next process in list.</p>")
            ),
            array(
                "id_num" => 50,
                "name" => tr("If =, skip next"),
                "short" => "=? skip",
                "argtype" => ProcessArg::VALUE,
                "function" => "if_equal_skip",
                "group" => tr("Conditional - User value"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value from last process is equal to the specified value, process execution will skip execution of next process in list.</p>")
            ),
            array(
                "id_num" => 51,
                "name" => tr("If !=, skip next"),
                "short" => "!=? skip",
                "argtype" => ProcessArg::VALUE,
                "function" => "if_not_equal_skip",
                "group" => tr("Conditional - User value"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value from last process is NOT equal to the specified value, process execution will skip execution of next process in list.</p>")
            ),
            array(
                "id_num" => 52,
                "name" => tr("GOTO"),
                "short" => "GOTO",
                "argtype" => ProcessArg::VALUE,
                "function" => "goto_process",
                "default" => 1,
                "group" => tr("Misc"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>Jumps the process execution to the specified position.</p><p><b>Warning</b><br>If you're not careful you can create a goto loop on the process list.<br>When a loop occurs, the API will appear to lock until the server php times out with an error.</p>")
            ),
            array(
                "id_num" => 53,
                "name" => tr("Source Feed"),
                "short" => "sfeed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "source_feed_data_time",
                "group" => tr("Virtual"),
                "input_context" => false,
                "virtual_feed_context" => true,
                "description" => tr("<p><b>Source Feed:</b><br>Virtual feeds should use this processor as the first one in the process list. It sources data from the selected feed.<br>The sourced value is passed back for further processing by the next processor in the processing list.<br>You can then add other processors to apply logic on the passed value for post-processing calculations in realtime.</p><p>Note: This virtual feed process list is executed on visualizations requests that use this virtual feed.</p>")
            ),
            array(
                "id_num" => 55,
                "name" => tr("+ source feed"),
                "short" => "+ sfeed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "add_source_feed",
                "group" => tr("Virtual"),
                "input_context" => false,
                "virtual_feed_context" => true,
                "description" => tr("<p>Add the specified feed.</p>")
            ),
            array(
                "id_num" => 56,
                "name" => tr("- source feed"),
                "short" => "- sfeed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "sub_source_feed",
                "group" => tr("Virtual"),
                "input_context" => false,
                "virtual_feed_context" => true,
                "description" => tr("<p>Subtract the specified feed.</p>")
            ),
            array(
                "id_num" => 57,
                "name" => tr("* source feed"),
                "short" => "x sfeed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "multiply_by_source_feed",
                "group" => tr("Virtual"),
                "input_context" => false,
                "virtual_feed_context" => true,
                "description" => tr("<p>Multiply by specified feed.</p>")
            ),
            array(
                "id_num" => 58,
                "name" => tr("/ source feed"),
                "short" => "/ sfeed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "divide_by_source_feed",
                "group" => tr("Virtual"),
                "input_context" => false,
                "virtual_feed_context" => true,
                "description" => tr("<p>Divide by specified feed. Returns NULL for zero values.</p>")
            ),
            array(
                "id_num" => 59,
                "name" => tr("/ source feed"),
                "short" => "/ sfeed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "reciprocal_by_source_feed",
                "group" => tr("Virtual"),
                "input_context" => false,
                "virtual_feed_context" => true,
                "description" => tr("<p>Return the reciprical of the specified feed. Returns NULL for zero values.</p>")
            ),
            array(
                // "id_num" => 60,
                "name" => tr("EXIT"),
                "short" => "EXIT",
                "argtype" => ProcessArg::NONE,
                "function" => "error_found",
                "group" => tr("Hidden"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>This was automaticaly added when a loop error was discovered on the processList or execution took too many steps to process.  Review the usage of GOTOs or decrease the number of items and delete this entry to resume execution.</p>"),
                "internalerror" => true,
                "internalerror_reason" => "HAS ERRORS",
                "internalerror_desc" => "Processlist disabled due to errors found during execution."
            ),
            array(
                "id_num" => 61,
                "name" => tr("Max value allowed"),
                "short" => "<max",
                "argtype" => ProcessArg::VALUE,
                "function" => "max_value_allowed",
                "group" => tr("Limits"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value is greater than <i>max value allowed</i> then the value passed to following process will be the <i>max value allowed</i></p>"),
                "requireredis" => false,
                "nochange" => false
            ),
            array(
                "id_num" => 62,
                "name" => tr("Min value allowed"),
                "short" => ">min",
                "argtype" => ProcessArg::VALUE,
                "function" => "min_value_allowed",
                "group" => tr("Limits"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>If value is lower than <i>min value allowed</i> then the value passed to following process will be the <i>min value allowed</i></p>"),
                "requireredis" => false,
                "nochange" => false
            ),
            array(
                "id_num" => 63,
                "name" => tr("Absolute value"),
                "short" => "abs",
                "argtype" => ProcessArg::VALUE,
                "function" => "abs_value",
                "group" => tr("Calibration"),
                "input_context" => true,
                "virtual_feed_context" => true,
                "description" => tr("<p>Return the absolute value of the current value. This can be useful for calibrating a particular variable on the web rather than by reprogramming hardware.</p>")
            ),
            array(
                "id_num" => 64,
                "name" => tr("kWh Accumulator"),
                "short" => "kwhacc",
                "argtype" => ProcessArg::FEEDID,
                "function" => "kwh_accumulator",
                "unit" => "kWh",
                "group" => tr("Main"),
                "engines" => array(Engine::PHPFINA, Engine::PHPTIMESERIES),
                "requireredis" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("This processor removes resets from a cumulative kWh input, it also filter's out spikes in energy use that are larger than a max power threshold set in the processor, assuming these are error's, the max power threshold is set to 60 kW. <br><br><b>Visualisation tip:</b> Feeds created with this input processor can be used to generate daily kWh data using the BarGraph visualisation with the delta property set to 1 and scale set to 0.001. See: <a href='https://guide.openenergymonitor.org/setup/daily-kwh/' target='_blank' rel='noopener'>Guide: Daily kWh</a><br><br>")
            ),
            array(
                "id_num" => 65,
                "name" => tr("Log to feed (Join)"),
                "short" => "log_join",
                "argtype" => ProcessArg::FEEDID,
                "function" => "log_to_feed_join",
                "group" => tr("Main"),
                "engines" => array(Engine::PHPFINA, Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY, Engine::CASSANDRA),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>In addition to the standard log to feed process, this process links missing data points with a straight line between the newest value and the previous value. It is designed for use with total cumulative kWh meter reading inputs, producing a feed that can be used with the delta property when creating bar graphs. See: <a href='https://guide.openenergymonitor.org/setup/daily-kwh/' target='_blank' rel='noopener'>Guide: Daily kWh</a><br><br>")
            ),
            array(
                "id_num" => 66,
                "name" => tr("max by input"),
                "short" => "max_inp",
                "argtype" => ProcessArg::INPUTID,
                "function" => "max_input",
                "group" => tr("Input"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Limits the current value by the last value from an input as selected from the input list. The result is passed back for further processing by the next processor in the processing list.</p>")
            ),
            array(
                "id_num" => 67,
                "name" => tr("min by input"),
                "short" => "min_inp",
                "argtype" => ProcessArg::INPUTID,
                "function" => "min_input",
                "group" => tr("Input"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Limits the current value by the last value from an input as selected from the input list. The result is passed back for further processing by the next processor in the processing list.</p>")
            ),
            array(
                "id_num" => 68,
                "name" => tr("max by feed"),
                "short" => "max_feed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "max_feed",
                "group" => tr("Feed"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Limits the current value by the last value from an feed as selected from the feed list. The result is passed back for further processing by the next processor in the processing list.</p>")
            ),
            array(
                "id_num" => 69,
                "name" => tr("min by feed"),
                "short" => "min_feed",
                "argtype" => ProcessArg::FEEDID,
                "function" => "min_feed",
                "group" => tr("Feed"),
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Limits the current value by the last value from an feed as selected from the feed list. The result is passed back for further processing by the next processor in the processing list.</p>")
            ),
            /*
            array(
                "id_num" => 70,
                "name" => tr("Power to kWh/15min"),
                "short" => "kwh15m",
                "argtype" => ProcessArg::FEEDID,
                "function" => "power_to_kwh_15m",
                "unit" => "kWh/15m",
                "group" => tr("Power & Energy"),
                "engines" => array(Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Convert a power value in Watts to a feed that contains an entry for the total energy used every 15 min (starting mid night) (kWh/15min)</p>")
            ),
            */
            array(
                "id_num" => 71,
                "name" => tr("Power to kWh / custom minutes"),
                "short" => "kwhslot",
                "args" => array(
                    array("key" => "interval", "type" => ProcessArg::VALUE, "name" => "Minutes", "desc" => tr("Slot in minutes slot to accumulate"), "default" => "15"),
                    array("key" => "feed", "type" => ProcessArg::FEEDID, "name" => "Feed", "desc" => tr("Output feed"), "engines" => array(Engine::PHPTIMESERIES, Engine::MYSQL, Engine::MYSQLMEMORY))
                ),
                "function" => "power_to_kwh_custom",
                "unit" => "kWh/slot",
                "group" => tr("Power & Energy"),
                "nochange" => true,
                "input_context" => true,
                "virtual_feed_context" => false,
                "description" => tr("<p>Convert a power value in Watts to a feed that contains an entry for the total energy used every selected minutes (starting mid night) (kWh/x min)</p>")
            )
        );
    }

    // / Below are functions of this module processlist
    public function scale($arg, $time, $value)
    {
        if ($value === null) {
            return $value;
        }
        return $value * $arg;
    }

    public function divide($arg, $time, $value)
    {
        if ($arg != 0) {
            return $value / $arg;
        } else {
            return null;
        }
    }

    public function offset($arg, $time, $value)
    {
        if ($value === null) {
            return $value;
        }
        return $value + $arg;
    }

    public function allowpositive($arg, $time, $value)
    {
        if ($value < 0) {
            $value = 0;
        }
        return $value;
    }

    public function allownegative($arg, $time, $value)
    {
        if ($value > 0) {
            $value = 0;
        }
        return $value;
    }

    public function max_value_allowed($arg, $time, $value)
    {
        if ($value > $arg) {
            $value = $arg;
        }
        return $value;
    }

    public function min_value_allowed($arg, $time, $value)
    {
        if ($value < $arg) {
            $value = $arg;
        }
        return $value;
    }

    public function reset2zero($arg, $time, $value)
    {
        $value = 0;
        return $value;
    }

    public function reset2original($arg, $time, $value)
    {
        return $this->proc_initialvalue;
    }

    public function reset2null($arg, $time, $value)
    {
        return null;
    }

    public function signed2unsigned($arg, $time, $value)
    {
        if ($value < 0) {
            $value += 65536;
        }
        return $value;
    }

    public function log_to_feed($id, $time, $value)
    {
        $this->feed->post($id, $time, $time, $value);

        return $value;
    }

    public function log_to_feed_join($id, $time, $value)
    {
        $padding_mode = "join";
        $this->feed->post($id, $time, $time, $value, $padding_mode);
        return $value;
    }

    public function abs_value($arg, $time, $value)
    {
        return abs($value);
    }

    //---------------------------------------------------------------------------------------
    // Times value by current value of another input
    //---------------------------------------------------------------------------------------
    public function times_input($id, $time, $value)
    {   
        $last_value = $this->input->get_last_value($id);
        
        // Handle null values and ensure numeric types
        if ($value === null || $last_value === null) {
            return null;
        }

        // Convert to numeric value - this is the key fix for PHP 8+
        $value = (float)$value;
        $last_value = (float)$last_value;

        return $value * $last_value;
    }

    public function divide_input($id, $time, $value)
    {
        $last_value = $this->input->get_last_value($id);

        // Handle null values and ensure numeric types
        if ($value === null || $last_value === null) {
            return null;
        }
        // Convert to numeric value - this is the key fix for PHP 8+
        $value = (float)$value;
        $last_value = (float)$last_value;

        if ($last_value > 0) {
            return $value / $last_value;
        } else {
            return null; // should this be null for a divide by zero?
        }
    }

    public function update_feed_data($id, $time, $value)
    {
        $time = $this->getstartday($time);

        $feedname = "feed_" . trim($id) . "";
        $result = $this->mysqli->query("SELECT time FROM $feedname WHERE `time` = '$time'");
        $row = $result->fetch_array();

        if (!$row) {
            $this->mysqli->query("INSERT INTO $feedname (time,data) VALUES ('$time','$value')");
        } else {
            $this->mysqli->query("UPDATE $feedname SET data = '$value' WHERE `time` = '$time'");
        }
        return $value;
    }

    public function add_input($id, $time, $value)
    {
        $last_value = $this->input->get_last_value($id);
        
        // Handle null values and ensure numeric types
        if ($value === null || $last_value === null) {
            return null;
        }
        
        // Convert to numeric values
        $value = (float)$value;
        $last_value = (float)$last_value;
        
        return $value + $last_value;
    }

    public function subtract_input($id, $time, $value)
    {
        $last_value = $this->input->get_last_value($id);
        
        // Handle null values and ensure numeric types
        if ($value === null || $last_value === null) {
            return null;
        }
        
        // Convert to numeric values
        $value = (float)$value;
        $last_value = (float)$last_value;

        return $value - $last_value;
    }

    public function max_input($id, $time, $value)
    {
        $max_limit = $this->input->get_last_value($id);
        if ($value > $max_limit) {
            $value = $max_limit;
        }
        return $value;
    }

    public function min_input($id, $time, $value)
    {
        $min_limit = $this->input->get_last_value($id);
        if ($value < $min_limit) {
            $value = $min_limit;
        }
        return $value;
    }

    public function max_feed($id, $time, $value)
    {
        $timevalue = $this->feed->get_timevalue($id);
        $max_limit = $timevalue['value'] * 1;
        if ($value > $max_limit) {
            $value = $max_limit;
        }
        return $value;
    }

    public function min_feed($id, $time, $value)
    {
        $timevalue = $this->feed->get_timevalue($id);
        $min_limit = $timevalue['value'] * 1;
        if ($value < $min_limit) {
            $value = $min_limit;
        }
        return $value;
    }

    //---------------------------------------------------------------------------------------
    // Power to kwh
    //---------------------------------------------------------------------------------------
    public function power_to_kwh($feedid, $time_now, $value)
    {
        // Handle null values and ensure numeric types
        if ($value === null) {
            return null;
        }
        
        // Convert to numeric value
        $value = (float)$value;

        $new_kwh = 0;

        // Get last value
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }
        $last_kwh = $last['value'] * 1; // will convert null to 0, required for first reading starting from 0
        $last_time = $last['time'] * 1; // will convert null to 0
        if (!$last_time) {
            $last_time = $time_now;
        }

        // only update if last datapoint was less than 2 hour old
        // this is to reduce the effect of monitor down time on creating
        // often large kwh readings.
        $time_elapsed = ($time_now - $last_time);
        if ($time_elapsed > 0 && $time_elapsed < 7200) { // 2hrs
            // kWh calculation
            $kwh_inc = ($time_elapsed * $value) / 3600000.0;
            $new_kwh = $last_kwh + $kwh_inc;
        } else {
            // in the event that redis is flushed the last time will
            // likely be > 7200s ago and so kwh inc is not calculated
            // rather than enter 0 we enter the last value
            $new_kwh = $last_kwh;
        }

        $padding_mode = "join";
        $this->feed->post($feedid, $time_now, $time_now, $new_kwh, $padding_mode);

        return $value;
    }

    public function power_to_kwhd($feedid, $time_now, $value)
    {
        // Handle null values and ensure numeric types
        if ($value === null) {
            return null;
        }
        
        // Convert to numeric value - this is the key fix for PHP 8+
        $value = (float)$value;

        $new_kwh = 0;

        // Get last value
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }
        $last_kwh = $last['value'] * 1; // will convert null to 0, required for first reading starting from 0
        $last_time = $last['time'] * 1; // will convert null to 0
        if (!$last_time) {
            $last_time = $time_now;
        }

        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);

        $time_elapsed = ($time_now - $last_time);
        if ($time_elapsed > 0 && $time_elapsed < 7200) { // 2hrs
            // kWh calculation
            $kwh_inc = ($time_elapsed * $value) / 3600000.0;
        } else {
            // in the event that redis is flushed the last time will
            // likely be > 7200s ago and so kwh inc is not calculated
            // rather than enter 0 we dont increase it
            $kwh_inc = 0;
        }

        if ($last_slot == $current_slot) {
            $new_kwh = $last_kwh + $kwh_inc;
        } else {
            # We are working in a new slot (new day) so don't increment it with the data from yesterday
            $new_kwh = $kwh_inc;
        }
        $this->feed->post($feedid, $time_now, $current_slot, $new_kwh);

        return $value;
    }

    public function power_to_kwh_15m($feedid, $time_now, $value)
    {
        $new_kwh = 0;

        // Get last value
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }
        $last_kwh = $last['value'] * 1; // will convert null to 0, required for first reading starting from 0
        $last_time = $last['time'] * 1; // will convert null to 0
        if (!$last_time) {
            $last_time = $time_now;
        }

        // Define stop interval in minutes
        $slot_interval_m = 15;
        $current_slot = $this->get_time_slot($time_now, $slot_interval_m);
        $last_slot = $this->get_time_slot($last_time, $slot_interval_m);

        $time_elapsed = ($time_now - $last_time);
        if ($time_elapsed > 0 && $time_elapsed <= $slot_interval_m * 60) { //15m
            // kWh calculation
            $kwh_inc = ($time_elapsed * $value) / 3600000.0;
        } else {
            // in the event that redis is flushed the last time will
            // likely be > slot interal ago and so kwh inc is not calculated
            // rather than enter 0 we dont increase it
            $kwh_inc = 0;
        }

        if ($last_slot['start_time'] == $current_slot['start_time']) {
            $new_kwh = $last_kwh + $kwh_inc;
        } else {
            # We are working in a new 15min slot so don't increment it with the data from last slot
            $new_kwh = $kwh_inc;
        }
        $this->feed->post($feedid, $time_now, $current_slot['end_time'], $new_kwh);

        $this->log->info("power_to_kwh_15m() feedid=$feedid start=" . $current_slot['start_time'] . " end=" . $current_slot['end_time'] . " new_kwh=$new_kwh value=$value ");

        return $value;
    }

    public function power_to_kwh_custom($args, $time_now, $value)
    {
        $mins = intval($args[0]);
        $feedid = intval($args[1]);

        $new_kwh = 0;

        // Get last value
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }
        $last_kwh = $last['value'] * 1; // will convert null to 0, required for first reading starting from 0
        $last_time = $last['time'] * 1; // will convert null to 0
        if (!$last_time) {
            $last_time = $time_now;
        }

        // Define stop interval in minutes
        $slot_interval_m = $mins;
        $current_slot = $this->get_time_slot($time_now, $slot_interval_m);
        $last_slot = $this->get_time_slot($last_time, $slot_interval_m);

        $time_elapsed = ($time_now - $last_time);
        if ($time_elapsed > 0 && $time_elapsed <= $slot_interval_m * 60) {
            // kWh calculation
            $kwh_inc = ($time_elapsed * $value) / 3600000.0;
        } else {
            // in the event that redis is flushed the last time will
            // likely be > slot interal ago and so kwh inc is not calculated
            // rather than enter 0 we dont increase it
            $kwh_inc = 0;
        }

        if ($last_slot['start_time'] == $current_slot['start_time']) {
            $new_kwh = $last_kwh + $kwh_inc;
        } else {
            # We are working in a new 15min slot so don't increment it with the data from last slot
            $new_kwh = $kwh_inc;
        }
        $this->feed->post($feedid, $time_now, $current_slot['end_time'], $new_kwh);

        $this->log->info("power_to_kwh_custom() feedid=$feedid start=" . $current_slot['start_time'] . " end=" . $current_slot['end_time'] . " new_kwh=$new_kwh value=$value ");

        return $value;
    }

    public function kwh_to_kwhd($feedid, $time_now, $value)
    {
        global $redis;
        if (!$redis) {
            return $value; // return if redis is not available
        }

        $currentkwhd = $this->feed->get_timevalue($feedid);
        if ($currentkwhd === null) {
            return $value; // feed does not exist
        }

        $last_time = $currentkwhd['time'];

        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);

        if ($redis->exists("process:kwhtokwhd:$feedid")) {
            $lastkwhvalue = $redis->hmget("process:kwhtokwhd:$feedid", array('time', 'value'));
            $kwhinc = $value - $lastkwhvalue['value'];

            // kwh values should always be increasing so ignore ones that are less
            // assume they are errors
            if ($kwhinc < 0) {
                $kwhinc = 0;
                $value = $lastkwhvalue['value'];
            }

            if ($last_slot == $current_slot) {
                $new_kwh = $currentkwhd['value'] + $kwhinc;
            } else {
                $new_kwh = $kwhinc;
            }

            $this->feed->post($feedid, $time_now, $current_slot, $new_kwh);
        }

        $redis->hMset("process:kwhtokwhd:$feedid", array('time' => $time_now, 'value' => $value));

        return $value;
    }

    //---------------------------------------------------------------------------------------
    // input on-time counter
    //---------------------------------------------------------------------------------------
    public function input_ontime($feedid, $time_now, $value)
    {
        // Get last value
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }
        $last_time = $last['time'];

        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);

        if (!isset($last['value'])) {
            $last['value'] = 0;
        }
        $ontime = $last['value'];
        $time_elapsed = 0;

        if ($value > 0 && (($time_now - $last_time) < 7200)) {
            $time_elapsed = $time_now - $last_time;
            $ontime += $time_elapsed;
        }

        if ($last_slot != $current_slot) {
            $ontime = $time_elapsed;
        }

        $this->feed->post($feedid, $time_now, $current_slot, $ontime);

        return $value;
    }

    //--------------------------------------------------------------------------------
    // Display the rate of change for the current and last entry
    //--------------------------------------------------------------------------------
    public function ratechange($feedid, $time, $value)
    {
        global $redis;
        if (!$redis) {
            return $value; // return if redis is not available
        }

        if ($redis->exists("process:ratechange:$feedid")) {
            $lastvalue = $redis->hmget("process:ratechange:$feedid", array('time', 'value'));
            $ratechange = $value - $lastvalue['value'];
            $this->feed->post($feedid, $time, $time, $ratechange);
        }
        $redis->hMset("process:ratechange:$feedid", array('time' => $time, 'value' => $value));

        return $ratechange;
    }

    public function save_to_input($inputid, $time, $value)
    {
        $this->input->set_timevalue($inputid, $time, $value);
        return $value;
    }

    public function whinc_to_kwhd($feedid, $time_now, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }
        $last_time = $last['time'];

        //$current_slot = floor($time_now / 86400) * 86400;
        //$last_slot = floor($last_time / 86400) * 86400;
        $current_slot = $this->getstartday($time_now);
        $last_slot = $this->getstartday($last_time);

        $new_kwh = $last['value'] + ($value / 1000.0);
        if ($last_slot != $current_slot) {
            $new_kwh = ($value / 1000.0);
        }

        $this->feed->post($feedid, $time_now, $current_slot, $new_kwh);

        return $value;
    }

    public function accumulator($feedid, $time, $value)
    {
        // Handle null values and ensure numeric types
        if ($value === null) {
            return null;
        }
        
        // Convert to numeric value - this is the key fix for PHP 8+
        $value = (float)$value;

        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }
        $value = $last['value'] + $value;
        $padding_mode = "join";
        $this->feed->post($feedid, $time, $time, $value, $padding_mode);
        return $value;
    }
    /*
    public function accumulator_daily($feedid, $time_now, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        if ($last===null) return $value; // feed does not exist
        $value = $last['value'] + $value;
        $feedtime = $this->getstartday($time_now);
        $this->feed->post($feedid, $time_now, $feedtime, $value);
        return $value;
    }*/

    // No longer supported
    public function histogram($feedid, $time_now, $value)
    {
        return $value;
    }

    public function pulse_diff($feedid, $time_now, $value)
    {
        $value = $this->signed2unsigned(false, false, $value);

        if ($value > 0) {
            $pulse_diff = 0;
            $last = $this->feed->get_timevalue($feedid);
            if ($last === null) {
                return 0; // feed does not exist
            }

            if ($last['time']) {
                // Need to handle resets of the pulse value (and negative 2**15?)
                if ($value >= $last['value']) {
                    $pulse_diff = $value - $last['value'];
                } else {
                    $pulse_diff = $value;
                }
            }

            // Save to allow next difference calc.
            $this->feed->post($feedid, $time_now, $time_now, $value);

            return $pulse_diff;
        }
    }

    public function kwh_to_power($feedid, $time, $value)
    {
        global $redis;
        if (!$redis) {
            return $value; // return if redis is not available
        }

        $power = 0;
        if ($redis->exists("process:kwhtopower:$feedid")) {
            $lastvalue = $redis->hmget("process:kwhtopower:$feedid", array('time', 'value'));
            $kwhinc = $value - $lastvalue['value'];
            $joules = $kwhinc * 3600000.0;
            $timeelapsed = ($time - $lastvalue['time']);
            if ($timeelapsed > 0) {     //This only avoids a crash, it's not ideal to return "power = 0" to the next process.
                $power = $joules / $timeelapsed;
                $this->feed->post($feedid, $time, $time, $power);
            } // should have else { log error message }
        }
        $redis->hMset("process:kwhtopower:$feedid", array('time' => $time, 'value' => $value));

        return $power;
    }

    public function max_value($feedid, $time_now, $value)
    {
        // Get last values
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }

        $last_val = $last['value'];
        $last_time = $last['time'];
        $feedtime = $this->getstartday($time_now);
        $time_check = $this->getstartday($last_time);

        // Runs on setup and midnight to reset current value - (otherwise db sets 0 as new max)
        if ($time_check != $feedtime || $value > $last_val) {
            $this->feed->post($feedid, $time_now, $feedtime, $value);
        }
        return $value;
    }

    public function min_value($feedid, $time_now, $value)
    {
        // Get last values
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }

        $last_val = $last['value'];
        $last_time = $last['time'];
        $feedtime = $this->getstartday($time_now);
        $time_check = $this->getstartday($last_time);

        // Runs on setup and midnight to reset current value - (otherwise db sets 0 as new min)
        if ($time_check != $feedtime || $value < $last_val) {
            $this->feed->post($feedid, $time_now, $feedtime, $value);
        }
        return $value;
    }

    public function add_feed($feedid, $time, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }

        // Handle null values and ensure numeric types
        if ($value === null || $last['value'] === null) {
            return null;
        }
        
        // Convert to numeric values
        $value = (float)$value;
        $last_value = (float)$last['value'];

        return $last_value + $value;
    }

    public function sub_feed($feedid, $time, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }

        // Handle null values and ensure numeric types
        if ($value === null || $last['value'] === null) {
            return null;
        }
        
        // Convert to numeric values
        $value = (float)$value;
        $last_value = (float)$last['value'];

        return $value - $last_value;
    }

    public function multiply_by_feed($feedid, $time, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }

        // Handle null values and ensure numeric types
        if ($value === null || $last['value'] === null) {
            return null;
        }

        // Convert to numeric values
        $value = (float)$value;
        $last_value = (float)$last['value'];

        return $value * $last_value;
    }

    public function divide_by_feed($feedid, $time, $value)
    {
        $last = $this->feed->get_timevalue($feedid);
        if ($last === null) {
            return $value; // feed does not exist
        }

        // Handle null values and ensure numeric types
        if ($value === null || $last['value'] === null) {
            return null;
        }

        // Convert to numeric values
        $value = (float)$value;
        $last_value = (float)$last['value'];

        if ($last_value != 0) {
            return $value / $last_value;
        } else {
            return null;
        }
    }

    public function wh_accumulator($feedid, $time, $value)
    {
        $max_power = 60000; // in Watt
        $totalwh = $value;

        global $redis;
        if (!$redis) {
            return $value; // return if redis is not available
        }

        if ($redis->exists("process:whaccumulator:$feedid")) {
            $last_input = $redis->hmget("process:whaccumulator:$feedid", array('time', 'value'));

            $last_feed = $this->feed->get_timevalue($feedid);
            if ($last_feed === null) {
                return $value; // feed does not exist
            }

            $totalwh = $last_feed['value'];

            $time_diff = $time - $last_feed['time'];
            $val_diff = $value - $last_input['value'];

            if ($time_diff > 0) {
                $power = ($val_diff * 3600) / $time_diff;

                if ($val_diff > 0 && $power < $max_power) {
                    $totalwh += $val_diff;
                }
            }

            $padding_mode = "join";
            $this->feed->post($feedid, $time, $time, $totalwh, $padding_mode);
        }
        $redis->hMset("process:whaccumulator:$feedid", array('time' => $time, 'value' => $value));

        return $totalwh;
    }

    public function kwh_accumulator($feedid, $time, $value)
    {
        $max_power = 60000; // in Watt
        $totalkwh = $value;

        global $redis;
        if (!$redis) {
            return $value; // return if redis is not available
        }

        if ($redis->exists("process:kwhaccumulator:$feedid")) {
            $last_input = $redis->hmget("process:kwhaccumulator:$feedid", array('time', 'value'));

            $last_feed = $this->feed->get_timevalue($feedid);
            if ($last_feed === null) {
                return $value; // feed does not exist
            }

            $totalkwh = $last_feed['value'];

            $time_diff = $time - $last_feed['time'];
            $val_diff = $value - $last_input['value'];

            if ($time_diff > 0) {
                $power = ($val_diff * 3600000) / $time_diff;

                if ($val_diff > 0 && $power < $max_power) {
                    $totalkwh += $val_diff;
                }
            }

            $padding_mode = "join";
            $this->feed->post($feedid, $time, $time, $totalkwh, $padding_mode);
        }
        $redis->hMset("process:kwhaccumulator:$feedid", array('time' => $time, 'value' => $value));

        return $totalkwh;
    }

    public function publish_to_mqtt($topic, $time, $value)
    {
        global $redis;
        // saves value to redis
        // phpmqtt_input.php is then used to publish the values
        if ($this->mqtt) {
            $data = array('topic' => $topic, 'value' => $value, 'timestamp' => $time);
            $redis->hset("publish_to_mqtt", $topic, $value);
            // $redis->rpush('mqtt-pub-queue', json_encode($data));
        }
        return $value;
    }


    // Conditional process list flow
    public function if_zero_skip($noarg, $time, $value)
    {
        if ($value == 0) {
            $this->proc_skip_next = true;
        }
        return $value;
    }
    public function if_not_zero_skip($noarg, $time, $value)
    {
        if ($value != 0) {
            $this->proc_skip_next = true;
        }
        return $value;
    }
    public function if_null_skip($noarg, $time, $value)
    {
        if ($value === null) {
            $this->proc_skip_next = true;
        }
        return $value;
    }
    public function if_not_null_skip($noarg, $time, $value)
    {
        if (!($value === null)) {
            $this->proc_skip_next = true;
        }
        return $value;
    }

    public function if_gt_skip($arg, $time, $value)
    {
        if ($value > $arg) {
            $this->proc_skip_next = true;
        }
        return $value;
    }
    public function if_gt_equal_skip($arg, $time, $value)
    {
        if ($value >= $arg) {
            $this->proc_skip_next = true;
        }
        return $value;
    }
    public function if_lt_skip($arg, $time, $value)
    {
        if ($value < $arg) {
            $this->proc_skip_next = true;
        }
        return $value;
    }
    public function if_lt_equal_skip($arg, $time, $value)
    {
        if ($value <= $arg) {
            $this->proc_skip_next = true;
        }
        return $value;
    }

    public function if_equal_skip($arg, $time, $value)
    {
        if ($value == $arg) {
            $this->proc_skip_next = true;
        }
        return $value;
    }
    public function if_not_equal_skip($arg, $time, $value)
    {
        if ($value != $arg) {
            $this->proc_skip_next = true;
        }
        return $value;
    }

    public function goto_process($proc_no, $time, $value)
    {
        $this->proc_goto = $proc_no - 2;
        if ($this->proc_goto < 0) {
            $this->proc_goto = 0; // Ensure we don't go to a negative process number
        }
        return $value;
    }

    public function error_found($arg, $time, $value)
    {
        $this->proc_goto = PHP_INT_MAX;
        return $value;
    }


    // Fetch datapoint from source feed data at specified timestamp
    // Loads full feed to data cache if it's the first time to load
    public function source_feed_data_time($feedid, $time, $value, $options)
    {
        // If start and end are set this is a request over muultiple data points
        if (isset($options['start']) && isset($options['end'])) {
            // Load feed to data cache if it has not yet been loaded
            if (!isset($this->data_cache[$feedid])) {
                $this->data_cache[$feedid] = $this->feed->get_data($feedid, $options['start'] * 1000, $options['end'] * 1000, $options['interval'], $options['average'], $options['timezone'], 'unix', false, 0, 0);
            }
            // Return value
            if (isset($this->data_cache[$feedid][$options['index']])) {
                return $this->data_cache[$feedid][$options['index']][1];
            }
        } else {
            // This is a request for the last value only
            $timevalue = $this->feed->get_timevalue($feedid);
            if (is_null($timevalue)) {
                return null;
            }
            return $timevalue["value"];
        }
        return null;
    }

    public function add_source_feed($feedid, $time, $value, $options)
    {
        $last = $this->source_feed_data_time($feedid, $time, $value, $options);

        if ($value === null || $last === null) {
            return null;
        }
        $value = $last + $value;
        return $value;
    }

    public function sub_source_feed($feedid, $time, $value, $options)
    {
        $last = $this->source_feed_data_time($feedid, $time, $value, $options);

        if ($value === null || $last === null) {
            return null;
        }
        $myvar = $last * 1;
        return $value - $myvar;
    }

    public function multiply_by_source_feed($feedid, $time, $value, $options)
    {
        $last = $this->source_feed_data_time($feedid, $time, $value, $options);

        if ($value === null || $last === null) {
            return null;
        }
        $value = $last * $value;
        return $value;
    }

    public function divide_by_source_feed($feedid, $time, $value, $options)
    {
        $last = $this->source_feed_data_time($feedid, $time, $value, $options);

        if ($value === null || $last === null) {
            return null;
        }
        $myvar = $last * 1;

        if ($myvar != 0) {
            return $value / $myvar;
        } else {
            return null;
        }
    }

    public function reciprocal_by_source_feed($feedid, $time, $value, $options)
    {
        $last = $this->source_feed_data_time($feedid, $time, $value, $options);

        if ($value == null || $last == null) {
            return null;
        }
        $myvar = $last * 1;

        if ($myvar != 0) {
            return 1 / $myvar;
        } else {
            return null;
        }
    }


    //CHAVEIRO TBD: virtual feed daily - not required on sql engine but needs tests for other engines
    public function get_feed_data_day($id, $time, $value, $options)
    {
        if ($options['start'] && $options['end']) {
            $time = $this->getstartday($options['start']);
        } else {
            $time = $this->getstartday($time);
        }

        $feedname = "feed_" . trim($id) . "";
        $result = $this->mysqli->query("SELECT data FROM $feedname WHERE `time` = '$time'");
        if ($result != null) {
            $row = $result->fetch_array();
        }
        if (isset($row)) {
            return $row['data'];
        } else {
            return null;
        }
    }



    // No longer used
    public function average($feedid, $time_now, $value)
    {
        return $value;
    } // needs re-implementing
    public function phaseshift($id, $time, $value)
    {
        return $value;
    }
    public function kwh_to_kwhd_old($feedid, $time_now, $value)
    {
        return $value;
    }
    public function power_acc_to_kwhd($feedid, $time_now, $value)
    {
        return $value;
    } // Process can now be achieved with allow positive process before power to kwhd

    //------------------------------------------------------------------------------------------------------
    // Calculate the energy used to heat up water based on the rate of change for the current and a previous temperature reading
    // See http://harizanov.com/2012/05/measuring-the-solar-yield/ for more info on how to use it
    //------------------------------------------------------------------------------------------------------
    public function heat_flux($feedid, $time_now, $value)
    {
        return $value;
    } // Removed to be reintroduced as a post-processing based visualisation calculated on the fly.

    // Get the start of the day
    public function getstartday($time_now)
    {
        $now = DateTime::createFromFormat("U", (int) $time_now);
        $now->setTimezone(new DateTimeZone($this->timezone));
        $now->setTime(0, 0);    // Today at 00:00
        return $now->format("U");
    }

    // Get the start and end time of the $slot_interval_m minutes slot starting 00:00
    public function get_time_slot($time_now, $slot_interval_m)
    {
        // Step 1: Use a DateTime object to find the start of the day (00:00)
        // in the specified timezone. This correctly handles Daylight Saving Time (DST).
        $now = (new DateTime('@' . (int) $time_now))->setTimezone(new DateTimeZone($this->timezone));
        $start_of_day_ts = (clone $now)->setTime(0, 0)->getTimestamp();

        // Step 2: Perform all further calculations using fast integer arithmetic.
        $seconds_in_interval = $slot_interval_m * 60;
        $seconds_since_start_of_day = (int) $time_now - $start_of_day_ts;

        // Use intdiv for faster and cleaner integer division.
        $slot_index = intdiv($seconds_since_start_of_day, $seconds_in_interval);

        $slot_start_time = $start_of_day_ts + ($slot_index * $seconds_in_interval);
        $slot_end_time = $start_of_day_ts + (($slot_index + 1) * $seconds_in_interval);

        return [
            'start_time' => (string) $slot_start_time,
            'end_time' => (string) $slot_end_time,
        ];
    }
}
