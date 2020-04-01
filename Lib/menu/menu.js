var menu = {

    /*
    
    Example menu object
    - Includes top bar and sidebar menu's in 3 level menu system
    - Links can be normal or hash locations
    
    {
        "setup": {
            "name": "Setup",
            "order": 1,
            "icon": "menu",
            "l2": {
                "feed":{"name":"Feeds","href":"feed/view","order":2,"icon":"format_list_bulleted"},
                "graph":{"name":"Graph","href":"graph","icon":"show_chart","order":3,"l3":[
                    {"name":"A","href":"graph/A"},
                    {"name":"B","href":"graph/B"},
                    {"name":"C","href":"graph/C"}
                ]},
                "input":{"name":"Inputs","href":"input/view","order":1,"icon":"input"}
            }
        },
        "app": {
            "name": "Apps",
            "order": 2,
            "icon": "apps",
            "l2": {
                "1":{"name":"App A","href":"app/view#A","icon":"show_chart","order":1},
                "2":{"name":"App B","href":"app/view#B","icon":"show_chart","order":2},
                "3":{"name":"App C","href":"app/view#C","icon":"show_chart","order":3}
            }
        },
        "dashboard": {
            "name": "Dashboard",
            "order": 3,
            "icon": "dashboard",
            "href": "dashboard/view"
        }
    }
    
    */

    // Holds the menu object
    obj: {},

    l2_visible: false,
    l3_visible: false,
    l2_min: false,

    last_active_l1: false,
    last_active_l2: false,
    last_active_l3: false,

    active_l1: false,
    active_l2: false,
    active_l3: false,
    
    is_disabled: false,
    
    // ------------------------------------------------------------------
    // Init Menu
    // ------------------------------------------------------------------    
    init: function(obj,session) {
    
        var q_parts = q.split("/");
        var controller = false; if (q_parts[0]!=undefined) controller = q_parts[0];
        // var action = false; if (q_parts[1]!=undefined) action = q_parts[1];

        menu.obj = obj;
        
        // Detect and merge in any custom menu definition created by a view
        if (window.custom_menu!=undefined) {
            for (var l1 in custom_menu) {
                menu.obj[l1]['l2'] = custom_menu[l1]['l2']
            }
        }

        // Detect l1 route on first load
        for (var l1 in menu.obj) {
            if (menu.obj[l1]['l2']!=undefined) {
                for (var l2 in menu.obj[l1]['l2']) {
                    if (menu.obj[l1]['l2'][l2]['l3']!=undefined) {
                        for (var l3 in menu.obj[l1]['l2'][l2]['l3']) {
                            if (menu.obj[l1]['l2'][l2]['l3'][l3].href.indexOf(controller)===0) {
                                menu.active_l1 = l1;
                                //active_l2 = l2;
                                //active_l3 = l3;
                            }
                        }
                    } else {
                        if (menu.obj[l1]['l2'][l2].href.indexOf(controller)===0) {
                            menu.active_l1 = l1;
                            // active_l2 = l2;
                        }
                    }
                }
            } else {
                if (menu.obj[l1].href.indexOf(controller)===0) menu.active_l1 = l1;
            }
        }

        menu.draw_l1();
        menu.events();
        menu.resize();
    },

    // ------------------------------------------------------------------    
    // L1 menu is the top bar menu
    // ------------------------------------------------------------------    
    draw_l1: function () {
        // Sort level 1 by order property
        // menu.sort(function(a, b) {
        //     return a["order"] - b["order"];
        // });

        // Build level 1 menu (top bar)
        var out = "";
        for (var l1 in menu.obj) {
            let item = menu.obj[l1]
            // Prepare active status
            let active = ""; if (l1==menu.active_l1) active = "active";
            // Prepare icon
            let icon = '<svg class="icon '+item['icon']+'"><use xlink:href="#icon-'+item['icon']+'"></use></svg>';
            // Menu item
            out += '<li><div l1='+l1+' class="'+active+'" title='+item['name']+'> '+icon+'<span class="menu-text-l1"> '+item['name']+'</span></div></li>';
            // Build level 3 menu (sidebar sub menu) if active
            // if (active && item['sub']!=undefined) active_l2 = l2;
        }
        $(".menu-l1 ul").html(out);
        
        if (menu.active_l1 && menu.obj[menu.active_l1]['l2']!=undefined) menu.draw_l2(); else menu.hide_l2();
    },

    // ------------------------------------------------------------------
    // Level 2 (Sidebar)
    // ------------------------------------------------------------------
    draw_l2: function () {
        // Sort level 2 by order property
        // build a set of keys first, sort these and then itterate through sorted keys
        var keys = Object.keys(menu.obj[menu.active_l1]['l2']);
        keys = keys.sort(function(a,b){
            return menu.obj[menu.active_l1]['l2'][a]["order"] - menu.obj[menu.active_l1]['l2'][b]["order"];
        });

        // Build level 2 menu (sidebar)
        var out = "";
        for (var z in keys) {
            let l2 = keys[z];
            let item = menu.obj[menu.active_l1]['l2'][l2];
            
            // Prepare active status
            let active = ""; if (q.indexOf(item['href'])===0) { active = "active"; menu.active_l2 = l2 }
            // Prepare icon
            let icon = '<svg class="icon '+item['icon']+'"><use xlink:href="#icon-'+item['icon']+'"></use></svg>';
            // Menu item
            out += '<li><div l2='+l2+' class="'+active+'" title='+item['name']+'> '+icon+'<span class="menu-text-l2"> '+item['name']+'</span></div></li>';
        }
        $(".menu-l2 ul").html(out); 
        
        if (menu.active_l2 && menu.obj[menu.active_l1]['l2'][menu.active_l2]!=undefined && menu.obj[menu.active_l1]['l2'][menu.active_l2]['l3']!=undefined) menu.draw_l3();
    },

    // ------------------------------------------------------------------
    // Level 3 (Sidebar submenu)
    // ------------------------------------------------------------------
    draw_l3: function () {
        var out = "";
        for (var l3 in menu.obj[menu.active_l1]['l2'][menu.active_l2]['l3']) {
            let item = menu.obj[menu.active_l1]['l2'][menu.active_l2]['l3'][l3];
            // Prepare active status
            active = ""; if (q.indexOf(item['href'])===0) active = "active";
            // Menu item
            out += '<li><a href="'+path+item['href']+'" class="'+active+'">'+item['name']+'</a></li>';
        }
        $(".menu-l3 ul").html(out);
        menu.show_l3();
    },
    
    // l2 and l3 hidden (no sidebar)
    
    // l2 exp
    
    // l2 min + l3 exp
    
    // l2 min + l3 hidden
    hide_l1: function () {
        $(".menu-l1").hide();
    },
    
    hide_l2: function () {
        menu.l2_visible = false;
        menu.l3_visible = false;
        $(".menu-l2").hide();
        $(".menu-l3").hide();
        $(".content-container").css("margin","46px auto 0 auto");
    },

    // If we minimise l2 we also hide l3
    min_l2: function () {
        menu.l2_min = true;
        menu.l2_visible = true;
        menu.l3_visible = false;
        $(".menu-l2").show();
        $(".menu-l2").css("width","50px");
        $(".menu-l3").hide();
        $(".menu-text-l2").hide();
        
        var window_width = $(window).width();
        var max_width = $(".content-container").css("max-width").replace("px","");
        
        if (max_width=='none' || window_width<max_width) {
            $(".content-container").css("margin","46px 0 0 50px");
        } else {
            $(".content-container").css("margin","46px auto 0 auto");
        }
    },

    // If we expand l2 we also hide l3
    exp_l2: function () {
        if (menu.l2_min) setTimeout(function(){ $(".menu-text-l2").show(); },200);
        menu.l2_min = false;
        menu.l2_visible = true;
        menu.hide_l3();
        $(".menu-l2").show();
        $(".menu-l2").css("width","240px");
        var left = 240;
        if (menu.width<1150) left = 50;
        $(".content-container").css("margin","46px 0 0 "+left+"px");
    },

    // If we show l3, l2_min = false moves back to expanded l2
    show_l3: function () {
        menu.min_l2();
        menu.l2_visible = true;
        menu.l3_visible = true;
        menu.l2_min = true;
        $(".menu-l2").css("width","50px");
        $(".menu-l3").show();
        $(".menu-text-l2").hide();
        var left = 290;
        if (menu.width<1150) left = 50;
        $(".content-container").css("margin","46px 0 0 "+left+"px");
    },

    // If we hide l3 - l2 expands
    hide_l3: function () {
        menu.l3_visible = false;
        $(".menu-l3").hide();
    },

    resize: function() {
        menu.width = $(window).width();
        menu.height = $(window).height();
        
        if (!menu.is_disabled) {
            if (menu.width>=576 && menu.width<992) {
                menu.min_l2();
            } else if (menu.width<576) {
                menu.hide_l2();
                menu.hide_l3();
            } else {
                menu.exp_l2();
            }
        }
    },
    
    disable: function() {
        menu.is_disabled = true;
        menu.hide_l1();
        menu.hide_l2();
        menu.hide_l3();
    },

    // -----------------------------------------------------------------------
    // Menu events
    // -----------------------------------------------------------------------    
    events: function() {

        $(".menu-l1 li div").click(function(event){
            menu.last_active_l1 = menu.active_l1;
            menu.active_l1 = $(this).attr("l1");
            let item = menu.obj[menu.active_l1];
            // Remove active class from all menu items
            $(".menu-l1 li div").removeClass("active");
            $(menu).addClass("active");
            // If no sub menu then menu item is a direct link
            if (item['l2']==undefined) {
                window.location = path+item['href']
            } else {
                if (menu.active_l1!=menu.last_active_l1) {
                    menu.draw_l2();
                    menu.exp_l2();
                } else {

                    if (!menu.l2_visible) {
                        menu.exp_l2();
                    } else if (menu.l2_visible && !menu.l2_min) {
                        menu.min_l2();
                    } else if (menu.l2_visible && !menu.l3_visible && menu.l2_min) { 
                        menu.hide_l2();
                    } else if (menu.l2_visible && menu.l2_min && menu.l3_visible) {
                        menu.hide_l3();
                    }
                }
            }
        });

        $(".menu-l2").on("click","li div",function(event){
            menu.active_l2 = $(this).attr("l2");
            let item = menu.obj[menu.active_l1]['l2'][menu.active_l2];
            // Remove active class from all menu items
            $(".menu-l2 li div").removeClass("active");
            // Set active class to current menu
            $(menu).addClass("active");
            // If no sub menu then menu item is a direct link
            if (item['l3']==undefined) {
                window.location = path+item['href']
            } else {
                if (!menu.l3_visible) {
                    // Expand sub menu
                    menu.draw_l3();
                } else {
                    menu.min_l2();
                }
            }
        });

        $(".menu-l2").on("click","li",function(event){
            event.stopPropagation();
        });

        $(".menu-l2").click(function(){
            if (menu.l2_min && menu.l3_visible) {
                menu.hide_l3();
            } else if (menu.l2_min && !menu.l3_visible) {
                menu.hide_l2();
            } else {
                menu.min_l2();
            }
        });
        
        $(window).resize(function(){
            menu.resize();
        });
    }
};
