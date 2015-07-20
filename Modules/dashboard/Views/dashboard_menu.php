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

    if ($type=="list") {
        $result .= "<a class='btn btn-mini' onclick=\"$.ajax({type : 'POST',url :  path + 'dashboard/create.json  ',data : '',dataType : 'json',success : location.reload()});\"><span class='icon-plus-sign' title='". _("New") . "' style='cursor:pointer'></span></a>";
    }

    if ($type!="list") {
        $result .= "<a class='btn btn-mini' href='" . $path . "dashboard/list'><span class='icon-th-list' title='" . _('List view') ."'></span></a>";
    }

    if ($result) {
        echo "<div class='btn-toolbar' style='padding:2px; margin: 0;' align='right'><div class='btn-group'>".$result."</div></div>";   
}