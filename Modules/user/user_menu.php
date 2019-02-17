<?php
    global $session, $fullwidth, $has_sidebar;
    $has_sidebar['user'] = false;
    // $fullwidth['user'] = true;

    // not sure if this is usefull yet?? need to add an ability to create "favorite" pages
    // $menu['setup'][] = array(
    //     'text' => _("Admin"),
    //     'path' => 'admin/view',
    //     'icon' => 'tasks',
    //     'active' => 'admin',
    //     'sort' => 6
    // );

    if($session['userid']>0){
        // $menu['user'][] = array(
        //     'text' => _("Add Shortcut"),
        //     'icon' => 'plus',
        //     'path' => 'user/links/add',
        //     'sort' => 0,
        //     'active' => 'none'
        // );
        // $menu['user'][] = array(
        //     'text' => _("All Shortcuts"),
        //     'path' => 'user/links',
        //     'icon' => 'favorite',
        //     'sort' => 1,
        //     'active' => 'none'
        // );
        $menu['user'][] = array(
            'text' => _("My Account"),
            'icon' => 'user',
            'path' => 'user/view',
            'sort' => 2,
            'active' => 'none'
        );
        $menu['user'][] = array(
            'li_class' => 'divider',
            'sort' => 3,
            'active' => 'none'
        );
        $menu['user'][] = array(
            'text' => _("Logout"),
            'icon' => 'logout',
            'path' => 'user/logout',
            'sort' => 4,
            'active' => 'none'
        );
        
    } else {
        $menu['user'][] = array(
            'text' => _("Login"),
            'icon' => 'user',
            'path' => '/',
        );
    }

    // $menu['includes']['user'][] = view('Modules/user/Views/sidebar.php');
