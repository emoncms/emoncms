<?php

class MysqlTimeSeries
{
    private $mysqli;
    private $log;

    /**
     * Constructor.
     *
     * @param api $mysqli Instance of mysqli
    */
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
        $this->log = new EmonLogger(__FILE__);
    }

    /**
     * Creates a histogram type mysql table.
     *
     * @param integer $feedid The feedid of the histogram table to be created
    */
    public function create($feedid,$options)
    {
        $feedname = "feed_".trim($feedid);
        $this->log->info("MySQL: Feed $feedid, Create started");
        $result = $this->mysqli->query("CREATE TABLE $feedname (`time` INT UNSIGNED, `data` float, INDEX (`time`)) ENGINE=MYISAM;");
        if ($result===false) {
            $this->log->warn("MySQL: Feed $feedid, Create failed, MySQL table creation unsucessful");
        	$this->delete($feedid);
        	return false;
        } else {
            $this->log->info("MySQL: Feed $feedid, Create successful");
            return true;
        }
    }

    public function post($feedid,$time,$value)
    {
        $feedname = "feed_".trim($feedid);
        $result = $this->mysqli->query("SELECT * FROM $feedname WHERE `time`='$time';");

        if ($result->num_rows==0) {
           $this->mysqli->query("INSERT INTO $feedname (`time`,`data`) VALUES ('$time','$value');");
           $this->log->info("MySQL: Feed $feedid - timestamp=$time value=$value, Post successful");
           return $value;
        } else {
            $this->log->warn("MySQL: Feed $feedid - timestamp=$time value=$value, updating of datapoints to be made via update function");
            return false; //value already exists
        }
        
    }

    public function update($feedid,$time,$value)
    {
        $feedname = "feed_".trim($feedid);
        $result = $this->mysqli->query("SELECT * FROM $feedname WHERE `time`='$time';");

        if ($result->num_rows==1) {
            $this->mysqli->query("UPDATE $feedname SET `data`='$value' WHERE `time` ='$time';");
            $this->log->info("MySQL: Feed $feedid - timestamp=$time value=$value, Update successful");
            return $value;
        } else {
            $this->log->warn("MySQL: Feed $feedid - timestamp=$time value=$value, posting of datapoints to be made via update function");
            return false; //value does not exist
        }
    }

    public function get_data($feedid,$start,$end,$outinterval)
    {
        $feedid = intval($feedid);
        $start = floatval($start/1000);
        $end = floatval($end/1000);
        $outinterval = intval($outinterval);
        $this->log->info("MySQL: Feed $feedid - range=($start,$end,$outinterval), Get_Data started");
        
        if ($outinterval<1) $outinterval = 1;
        $dp = ceil(($end - $start) / $outinterval);
        $end = $start + ($dp * $outinterval);
        if ($dp<1) return false;

        // Check if datatype is daily so that select over range is used rather than skip select approach
        $result = $this->mysqli->query("SELECT datatype FROM feeds WHERE `id`='$feedid';");
        $row = $result->fetch_array();
        $datatype = $row['datatype'];
        if ($datatype==2) $dp = 0;

        $feedname = "feed_".trim($feedid)."";

        $data = array();
        $range = $end - $start;
        if ($range > 180000 && $dp > 0) // 50 hours
        {
            $td = $range / $dp;
            $stmt = $this->mysqli->prepare("SELECT `time`, `data` FROM $feedname WHERE `time` BETWEEN ? AND ? ORDER BY `time` ASC LIMIT 1;");
            $t = $start; $tb = 0;
            $stmt->bind_param("ii", $t, $tb);
            $stmt->bind_result($dataTime, $dataValue);
            for ($i=0; $i<$dp; $i++)
            {
                $tb = $start + intval(($i+1)*$td);
                $stmt->execute();
                if ($stmt->fetch()) {
                    if ($dataValue!=NULL) { // Remove this to show white space gaps in graph
                        $time = $dataTime * 1000;
                        $data[] = array($time, (float)$dataValue);
                    }
                }
                $t = $tb;
            }
        } else {
            if ($range > 5000 && $dp > 0)
            {
                $td = intval($range / $dp);
                $sql = "SELECT FLOOR(`time`/$td) AS `time`, AVG(`data`) AS `data` FROM $feedname WHERE `time` BETWEEN $start AND $end GROUP BY 1 ORDER BY `time` ASC;";
            } else {
                $td = 1;
                $sql = "SELECT `time`, `data` FROM $feedname WHERE `time` BETWEEN $start AND $end ORDER BY `time` ASC;";
            }

            $result = $this->mysqli->query($sql);
            if($result) {
                while($row = $result->fetch_array()) {
                    $dataValue = $row['data'];
                    if ($dataValue!=NULL) { // Remove this to show white space gaps in graph
                        $time = $row['time'] * 1000 * $td;
                        $data[] = array($time , (float)$dataValue);
                    }
                }
            } else {
                $this->log->warn("MySQL: Feed $feedid - range=($start,$end,$outinterval), Fetching data over range unsuccessful"); 
                return false;
            }
        }
        return $data;
    }

    public function lastvalue($feedid)
    {
        $feedid = (int) $feedid;
        $feedname = "feed_".trim($feedid);

        $result = $this->mysqli->query("SELECT `time`, `data` FROM $feedname ORDER BY `time` Desc LIMIT 1;");
        if ($result && $row = $result->fetch_array()){
            $row['time'] = date("Y-n-j H:i:s", $row['time']);
            return array('time'=>$row['time'], 'value'=>$row['data']);
        } else {
            $this->log->warn("MySQL: Feed $feedid, LastValue failed, fetching unsuccessful");
            return false;
        }
    }

    public function export($feedid,$start)
    {
        // Feed id and start time of feed to export
        $feedid = intval($feedid);
        $start = intval($start)-1;

        // Open database etc here
        // Extend timeout limit from 30s to 2mins
        set_time_limit (120);

        // Regulate mysql and apache load.
        $block_size = 400;
        $sleep = 80000;

        $feedname = "feed_".trim($feedid);
        $fileName = $feedname.'.csv';

        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$fileName}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $fh = @fopen( 'php://output', 'w' );

        // Load new feed blocks until there is no more data
        $moredata_available = 1;
        while ($moredata_available)
        {
            // 1) Load a block
            $result = $this->mysqli->query("SELECT * FROM $feedname WHERE `time`>'$start' ORDER BY `time` Asc Limit $block_size;");
            
            if ($result===false) {
        		$this->log->warn("MySQL: Feed $feedid - start=$start interval=$interval, Export failed, fetching MySQL data unsuccessful");
            	return false;
        	}	

            $moredata_available = 0;
            while($row = $result->fetch_array())
            {
                // Write block as csv to output stream
                if (!isset($row['data2'])) {
                    fputcsv($fh, array($row['time'],$row['data']));
                } else {
                    fputcsv($fh, array($row['time'],$row['data'],$row['data2']));
                }

                // Set new start time so that we read the next block along
                $start = $row['time'];
                $moredata_available = 1;
            }
            // 2) Sleep for a bit
            usleep($sleep);
        }

        fclose($fh);
        exit;
    }

    public function delete_data_point($feedid,$time)
    {
        $feedid = intval($feedid);
        $time = intval($time);
        $feedname = "feed_".trim($feedid);
        $result = $this->mysqli->query("DELETE FROM $feedname where `time`='$time' LIMIT 1;");
        if ($result===false) {
        	$this->log->warn("MySQL: Feed $feedid, Delete of value at ($time) failed");
        	return false;
        } else {
            return true;
        }
    }

    public function deletedatarange($feedid,$start,$end)
    {
        $feedid = intval($feedid);
        $start = intval($start);
        $end = intval($end);

        $feedname = "feed_".trim($feedid)."";
        $result = $this->mysqli->query("DELETE FROM $feedname where `time` >= '$start' AND `time`<= '$end';");
        if ($result===false) {
        	$this->log->warn("MySQL: Feed $feedid, DeleteDataRange [$start,$end] failed");
        	return false;
        } else {
            return true;
        }
    }

    public function delete($feedid)
    {
        $this->mysqli->query("DROP TABLE feed_".$feedid.";");
        if ($result===false) {
        	$this->log->warn("MySQL: Feed $feedid, Delete failed");
        	return false;
        } else {
            $this->log->info("MySQL: Feed $feedid, Delete successful");
            return true;
        }
    }

    public function get_feed_size($feedid)
    {
        $feedname = "feed_".$feedid;
        $result = $this->mysqli->query("SHOW TABLE STATUS LIKE '$feedname';");
        $row = $result->fetch_array();
        $tablesize = $row['Data_length']+$row['Index_length'];
        return $tablesize;
    }
    
    public function get_meta($feedid)
    {
    
    }
    
    public function csv_export($feedid,$start,$end,$outinterval)
    {
        global $csv_decimal_places;
        global $csv_decimal_place_separator;
        global $csv_field_separator;

        //echo $feedid;
        $outinterval = intval($outinterval);
        $feedid = intval($feedid);
        $start = floatval($start);
        $end = floatval($end);
        
        if ($outinterval<1) $outinterval = 1;
        $dp = ceil(($end - $start) / $outinterval);
        $end = $start + ($dp * $outinterval);
        if ($dp<1) return false;

        if ($end == 0) $end = time();

        $feedname = "feed_".trim($feedid);

        // There is no need for the browser to cache the output
        header("Cache-Control: no-cache, no-store, must-revalidate");

        // Tell the browser to handle output as a csv file to be downloaded
        header('Content-Description: File Transfer');
        header("Content-type: application/octet-stream");
        $filename = $feedid.".csv";
        header("Content-Disposition: attachment; filename={$filename}");

        header("Expires: 0");
        header("Pragma: no-cache");

        // Write to output stream
        $exportfh = @fopen( 'php://output', 'w' );

        $data = array();
        $range = $end - $start;
        if ($range > 180000 && $dp > 0) // 50 hours
        {
            $td = $range / $dp;
            $stmt = $this->mysqli->prepare("SELECT time, data FROM $feedname WHERE time BETWEEN ? AND ? LIMIT 1;");
            $t = $start; $tb = 0;
            $stmt->bind_param("ii", $t, $tb);
            $stmt->bind_result($dataTime, $dataValue);
            for ($i=0; $i<$dp; $i++)
            {
                $tb = $start + intval(($i+1)*$td);
                $stmt->execute();
                if ($stmt->fetch()) {
                    if ($dataValue!=NULL) { // Remove this to show white space gaps in graph
                        $time = $dataTime * 1000;
                        fwrite($exportfh, $dataTime.$csv_field_separator.number_format($dataValue,$csv_decimal_places,$csv_decimal_place_separator,'')."\n");
                    }
                }
                $t = $tb;
            }
        } else {
            if ($range > 5000 && $dp > 0)
            {
                $td = intval($range / $dp);
                $sql = "SELECT FLOOR(time/$td) AS time, AVG(data) AS data FROM $feedname WHERE time BETWEEN $start AND $end GROUP BY 1 ORDER BY time ASC;";
            } else {
                $td = 1;
                $sql = "SELECT time, data FROM $feedname WHERE time BETWEEN $start AND $end ORDER BY time ASC;";
            }

            $result = $this->mysqli->query($sql);
            if($result) {
                while($row = $result->fetch_array()) {
                    $dataValue = $row['data'];
                    if ($dataValue!=NULL) { // Remove this to show white space gaps in graph
                        $dataTime = $row['time'] * 1000 * $td;
                        fwrite($exportfh, $dataTime.$csv_field_separator.number_format($dataValue,$csv_decimal_places,$csv_decimal_place_separator,'')."\n");
                    }
                }
            }
        }
        
        fclose($exportfh);
        exit;
    }

}
