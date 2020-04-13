<?php
/**
 * @package EmonCMS.Feeds
 * Emoncms - open source energy visualisation
 *
 * @copyright OpenEnergyMonitor project; See COPYRIGHT.txt
 * @license GNU Affero General Public License; see LICENSE.txt
 * @link http://openenergymonitor.org
 */

defined('EMONCMS_EXEC') or die;

class MysqlMemory extends MysqlTimeSeries
{
    /**
     * @param int $feedid
     * @param array $options
     * @return bool
     */
    public function create($feedid, $options)
    {
        $feedname = "feed_" . trim($feedid) . "";
        $this->log->info("create() Mysql Memory $feedname");
        $result = $this->mysqli->query("CREATE TABLE $feedname (time INT UNSIGNED NOT NULL, data FLOAT NOT NULL, UNIQUE (time)) ENGINE=MEMORY");
        return true;
    }

}
