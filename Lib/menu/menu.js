var menu = {

    // Holds the menu object collated from the _menu.php menu definition files
    obj: {},

    // Menu visibility and states
    // These do not currently control the state from startup but are set during startup
    l2_visible: false,
    l3_visible: false,
    l2_min: false,

    last_active_l1: false,
    last_active_l2: false,
    last_active_l3: false,

    active_l1: false,
    active_l2: false,
    active_l3: false,
    
    mode: 'auto',
    
    is_disabled: false,
    
    // html5 browser storage: checked in init
    store: false,
    
    // ------------------------------------------------------------------
    // Init Menu
    // ------------------------------------------------------------------    
    init: function(obj,session) {
    
        // html5 browser storage: used to user preferences for menu
        if (typeof(Storage)!=="undefined") menu.store = true;
        
        var q_parts = q.split("#");
        q_parts = q_parts[0].split("/");
        var controller = false; if (q_parts[0]!=undefined) controller = q_parts[0];
        
        
        console.log(controller)
        
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
                if (menu.obj[l1].href!=undefined && menu.obj[l1].href.indexOf(controller)===0) menu.active_l1 = l1;
            }
        }

        menu.draw_l1();
        menu.events();
        menu.resize();

        // Initial state of l2 menu
        /*
        if (menu.store && localStorage.menu_l2_min=='true') {
            $(".menu-l2").css('transition','none');
            menu.min_l2();
            $(".menu-l2").css('transition','all 0.3s ease-out');
        }*/
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
            // Title
            let title = item['name'];
            if (item['title']!=undefined) title = item['title'];
            // Menu item
            out += '<li><div l1='+l1+' class="'+active+'" title="'+title+'"> '+icon+'<span class="menu-text-l1"> '+item['name']+'</span></div></li>';
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
        var menu_title_l2 = menu.obj[menu.active_l1]['name'];
        if (menu_title_l2=="Setup") menu_title_l2 = "Emoncms";
        var out = '<h4 class="menu-title-l2"><span>'+menu_title_l2+'</span></h4>';
        for (var z in keys) {
            let l2 = keys[z];
            let item = menu.obj[menu.active_l1]['l2'][l2];
            
            if (item['divider']!=undefined && item['divider']) {
                out += '<li style="height:'+item['divider']+'"></li>';
            } else {
                // Prepare active status
                let active = ""; if (q.indexOf(item['href'])===0) { active = "active"; menu.active_l2 = l2 }
                // Prepare icon
                let icon = "";
                if (item['icon']!=undefined) {
                    icon = '<svg class="icon '+item['icon']+'"><use xlink:href="#icon-'+item['icon']+'"></use></svg>';
                }
               // Title
                let title = item['name'];
                if (item['title']!=undefined) title = item['title'];
                // Menu item
                out += '<li><div l2='+l2+' class="'+active+'" title="'+title+'"> '+icon+'<span class="menu-text-l2"> '+item['name']+'</span></div></li>';
            }
        }
        
        $(".menu-l2 ul").html(out); 
        
        if (menu.active_l2 && menu.obj[menu.active_l1]['l2'][menu.active_l2]!=undefined && menu.obj[menu.active_l1]['l2'][menu.active_l2]['l3']!=undefined) menu.draw_l3();
    },

    // ------------------------------------------------------------------
    // Level 3 (Sidebar submenu)
    // ------------------------------------------------------------------
    draw_l3: function () {
        var out = '<div class="htop"></div><h3 class="l3-title mx-3" style="color:#aaa">'+menu.obj[menu.active_l1]['l2'][menu.active_l2]['name']+'</h3>';
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
        $(".menu-title-l2 span").hide();
        
        var window_width = $(window).width();
        var max_width = $(".content-container").css("max-width").replace("px","");
        
        if (max_width=='none' || window_width<max_width) {
            $(".content-container").css("margin","46px 0 0 50px");
        } else {
            $(".content-container").css("margin","46px auto 0 auto");
        }
        
        var ctrl = $("#menu-l2-controls");
        ctrl.html('<svg class="icon"><use xlink:href="#icon-expand"></use></svg>');
        ctrl.attr("title","Expand sidebar").removeClass("ctrl-exp").addClass("ctrl-min");
    },

    // If we expand l2 we also hide l3
    exp_l2: function () {
        if (menu.l2_min) setTimeout(function(){ $(".menu-text-l2").show(); $(".menu-title-l2 span").show(); },200);
        menu.l2_min = false;
        menu.l2_visible = true;
        menu.hide_l3();
        $(".menu-l2").show();
        $(".menu-l2").css("width","240px");
        var left = 240;
        if (menu.width<1150) left = 50;
        $(".content-container").css("margin","46px 0 0 "+left+"px");

        var ctrl = $("#menu-l2-controls");
        ctrl.html('<svg class="icon"><use xlink:href="#icon-contract"></use></svg>');
        ctrl.attr("title","Minimise sidebar").removeClass("ctrl-min").addClass("ctrl-exp");
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
            //if (menu.store && localStorage.menu_l2_min=='true') {
            //    menu.min_l2();
            //} else {
            
                if (menu.mode=='auto') {
                    if (menu.width>=576 && menu.width<992) {
                        menu.min_l2();
                    } else if (menu.width<576) {
                        menu.hide_l2();
                        menu.hide_l3();
                        
                    } else {
                        //if (menu.store && localStorage.menu_l2_min=='true') {
                        
                        //} else {
                            if (!menu.l3_visible) menu.exp_l2();
                        //}
                    }
                }
                
                if (menu.width>=992 && menu.l2_visible && (!menu.l2_min || menu.l3_visible)) {
                    menu.mode = 'auto'
                }
                
                
            //}
            
            if (menu.width<576) {
                $(".menu-text-l1").hide();
            } else {
                $(".menu-text-l1").show();
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
            $(".menu-l1 li div[l1="+menu.active_l1+"]").addClass("active");
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
            // Store l2 state to localStorage
            // if (menu.store) localStorage.menu_l2_min = menu.l2_min;
            menu.mode = 'manual'
        });

        $(".menu-l2").on("click","li div",function(event){
            menu.active_l2 = $(this).attr("l2");
            let item = menu.obj[menu.active_l1]['l2'][menu.active_l2];
            // Remove active class from all menu items
            $(".menu-l2 li div").removeClass("active");
            // Set active class to current menu
            $(".menu-l2 li div[l2="+menu.active_l2+"]").addClass("active");
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
        
        $("#menu-l2-controls").click(function(event){
            event.stopPropagation();
            if (menu.l2_visible && menu.l2_min) {
                menu.exp_l2();
            } else {
                menu.min_l2();
            }
            // Store l2 state to localStorage
            if (menu.store) localStorage.menu_l2_min = menu.l2_min;
            menu.mode = 'manual'
        });
        
        /*
        $(".menu-l2").click(function(){
            if (menu.l2_min && menu.l3_visible) {
                menu.hide_l3();
            } else if (menu.l2_min && !menu.l3_visible) {
                menu.hide_l2();
            } else {
                menu.min_l2();
            }
            // Store l2 state to localStorage
            if (menu.store) localStorage.menu_l2_min = menu.l2_min;
        });*/
        
        $(window).resize(function(){
            menu.resize();
        });
    }
};
