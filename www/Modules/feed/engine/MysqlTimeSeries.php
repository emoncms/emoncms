<?php

class MysqlTimeSeries
{

    private $mysqli;

    /**
     * Constructor.
     *
     * @param api $mysqli Instance of mysqli
     *
     * @api
    */
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * Creates a histogram type mysql table.
     *
     * @param integer $feedid The feedid of the histogram table to be created
    */
    public function create($feedid,$options)
    {
        $feedname = "feed_".trim($feedid)."";

        $result = $this->mysqli->query(
        "CREATE TABLE $feedname (
    time INT UNSIGNED, data float,
        INDEX ( `time` )) ENGINE=MYISAM");

        return true;
    }

    public function post($feedid,$time,$value)
    {
        $feedname = "feed_".trim($feedid)."";
        $this->mysqli->query("INSERT INTO $feedname (`time`,`data`) VALUES ('$time','$value')");
    }

    public function update($feedid,$time,$value)
    {
        $feedname = "feed_".trim($feedid)."";
        // a. update or insert data value in feed table
        $result = $this->mysqli->query("SELECT * FROM $feedname WHERE time = '$time'");

        if (!$result) return $value;
        $row = $result->fetch_array();

        if ($row) $this->mysqli->query("UPDATE $feedname SET data = '$value' WHERE time = '$time'");
        if (!$row) {$value = 0; $this->mysqli->query("INSERT INTO $feedname (`time`,`data`) VALUES ('$time','$value')");}

        return $value;
    }

    public function get_data($feedid,$start,$end,$outinterval)
    {
        //echo $feedid;
        $outinterval = intval($outinterval);
        $feedid = intval($feedid);
        $start = floatval($start/1000);
        $end = floatval($end/1000);
        
        if ($outinterval<1) $outinterval = 1;
        $dp = ceil(($end - $start) / $outinterval);
        $end = $start + ($dp * $outinterval);
        if ($dp<1) return false;

        if ($end == 0) $end = time();

        $feedname = "feed_".trim($feedid)."";

        $data = array();
        $range = $end - $start;
        if ($range > 180000 && $dp > 0) // 50 hours
        {
            $td = $range / $dp;
            $stmt = $this->mysqli->prepare("SELECT time, data FROM $feedname WHERE time BETWEEN ? AND ? LIMIT 1");
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
                        $data[] = array($time, $dataValue);
                    }
                }
                $t = $tb;
            }
        } else {
            if ($range > 5000 && $dp > 0)
            {
                $td = intval($range / $dp);
                $sql = "SELECT FLOOR(time/$td) AS time, AVG(data) AS data".
                    " FROM $feedname WHERE time BETWEEN $start AND $end".
                    " GROUP BY 1";
            } else {
                $td = 1;
                $sql = "SELECT time, data FROM $feedname".
                    " WHERE time BETWEEN $start AND $end ORDER BY time DESC";
            }

            $result = $this->mysqli->query($sql);
            if($result) {
                while($row = $result->fetch_array()) {
                    $dataValue = $row['data'];
                    if ($dataValue!=NULL) { // Remove this to show white space gaps in graph
                        $time = $row['time'] * 1000 * $td;
                        $data[] = array($time , $dataValue);
                    }
                }
            }
        }

        return $data;
    }

    public function lastvalue($feedid)
    {
        $feedid = (int) $feedid;
        $feedname = "feed_".trim($feedid)."";

        $result = $this->mysqli->query("SELECT time, data FROM $feedname ORDER BY time Desc LIMIT 1");
        if ($result && $row = $result->fetch_array()){
            $row['time'] = date("Y-n-j H:i:s", $row['time']);
            return array('time'=>$row['time'], 'value'=>$row['data']);
        } else {
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

        $feedname = "feed_".trim($feedid)."";
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
            $result = $this->mysqli->query("SELECT * FROM $feedname WHERE time>$start
            ORDER BY time Asc Limit $block_size");

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

        $feedname = "feed_".trim($feedid)."";
        $this->mysqli->query("DELETE FROM $feedname where `time` = '$time' LIMIT 1");
    }

    public function deletedatarange($feedid,$start,$end)
    {
        $feedid = intval($feedid);
        $start = intval($start/1000.0);
        $end = intval($end/1000.0);

        $feedname = "feed_".trim($feedid)."";
        $this->mysqli->query("DELETE FROM $feedname where `time` >= '$start' AND `time`<= '$end'");

        return true;
    }

    public function delete($feedid)
    {
        $this->mysqli->query("DROP TABLE feed_".$feedid);
    }

    public function get_feed_size($feedid)
    {
        $feedname = "feed_".$feedid;
        $result = $this->mysqli->query("SHOW TABLE STATUS LIKE '$feedname'");
        $row = $result->fetch_array();
        $tablesize = $row['Data_length']+$row['Index_length'];
        return $tablesize;
    }
    
    public function get_meta($feedid)
    {
    
    }
    
    public function csv_export($feedid,$start,$end,$outinterval)
    {
        //echo $feedid;
        $outinterval = intval($outinterval);
        $feedid = intval($feedid);
        $start = floatval($start/1000);
        $end = floatval($end/1000);
        
        if ($outinterval<1) $outinterval = 1;
        $dp = ceil(($end - $start) / $outinterval);
        $end = $start + ($dp * $outinterval);
        if ($dp<1) return false;

        if ($end == 0) $end = time();

        $feedname = "feed_".trim($feedid)."";

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
            $stmt = $this->mysqli->prepare("SELECT time, data FROM $feedname WHERE time BETWEEN ? AND ? LIMIT 1");
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
                        fwrite($exportfh, $dataTime.",".number_format($dataValue,2)."\n");
                    }
                }
                $t = $tb;
            }
        } else {
            if ($range > 5000 && $dp > 0)
            {
                $td = intval($range / $dp);
                $sql = "SELECT FLOOR(time/$td) AS time, AVG(data) AS data".
                    " FROM $feedname WHERE time BETWEEN $start AND $end".
                    " GROUP BY 1";
            } else {
                $td = 1;
                $sql = "SELECT time, data FROM $feedname".
                    " WHERE time BETWEEN $start AND $end ORDER BY time DESC";
            }

            $result = $this->mysqli->query($sql);
            if($result) {
                while($row = $result->fetch_array()) {
                    $dataValue = $row['data'];
                    if ($dataValue!=NULL) { // Remove this to show white space gaps in graph
                        $time = $row['time'] * 1000 * $td;
                        fwrite($exportfh, $dataTime.",".number_format($dataValue,2)."\n");
                    }
                }
            }
        }
        
        fclose($exportfh);
        exit;
    }

}
