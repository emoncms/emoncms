<?php

class dbTimeSeries
{
	private $db;

	/*
	 * Constructor.
	 *
	 * @param api $conn Instance of db
	 *
	 * @api
	 */
	public function __construct($conn)
	{
		global $default_log_engine;

		switch ($default_log_engine) {
		case (Engine::POSTGRESQL):
			require "Modules/feed/engine/PgsqlTimeSeries.php";
			$this->db = new PgsqlTimeSeries($conn);
			break;
		case (Engine::SQLITE):
			require "Modules/feed/engine/SqliteTimeSeries.php";
			$this->db = new SqliteTimeSeries($conn);
			break;
		case (Engine::MYSQL):
			require "Modules/feed/engine/MysqlTimeSeries.php";
			$this->db = new MysqlTimeSeries($conn);
			break;
		default:
			/* Set default engine or error out? */
			break;
		}
	}

	/*
	 * Creates a histogram type db table.
	 *
	 * @param integer $feedid The feedid of the histogram table to be created
	 */
	public function create($feedid)
	{
		return $this->db->create($feedid);
	}

	public function insert($feedid, $time, $value)
	{
		return $this->db->insert($feedid, $time, $value);
	}

	public function update($feedid, $time, $value)
	{
		return $this->db->update($feedid, $time, $value);
	}

	public function get_data($feedid, $start, $end, $dp)
	{
		return $this->db->get_data($feedid, $start, $end, $dp);
	}

	public function export($feedid, $start)
	{
		return $this->db->export($feedid, $start);
	}

	public function delete_data_point($feedid, $time)
	{
		return $this->db->delete_data_point($feedid, $time);
	}

	public function deletedatarange($feedid, $start, $end)
	{
		return $this->db->deletedatarange($feedid, $start, $end);
	}

	public function delete($feedid)
	{
		return $this->db->delete($feedid);
	}

	public function get_feed_size($feedid)
	{
		return $this->db->get_feed_size($feedid);
	}
}
