<?php

class SqliteTimeSeries
{

	private $conn;

	/*
	 * Constructor.
	 *
	 * @param api $conn Instance of sqlite
	 *
	 * @api
	 */
	public function __construct($conn)
	{
		$this->conn = $conn;
	}

	/*
	 * Creates a histogram type pgsql table.
	 *
	 * @param integer $feedid The feedid of the histogram table to be created
	 */
	public function create($feedid)
	{
		$feedname = "feed_" . trim($feedid) . "";

		$sql = ("CREATE TABLE $feedname (time TIMESTAMP WITH TIME ZONE, data REAL);" .
			"CREATE INDEX " . $feedname . "_time_id ON $feedname(time);");
		$result = $this->conn->exec($sql);
		$retval = ($result !== FALSE) ? TRUE : FALSE;
		$result = NULL;

		return $retval;
	}

	public function insert($feedid, $time, $value)
	{
		$feedname = "feed_" . trim($feedid) . "";
		$sql = ("INSERT INTO $feedname (time, data) VALUES ('$time', '$value');");
		$result = $this->conn->exec($sql);
		$retval = ($result !== FALSE) ? TRUE : FALSE;
		$result = NULL;

		return $retval;
	}

	public function update($feedid, $time, $value)
	{
		$feedname = "feed_" . trim($feedid) . "";
		/* a. update or insert data value in feed table */
		$sql = ("SELECT data FROM $feedname WHERE time = '$time';");
		$result = $this->conn->query($sql);
		if (!$result) {
			return $value;
		}

		$array = $result->fetch(PDO::FETCH_ASSOC);
		if ($array['data']) {
			$sql = ("UPDATE $feedname SET data = '$value' WHERE time = '$time';");
		} else {
			$value = 0;
			$sql = ("INSERT INTO $feedname (time, data) VALUES ('$time', '$value');");
		}
		$result = $this->conn->exec($sql);
		$result = NULL;

		return $value;
	}
	public function get_data($feedid, $start, $end, $dp)
	{
		$dp = intval($dp);
		$feedid = intval($feedid);
		$start = floatval($start);
		$end = floatval($end);

		if ($end == 0)
			$end = time() * 1000; /* bloat up to match the precision of the other timestamps */

		$feedname = "feed_" . trim($feedid) . "";
		/* The higher precision of javascripts Date class doesn't match PHP or Postgresql, convert it down.
		 * Postgresql actually does know and work with decimals
		 */
		$start = intval($start / 1000);
		$end = intval($end / 1000);

		$data = array();
		$range = $end - $start;
		if ($range > 180000 && $dp > 0) { /* 180000 seconds = 50 hrs */
			$td = $range / $dp;
			$sql = ("SELECT time, data FROM $feedname WHERE time BETWEEN :start AND :end LIMIT 1;");
//			$stmt = $this->conn->prepare($sql);
			$t = $start;
			$tb = 0;
			for ($i = 0; $i < $dp; $i++) {
				$tb = $start + intval(($i + 1) * $td);
				$sql = ("SELECT time, data FROM $feedname WHERE time BETWEEN $t AND $tb LIMIT 1;");
//				$stmt->bindValue(':start', $tb, PDO::PARAM_INT);
//				$stmt->bindValue(':end', $t, PDO::PARAM_INT);
//				$result = $stmt->execute();
				$result = $this->conn->query($sql);
				$timedata = $result->fetch(PDO::FETCH_ASSOC);

				if ($timedata) {
					if ($timedata['data'] != NULL) { /* Remove this to show white space gaps in graph */
						$time = $timedata['time'];
						$data[] = array($time, $timedata['data']);
					}
				}
				$t = $tb;
			}
		} else {
			if ($range > 5000 && $dp > 0) {
				$td = intval($range / $dp);
				$sql = "SELECT cast((time / $td) AS integer) AS time, AVG(data) AS data " .
				       "FROM $feedname WHERE time BETWEEN $start AND $end " .
				       "GROUP BY time;";
			} else {
				$td = 1;
				$sql = "SELECT time, data FROM $feedname " .
				       "WHERE time BETWEEN $start AND $end ORDER BY time DESC;";
			}

			$result = $this->conn->query($sql);
			if ($result) {
				while($row = $result->fetch(PDO::FETCH_ASSOC)) {
					$timedata = $row['data'];
					if ($timedata != NULL) { /* Remove this to show white space gaps in graph */
						$time = $row['time'] * 1000 * $td;
						$data[] = array($time , $timedata);
					}
				}
			}
		}
		$result->closeCursor();

		return $data;
	}

