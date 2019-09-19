<?php

class MysqlMemory extends MysqlTimeSeries
{
    public function create($feedid,$options)
    {
        $feedname = "feed_".trim($feedid)."";
		$this->log->info("create() Mysql Memory $feedname");
        $result = $this->mysqli->query("CREATE TABLE $feedname (time INT UNSIGNED NOT NULL, data FLOAT NOT NULL, UNIQUE (time)) ENGINE=MEMORY");
        return true;
    }

}
