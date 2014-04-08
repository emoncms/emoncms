<?php

  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  */

  define('EMONCMS_EXEC', 1);

  $fp = fopen("importlock", "w");
  if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

  $basedir = str_replace("/Modules/raspberrypi","",dirname(__FILE__));
  chdir($basedir);

  // 1) Load settings and core scripts
  require "process_settings.php";
  // 2) Database
  $mysqli = new mysqli($server,$username,$password,$database);

  $redis = new Redis();
  $redis->connect("127.0.0.1");

  // 3) User sessions
  require("Modules/user/user_model.php");
  $user = new User($mysqli,$redis,null);

  include "Modules/raspberrypi/raspberrypi_model.php";
  $raspberrypi = new RaspberryPI($mysqli);

  include "Modules/packetgen/packetgen_model.php";
  $packetgen = new PacketGen($mysqli,$redis);

  $raspberrypi->set_running();

  $settings = $raspberrypi->get();
  $apikey = $settings->apikey;
  if (!$settings->userid) $settings->userid = 1;

  $session = array();
  $session['userid'] = $settings->userid;


  $group = $settings->sgroup;
  $frequency = $settings->frequency;
  $baseid = $settings->baseid;

  // Create a stream context that configures the serial port
  // And enables canonical input.
  $c = stream_context_create(array('dio' =>
    array('data_rate' => 9600,
          'data_bits' => 8,
          'stop_bits' => 1,
          'parity' => 0,
          'flow_control' => 0,
          'is_canonical' => 1)));

  // Are we POSIX or Windows?  POSIX platforms do not have a
  // Standard port naming scheme so it could be /dev/ttyUSB0
  // or some long /dev/tty.serial_port_name_thingy on OSX.
  if (PATH_SEPARATOR != ";") {
    $filename = "dio.serial:///dev/ttyAMA0";
  } else {
    $filename = "dio.serial://dev/ttyAMA0";
  }
  
  $controltime = time();
  $controlinterval = 60;
  $controlinterval = $packetgen->get_interval($session['userid']);

  // Open the stream for read and write and use it.
  $f = fopen($filename, "r+", false, $c);
  stream_set_timeout($f, 0,1000);
  if ($f)
  {
    //fprintf($f,"\r\n");
    sleep(1);
    fprintf($f,$baseid."i");
    sleep(1);
    fprintf($f,$frequency."b");
    sleep(1);
    fprintf($f,$group."g");
    sleep(1);

    $start = time();
    $ni = 0; $remotedata = "[";
    $start_time = time();
    $remotetimer = time();
    $glcdtime = time();

    while(true)
    {
      if (time()-$start>10)
      {
        $start = time();

        $settings = $raspberrypi->get();
        $session['userid'] = $settings->userid;
        
        $controlinterval = $packetgen->get_interval($session['userid']);

        if ($settings->sgroup !=$group) {
          $group = $settings->sgroup; 
          fprintf($f,$group."g"); 
          echo "Group set: ".$group."\n";
        }

        if ($settings->frequency !=$frequency) {
          $frequency = $settings->frequency; 
          fprintf($f,$frequency."b"); 
          echo "Frequency set: ".$frequency."\n";
        }

        if ($settings->baseid !=$baseid) {
          $baseid = $settings->baseid; 
          fprintf($f,$baseid."i"); 
          echo "Base station set: ".$baseid."\n";
        }

        $raspberrypi->set_running();
      }

      $data = fgets($f);
      if ($data && $data!="\n")
      {


        if ($data[0]==">")  
        {
          echo "MESSAGE RX:".$data;
          $data = trim($data);
          $len = strlen($data);

          /*

          For some as yet unknown reason periodically when sending data out from the rfm12pi
          and maybe as part of the script the rfm12pi settings get set unintentionally. 
          It has been suggested that this could be due to the data string to be sent being
          corrupted and turning into a settings string.

          To fix this situation a check is included here to confirm that the rfm12pi settings
          have been set correctly and to catch an accidental change of settings.
          
          If an accidental change of settings occurs the settings will be changed back to the
          user specified baseid, frequency and group settings.

          */

          if ($data[$len-1]=='b') {
            $val = intval(substr($data,2,-1));
            if ($val == $frequency) {
              echo "FREQUENCY SET CORRECTLY\n"; 
            } else {
              echo "FREQUENCY ERROR, RE SENDING FREQUENCY\n";
              fprintf($f,$frequency."b"); 
              usleep(100);
            }
          }

          if ($data[$len-1]=='g') {
            $val = intval(substr($data,2,-1));
            if ($val == $group) {
              echo "GROUP SET CORRECTLY\n"; 
            } else {
              echo "GROUP ERROR, RE SENDING GROUP\n";
              fprintf($f,$group."g"); 
              usleep(100);
            }
          }

          if ($data[$len-1]=='i') {
            $val = intval(substr($data,2,-1));
            if ($val == $baseid) {
              echo "BASEID SET CORRECTLY\n";
            } else {
              echo "BASEID ERROR, RE SENDING BASEID\n";
              fprintf($f,$baseid."g");
              usleep(100);
            }
          }
        } 
        elseif ($data[1]=="-") 
        {
          // Messages that start with a dash indicate a successful tx of data
          echo "LENGTH:".$data;
        }
        elseif (preg_match("/config save failed/i",$data))
        {
          /*

          Sometimes the RFM12PI returns config save failed the following resets the connection in the event of 
          recieving this message. 
          
          */

          echo "CONFIG save fail detected ".time()."\n";
          fclose($f);
          sleep(1);
          $f = fopen($filename, "r+", false, $c);
          stream_set_timeout($f, 0,1000);
          if ($f)
          {
            //fprintf($f,"\r\n");
            sleep(1);
            fprintf($f,$baseid."i");
            sleep(1);
            fprintf($f,$frequency."b");
            sleep(1);
            fprintf($f,$group."g");
            sleep(1);
          }
        }
        elseif (preg_match("/config failed/i",$data))
        {
          echo "CONFIG fail detected ".time()."\n";
          fclose($f);
          sleep(1);
          $f = fopen($filename, "r+", false, $c);
          stream_set_timeout($f, 0,1000);
          if ($f)
          {
            //fprintf($f,"\r\n");
            sleep(1);
            fprintf($f,$baseid."i");
            sleep(1);
            fprintf($f,$frequency."b");
            sleep(1);
            fprintf($f,$group."g");
            sleep(1);
          }
        }
        else
        {
          echo "DATA RX:".$data;
          $values = explode(' ',trim($data));
          if (isset($values[1]) && is_numeric($values[1]))
          {

            $nodeid = (int) $values[0];
            $time = time();

            unset($values[0]);
            // print "http://localhost/rework/node/set.json?apikey=913f557706d069745bfe8dac70040261&time=".$time."&nodeid=".$nodeid."&data=".implode(",",$values)."\n";
            print file_get_contents("http://localhost/rework/node/set.json?apikey=913f557706d069745bfe8dac70040261&time=".$time."&nodeid=".$nodeid."&data=".implode(",",$values));
          }
        }
      }
      
      // RFM12Pi control packet broadcaster
      if ($controlinterval>0 && (time()-$controltime) > $controlinterval)
      {
        $controltime = time();
        $str = $packetgen->getrfm12packet($session['userid']);
        fprintf($f,$str."s");
        usleep(100);
      }
      
    }
  }
  fclose($f);

function getcontent($server, $port, $file)
{
   //$cont = "";
   $ip = gethostbyname($server);
   $fp = fsockopen($ip, $port);
   if (!$fp)
   {
     return "Unknown";
   }
   else
   {
     $com = "GET $file HTTP/1.1\r\nAccept: */*\r\nAccept-Language: de-ch\r\nAccept-Encoding: gzip, deflate\r\nUser-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)\r\nHost: $server:$port\r\nConnection: Keep-Alive\r\n\r\n";
     fputs($fp, $com);
     /* Don't really need to fetch output as it slows us down
     while (!feof($fp))
     {
       $cont .= fread($fp, 500);
     }
     */
     fclose($fp);
     // $cont = substr($cont, strpos($cont, "\r\n\r\n") + 4);
     // return $cont;
   }
}