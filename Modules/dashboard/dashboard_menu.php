<?php
    global $mysqli,$route;
    require_once "Modules/dashboard/dashboard_model.php";
    $dashboard = new Dashboard($mysqli);

    $location = 'view';
    $sess = (isset($session['write']) && $session['write'] ? 'write':(isset($session['read']) && $session['read'] ? 'read':''));
    if (isset($session['profile']) && $session['profile']==1) {
        $dashpath = $session['username'];
        $sess= 'all';
    } else {
        if ($route->action == "edit" && $session['write']) $location = 'edit';
        $dashpath = 'dashboard/'.$location;
    }

    // Contains a list for the drop down with dashboards available for user session type
    $listmenu = $dashboard->build_menu_array($location);
    
    $domain = "messages";
    bindtextdomain($domain, "Modules/dashboard/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_left[] = array('name'=> dgettext($domain, "Dashboards"), 'path'=>$dashpath , 'session'=>$sess, 'order' => 4, 'dropdown'=>$listmenu);
