<?php

class PgsqlTimeSeries
{

	private $conn;

	/*
	 * Constructor.
	 *
	 * @param api $conn Instance of pgsql
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

		$sql = (
			"CREATE TABLE $feedname (time TIMESTAMP WITH TIME ZONE, data REAL);" .
			"CREATE INDEX " . $feedname . "_time_id ON $feedname(time);");
		return pg_query($this->conn, $sql);
	}

	public function insert($feedid, $time, $value)
	{
		$feedname = "feed_" . trim($feedid) . "";
		$sql = ("INSERT INTO $feedname (time, data) VALUES (to_timestamp($time), '$value');");
		$result = pg_query($this->conn, $sql);
	}

	public function update($feedid, $time, $value)
	{
		$feedname = "feed_" . trim($feedid) . "";
		// a. update or insert data value in feed table
		$sql = ("SELECT data FROM $feedname WHERE time = to_timestamp($time);");
		$result = pg_query($this->conn, $sql);
		if (!$result)
			return $value;

		$row = pg_fetch_row($result);
		if ($row) {
			$sql = ("UPDATE $feedname SET data = '$value' WHERE time = to_timestamp($time);");
		} else {
			$value = 0;
			$sql = ("INSERT INTO $feedname (time, data) VALUES (to_timestamp($time), '$value');");
		}
		pg_query($this->conn, $sql);

		return $value;
	}

	private function get_datares($range, $dp)
	{
		/* FIXME resolution values need to be determined, this is just randomly filled in. */
		$resolution = $range / $dp;
		$datares = "millennium";
		if ($resolution > 100)
			$datares = "century";
		if ($resolution > 200)
			$datares = "decade";
		if ($resolution > 300)
			$datares = "year";
		if ($resolution > 400)
			$datares = "quarter";
		if ($resolution > 500)
			$datares = "month";
		if ($resolution > 600)
			$datares = "week";
		if ($resolution > 700)
			$datares = "day";
		if ($resolution > 800)
			$datares = "day";
		if ($resolution > 900)
			$datares = "hour";
		if ($resolution > 1000)
			$datares = "minute";
		if ($resolution > 1100)
			$datares = "milliseconds";
		if ($resolution > 1200)
			$datares = "microseconds";

		return $datares;
	}

	public function got_data($feedid, $start, $end, $dp)
	{
		$dp = intval($dp);
		$feedid = intval($feedid);
		$start = floatval($start);
		$end = floatval($end);
		$data = array();

		/* The higher precision of javascripts Date class doesn't match PHP or Postgresql, convert it down.
		 * Postgresql actually does know and work with decimals
		 */
		$start /= 1000;
		$end /= 1000;

		if ($end == 0)
			$end = time();

		$datetrunc = $this->get_datares(abs($end - $start), $dp);
		$feedname = "feed_" . trim($feedid) . "";
		$sql = ("SELECT trunc(extract(epoch FROM date_trunc('$datetrunc', time)) * 1000) AS time, avg(data) AS data FROM $feedname WHERE time BETWEEN to_timestamp($start) AND to_timestamp($end) GROUP BY time");; /* remember to multiply by 1000 to match the javascript representation */
		$result = pg_query($this->conn, $sql);
		$numrows = pg_num_rows($result);
		for ($i = 0; $i < $numrows; $i++)
			$data[] = pg_fetch_array($result, $i, PGSQL_NUM);

		return $data;
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
		$start /= 1000;
		$end /= 1000;

		$data = array();
		$range = $end - $start;
		/* TODO: investigate why there's a split and if it can't be handled better by date_trunc */
		if ($range > 180000 && $dp > 0) { /* 180000 seconds = 50 hrs */
			$td = $range / $dp;
			$sql = ("SELECT extract(epoch from time) AS time, data FROM $feedname WHERE time BETWEEN to_timestamp($1) AND to_timestamp($2) LIMIT 1;");
			$stmt = pg_prepare($this->conn, "", $sql);
			$t = $start;
			$tb = 0;
			for ($i = 0; $i < $dp; $i++) {

				$result = pg_execute($this->conn, "", array($tb, $t));
				$timedata = pg_fetch_array($result);

				$tb = $start + intval(($i + 1) * $td);
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
				$sql = "SELECT floor(extract(epoch from time) / $td) AS time, AVG(data) AS data " .
				       "FROM $feedname WHERE time BETWEEN to_timestamp($start) AND to_timestamp($end) " .
				       "GROUP BY time;";
			} else {
				$td = 1;
				$sql = "SELECT time, data FROM $feedname " .
				       "WHERE time BETWEEN to_timestamp($start) AND to_timestamp($end) ORDER BY time DESC;";
			}

			$result = pg_query($this->conn, $sql);
			if ($result) {
				while($row = pg_fetch_array($result)) {
					$timedata = $row['data'];
					if ($timedata != NULL) { /* Remove this to show white space gaps in graph */
						$time = $row['time'] * 1000 * $td;
						$data[] = array($time , $timedata);
					}
				}
			}
		}

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
			$sql = ("SELECT extract(epoch FROM time) AS time FROM $feedname WHERE time > to_timestamp($start) ORDER BY time ASC LIMIT $block_size;");
			$result = pg_query($this->conn, $sql);

			$moredata_available = false;
			while ($row = pg_fetch_array($result)) {
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

		fclose($fh);
		exit;
	}

	public function delete_data_point($feedid, $time)
	{
		$feedid = intval($feedid);
		$time = intval($time);

		$feedname = "feed_" . trim($feedid) . "";
		$sql = ("DELETE FROM $feedname where time = to_timestamp($time) LIMIT 1");
		return pg_query($this->conn, $sql);
	}

	public function deletedatarange($feedid, $start, $end)
	{
		$feedid = intval($feedid);
		$start = intval($start / 1000);
		$end = intval($end / 1000);

		$feedname = "feed_" . trim($feedid) . "";
		$sql = ("DELETE FROM $feedname where time >= to_timestamp($start) AND time <= to_timestamp($end)");
		return pg_query($this->conn, $sql);
	}

	public function delete($feedid)
	{
		$sql = ("DROP TABLE feed_" . $feedid);
		return pg_query($this->conn, $sql);
	}

	public function get_feed_size($feedid)
	{
		$feedname = "feed_" .  $feedid;
		$sql = ("SELECT pg_total_relation_size('$feedname');");
		$result = pg_query($this->conn, $sql);
		$row = pg_fetch_row($result);
		return $row['0'];
	}
}
