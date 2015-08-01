<?php
/*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/

    global $path;

    $result = "";

    if ($type=="view" && isset($id)) {
        $result .= "<a class='btn btn-mini' href='" . $path . "dashboard/edit?id=" . $id . "'><span class='icon-edit' title='" . _("Draw Editor") . "'></span></a>";
    }

    if ($type=="edit" && isset($id)) {
        $result .= "<a class='btn btn-mini' href='#dashConfigModal' role='button' data-toggle='modal'><span class='icon-wrench' title='" . _("Configure dashboard") . "'></span></a>";
        $result .= "<a class='btn btn-mini' href='" . $path . "dashboard/view?id=" . $id . "'><span class='icon-eye-open' title='" . _("View mode") . "'></span></a>";
    }

    //CHAVEIRO: Removed, dashboard list is accessible via setup menu now
    //if ($type!="list") {
    //    $result .= "<a class='btn btn-mini' href='" . $path . "dashboard/list'><span class='icon-th-list' title='" . _('List view') ."'></span></a>";
    //}

    if ($result) {
        echo "<div class='btn-toolbar' style='padding:2px; margin: 0;' align='right'><div class='btn-group'>".$result."</div></div>";   
    }