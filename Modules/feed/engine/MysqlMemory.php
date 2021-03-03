<?php

class MysqlMemory extends MysqlTimeSeries
{
    public function create($feedid, $options)
    {
        $table = $this->get_table(intval($feedid));
        $name = $table['name'];
        $type = $table['type'];
        
        $this->log->info("create() Mysql Memory $name");
        $this->mysqli->query("CREATE TABLE $name (time INT UNSIGNED NOT NULL, data $type, UNIQUE (time)) ENGINE=MEMORY");
        return true;
    }

}
