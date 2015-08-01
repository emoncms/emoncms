<?php
    global $mysqli,$route,$session;
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

    $publishedDashs = 0;
    // Show published dashboards as single items
    foreach ($listmenu as $dash){
        if ($dash['published']){
            $menu_dashboard[] = array('name'=> $dash['name'], 'desc'=> $dash['desc'],'icon'=>'icon-star icon-white', 'published'=>$dash['published'], 'path'=>$dash['path'] , 'session'=>$sess, 'order'=>$dash['order']);
            $publishedDashs++;
        }
    }

    // If not all dashboards are published, show a dropdown menu with all
    if (count($listmenu) > $publishedDashs) {
        $menu_left[] = array('name'=> dgettext($domain, "Dashboard"), 'icon'=>'icon-th-large icon-white', 'path'=>$dashpath , 'session'=>$sess, 'order'=>0, 'dropdown'=>$listmenu);
    }

    $menu_dropdown_config[] = array('name'=> dgettext($domain, "Dashboards"), 'icon'=>'icon-th-large', 'path'=>"dashboard/list" , 'session'=>"write", 'order'=>30 );

