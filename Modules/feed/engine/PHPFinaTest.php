<?php
namespace test;
define('EMONCMS_EXEC', TRUE);
include_once dirname(dirname(dirname(dirname(__FILE__)))) . '/process_settings.php';
include_once dirname(dirname(dirname(dirname(__FILE__)))) . '/Lib/EmonLogger.php';
include_once dirname(__FILE__) . '/PHPFina.php';
use \PHPFina as PHPFina;
use \EmonLogger as EmonLogger;

$settings = array('datadir' => '/tmp/');
$options = array('interval'=>10);
$feedid = 0;

class PHPFinaTest extends \PHPUnit\Framework\TestCase {

    // Done once (before & after) for each 
    // public function named "test[METHOD]()"
    public function setUp() {
        global $settings, $options, $feedid;
        $this->options = $options;
        $this->settings = $settings;
        $this->feedid = $feedid;
        $this->start = strtotime("-1 week"); // 2 week interval ( 20160 minutes / 336 hours)
        $this->end = strtotime("+1 week");
        $this->interval = 1500; // 25 mins between points
        $this->outinterval = 1500; //?? not sure if this is good
        $this->usertimezone = 'Europe/London';
        $this->baseUrl = 'http://localhost/emoncms';
        $this->engine = new PHPFina($settings);
        $this->create();
    }
    public function tearDown(){ 
        $this->delete();
    }

    // used in PHPUnit's setUp() and tearDown() above
    public function create() {
        $this->assertTrue($this->engine->create($this->feedid, $this->options));
    }
    public function delete() {
        $this->assertNull($this->engine->delete($this->feedid));
    }


    // Required Engine Methods....

    public function testGet_meta() {
        $meta = $this->engine->get_meta($this->feedid);
        $this->assertInstanceOf(\stdClass::class, $meta);
    }

    public function testUpdate() {
        $value = $this->engine->update($this->feedid,time(),rand(200,1500));
        $this->assertNotFalse($value);
    }

    public function testLastvalue() {
        $array = $this->engine->lastvalue($this->feedid);
        $this->assertNotFalse($array);
    }

    public function testGet_data() {
        $skipmissing = 0;
        $limitinterval = 1;
		$data = $this->engine->get_data($this->feedid,$this->start,$this->end,$this->interval,$skipmissing,$limitinterval);
        $this->assertTrue(!empty($data) && empty($data['success']), 'no blank result or success == false');
    }

    public function testCsv_export() {
        // $this->engine->csv_export($this->feedid,$this->start,$this->end,$this->outinterval,$this->usertimezone);
        $urlToControllerThatGeneratesCsvDownload = $this->baseUrl.'/feed/csvexport';
        $params = array(
            'id'=>$this->feedid,
            'start'=>$this->start,
            'end'=>$this->end,
            'interva'=>$this->outinterval,
            'timeformat'=>$this->usertimezone
        );
        $urlToControllerThatGeneratesCsvDownload.=('?'.http_build_query($params));
        $response = $this->call('GET', $urlToControllerThatGeneratesCsvDownload);
        $this->assertTrue(strpos($response->content(), 'csv') !== false);
	}

    public function testGet_average_DMY() {
		$this->engine->get_average_DMY($this->feedid,$this->start,$this->end,$mode,$timezone);
		$this->assertTrue(false);
	}

    public function testGet_average() {
		$this->engine->get_average($this->feedid,$this->start,$this->end,$this->outinterval);
		$this->assertTrue(false);
	}

    public function testGet_data_DMY_time_of_day() {
		$this->engine->get_data_DMY_time_of_day($this->feedid,$this->start,$this->end,$mode,$timezone,$split);
		$this->assertTrue(false);
	}

    public function testGet_data_DMY() {
		$this->engine->get_data_DMY($this->feedid,$this->start,$this->end,$mode,$timezone);
		$this->assertTrue(false);
	}

    public function testUpload_fixed_interval() {
		$this->engine->upload_fixed_interval($this->feedid,$this->start,$this->interval,$npoints);
		$this->assertTrue(false);
	}

    public function testUpload_variable_interval() {
		$this->engine->upload_variable_interval($this->feedid,$npoints);
		$this->assertTrue(false);
	}

    public function testClear() {
		$this->engine->clear($this->feedid);
		$this->assertTrue(false);
	}

    public function testTrim() {
		$this->engine->trim($this->feedid, $this->start_time);
		$this->assertTrue(false);
	}



    // public function get_feed_size($feedid);
    public function testGet_feed_size() {
        $size = $this->engine->get_meta($this->feedid);
        $this->assertInternalType('int', $size);
    }

    // public function post($feedid,$feedtime,$value,$arg);
    public function testPost() {
        $size = $this->engine->get_meta($this->feedid);
        $this->assertInternalType('int', $size);
    }



























}
