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
            "l2": [
                {"name":"Feeds","href":"feed/view","order":2,"icon":"format_list_bulleted"},
                {"name":"Graph","href":"graph","icon":"show_chart","order":3,"l3":[
                    {"name":"A","href":"graph/A"},
                    {"name":"B","href":"graph/B"},
                    {"name":"C","href":"graph/C"}
                ]},
                {"name":"Inputs","href":"input/view","order":1,"icon":"input"}
            ]
        },
        "app": {
            "name": "Apps",
            "order": 2,
            "icon": "apps",
            "l2": [
                {"name":"App A","href":"app/view#A","icon":"show_chart","order":1},
                {"name":"App B","href":"app/view#B","icon":"show_chart","order":2},
                {"name":"App C","href":"app/view#C","icon":"show_chart","order":3}
            ]
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

    l3_visible: false,
    l2_min: false,

    active_l1: false,
    active_l2: false,
    active_l3: false,
    
    // ------------------------------------------------------------------
    // Init Menu
    // ------------------------------------------------------------------    
    init: function(obj) {
        
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
                            if (menu.obj[l1]['l2'][l2]['l3'][l3].href.indexOf(q)===0) {
                                menu.active_l1 = l1;
                                //active_l2 = l2;
                                //active_l3 = l3;
                            }
                        }
                    } else {
                        if (menu.obj[l1]['l2'][l2].href.indexOf(q)===0) {
                            menu.active_l1 = l1;
                            // active_l2 = l2;
                        }
                    }
                }
            } else {
                if (menu.obj[l1].href.indexOf(q)===0) menu.active_l1 = l1;
            }
        }

        menu.draw_l1();
        menu.events();
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
        menu.obj[menu.active_l1]['l2'].sort(function(a, b) {
            return a["order"] - b["order"];
        });

        // Build level 2 menu (sidebar)
        var out = "";
        for (var l2 in menu.obj[menu.active_l1]['l2']) {
            let item = menu.obj[menu.active_l1]['l2'][l2];
            
            // Prepare active status
            let active = ""; if (q.indexOf(item['href'])===0) { active = "active"; menu.active_l2 = l2 }
            // Prepare icon
            let icon = '<svg class="icon '+item['icon']+'"><use xlink:href="#icon-'+item['icon']+'"></use></svg>';
            // Menu item
            out += '<li><div l2='+l2+' class="'+active+'" title='+item['name']+'> '+icon+'<span class="menu-text-l2"> '+item['name']+'</span></div></li>';
        }
        $(".menu-l2 ul").html(out); 

        menu.exp_l2();

        if (menu.active_l2 && menu.obj[menu.active_l1]['l2'][menu.active_l2]['l3']!=undefined) menu.draw_l3();
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

    // If we minimise l2 we also hide l3
    min_l2: function () {
        menu.l2_min = true;
        menu.l3_visible = false;
        $(".menu-l2").css("width","50px");
        $(".menu-l3").hide();
        $(".menu-text-l2").hide();
    },

    // If we expand l2 we also hide l3
    exp_l2: function () {
        menu.l2_min = false;
        menu.hide_l3();
    },

    hide_l2: function () {
        $(".menu-l2").hide();
        $(".menu-l3").hide();
    },

    show_l2: function () {
        $(".menu-l2").show();
        $(".menu-l3").show();
    },

    // If we show l3, l2_min = false moves back to expanded l2
    show_l3: function () {
        menu.l3_visible = true;
        menu.l2_min = false;
        $(".menu-l2").css("width","50px");
        $(".menu-l3").show();
        $(".menu-text-l2").hide();
    },

    // If we hide l3 - l2 expands
    hide_l3: function () {
        menu.l3_visible = false;
        $(".menu-l2").css("width","250px");
        $(".menu-l3").hide();
        $(".menu-text-l2").show();
    },

    // -----------------------------------------------------------------------
    // Menu events
    // -----------------------------------------------------------------------    
    events: function() {

        $(".menu-l1 li div").click(function(event){
            menu.active_l1 = $(this).attr("l1");
            let item = menu.obj[menu.active_l1];
            // Remove active class from all menu items
            $(".menu-l1 li div").removeClass("active");
            $(menu).addClass("active");
            // If no sub menu then menu item is a direct link
            if (item['l2']==undefined) {
                window.location = path+item['href']
            } else {
                menu.show_l2();
                menu.exp_l2();
                menu.draw_l2();
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
                    // Show level 3 menu
                    menu.show_l3();
                } else {
                    menu.hide_l3();
                }
            }
        });

        $(".menu-l2").on("click","li",function(event){
            event.stopPropagation();
        });

        $(".menu-l2").click(function(){
            if (menu.l2_min) {
                menu.exp_l2();
            } else {
                menu.min_l2();
            }
        });
    }
};