	public function export($feedida ,$start)
	{
		/* Feed id and start time of feed to export */
		$feedid = intval($feedid);
		$start = intval($start);

		/* Open database etc here
		 * Extend timeout limit from 30s to 2mins
		 */
		set_time_limit (120);

		/* Regulate pgsql and webserver load. */
		$block_size = 400;
		$sleep = 80000;

		$feedname = "feed_" . trim($feedid) . "";
		$filename = $feedname . '.csv';

		/* There is no need for the browser to cache the output */
		header("Cache-Control: no-cache, no-store, must-revalidate");

		/* Tell the browser to handle output as a csv file to be downloaded */
		header('Content-Description: File Transfer');
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename={$filename}");

		header("Expires: 0");
		header("Pragma: no-cache");

		/* Write to output stream */
		$fh = @fopen( 'php://output', 'w');

		/* Load new feed blocks until there is no more data */
		$moredata_available = true;
		while ($moredata_available) {
			/* 1) Load a block */
			$sql = ("SELECT time FROM $feedname WHERE time > '$start' ORDER BY time ASC LIMIT $block_size;");
			$result = $this->conn->query($sql);

			$moredata_available = false;
			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				/* Write block as csv to output stream */
				if (isset($row['data2'])) {
					fputcsv($fh, array($row['time'], $row['data'], $row['data2']));
				} else {
					fputcsv($fh, array($row['time'], $row['data']));
				}

				/* Set new start time so that we read the next block along */
				$start = $row['time'];
				$moredata_available = true;
			}
			/* 2) Sleep for a bit */
			usleep($sleep);
		}
		$result->closeCursor();
		fclose($fh);

		exit;
	}

	public function delete_data_point($feedid, $time)
	{
		$feedid = intval($feedid);
		$time = intval($time);

		$feedname = "feed_" . trim($feedid) . "";
		$sql = ("DELETE FROM $feedname where time = '$time' LIMIT 1;");
		$result = $this->conn->exec($sql);
		$retval = ($result !== FALSE) ? TRUE : FALSE;
		$result = NULL;

		return $retval;
	}

	public function deletedatarange($feedid, $start, $end)
	{
		$feedid = intval($feedid);
		$start = intval($start / 1000);
		$end = intval($end / 1000);

		$feedname = "feed_" . trim($feedid) . "";
		$sql = ("DELETE FROM $feedname where time >= '$start' AND time <= '$end';");
		$result = $this->conn->exec($sql);
		$retval = ($result !== FALSE) ? TRUE : FALSE;
		$result = NULL;

		return $retval;
	}

	public function delete($feedid)
	{
		$sql = ("DROP TABLE IF EXISTS feed_" . $feedid . ";");
		$result = $this->conn->exec($sql);
		$retval = ($result !== FALSE) ? TRUE : FALSE;
		$result = NULL;

		return $retval;
	}

	public function get_feed_size($feedid)
	{
		/* SQLite3 doesn't support anything to obtain the actual size of a table.
		 * This uses sum and count to roughly estimate the used bytes. This is a slow
		 * and resource wasting process. Luckly this only gets done when the user presses
		 * the button so I guess its 'ok' for now. We estimate 4 bytes for the time and 8
		 * the data.
		 */
		$feedname = "feed_" .  $feedid;
		$sql = ("SELECT (count(data) * 12) AS size FROM '$feedname';");
		$result = $this->conn->query($sql);
		$row = $result->fetch(PDO::FETCH_ASSOC);
		$result->closeCursor();
		return $row['size'];
	}
}
