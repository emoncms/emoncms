<?php
    global $session;
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
            'order' => 2,
            'active' => 'none'
        );
        // $menu['user'][] = array(
        //     'li_class' => 'divider',
        //     'sort' => 3,
        //     'active' => 'none'
        // );
        $menu['user'][] = array(
            'text' => _("Logout"),
            'icon' => 'logout',
            'path' => 'user/logout',
            'order' => 4,
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
