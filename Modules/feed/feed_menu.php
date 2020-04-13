<?php
/**
 * @package EmonCMS.Site
 * Emoncms - open source energy visualisation
 *
 * @copyright OpenEnergyMonitor project; See COPYRIGHT.txt
 * @license GNU Affero General Public License; see LICENSE.txt
 * @link http://openenergymonitor.org
 */

defined('EMONCMS_EXEC') or die;

$menu['sidebar']['emoncms'][] = array(
    'text' => _("Feeds"),
    'path' => 'feed/view',
    'icon' => 'format_list_bulleted',
    'order' => 1
);
