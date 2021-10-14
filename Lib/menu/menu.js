var menu = {

    // Holds the menu object collated from the _menu.php menu definition files
    obj: {},

    // Menu visibility and states
    // These do not currently control the state from startup but are set during startup
    menu_top_visible: true,
    l2_visible: false,
    l3_visible: false,
    l2_min: false,

    last_active_l1: false,

    active_l1: false,
    active_l2: false,
    active_l3: false,
    
    mode: 'auto',
    is_disabled: false,

    auto_hide: true,    
    auto_hide_timer: null,
    
    // ------------------------------------------------------------------
    // Init Menu
    // ------------------------------------------------------------------    
    init: function(obj,session) {
        var q_parts = q.split("#");
        q_parts = q.split("?");
        q_parts = q_parts[0].split("/");
        var controller = false; if (q_parts[0]!=undefined) controller = q_parts[0];
        
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
                            }
                        }
                    } else {
                        if (menu.obj[l1]['l2'][l2].href.indexOf(controller)===0) {
                            menu.active_l1 = l1;
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
    },

    // ------------------------------------------------------------------    
    // L1 menu is the top bar menu
    // ------------------------------------------------------------------    
    draw_l1: function () {
        console.log("draw_l1");
        // Build level 1 menu (top bar)
        var out = "";
        for (var l1 in menu.obj) {
            let item = menu.obj[l1]
            // Prepare active status
            let active = ""; if (l1==menu.active_l1) active = "active";
            // Prepare icon
            let icon = '<svg class="icon '+item['icon']+'"><use xlink:href="#icon-'+item['icon']+'"></use></svg>';
            // Title
            if (item['name']!=undefined) {
                let title = item['name'];
                if (item['title']!=undefined) title = item['title'];
                // Menu item
                let href='';
                if (item['default']!=undefined) {
                    href = 'href="'+path+item['default']+'"';
                }
                out += '<li><a '+href+' onclick="return false;"><div l1='+l1+' class="'+active+'" title="'+title+'"> '+icon+'<span class="menu-text-l1"> '+item['name']+'</span></div></a></li>';
            }
        }
        $(".menu-l1 ul").html(out);
        
        if (menu.active_l1 && menu.obj[menu.active_l1]['l2']!=undefined) { 
            menu.draw_l2();
        } else { 
            menu.hide_l2();
        }
    },

    // ------------------------------------------------------------------
    // Level 2 (Sidebar)
    // ------------------------------------------------------------------
    draw_l2: function () {
        console.log("draw_l2");
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
                let active = ""; 
                if (q.indexOf(item['href'])===0) { 
                    active = "active"; 
                    menu.active_l2 = l2;
                }
                // Prepare icon
                let icon = "";
                if (item['icon']!=undefined) {
                    icon = '<svg class="icon '+item['icon']+'"><use xlink:href="#icon-'+item['icon']+'"></use></svg>';
                }
                
                // Title
                let title = item['name'];
                if (item['title']!=undefined) title = item['title'];

                // Create link if applicable
                let href = ''
                if (item['l3']==undefined) {
                    href = 'href="'+path+item['href']+'"'
                } else {
                    if (item['default']!=undefined) {
                        href = 'href="'+path+item['default']+'"'
                    }
                }
                // Disable link for active menu items
                if (active=="active") href = '';
                
                // Menu item                
                out += '<li><a '+href+'><div l2='+l2+' class="'+active+'" title="'+title+'"> '+icon+'<span class="menu-text-l2"> '+item['name']+'</span></div></a></li>';
            }
        }
        
        $(".menu-l2 ul").html(out); 
        
        if (menu.l2_min) {
            $(".menu-text-l2").hide();
            $(".menu-title-l2 span").hide();
        } else {
            $(".menu-text-l2").show();
            $(".menu-title-l2 span").show();
        }

        // If menu_l2 open and l2 menu item active and l3 exists: draw l3
        if (menu.active_l2 && menu.obj[menu.active_l1]['l2'][menu.active_l2]!=undefined && menu.obj[menu.active_l1]['l2'][menu.active_l2]['l3']!=undefined) {
            menu.draw_l3();
        }
    },

    // ------------------------------------------------------------------
    // Level 3 (Sidebar submenu)
    // ------------------------------------------------------------------
    draw_l3: function () {
        console.log("draw_l3");
        var out = '<div class="htop"></div><h3 class="l3-title mx-3">'+menu.obj[menu.active_l1]['l2'][menu.active_l2]['name']+'</h3>';
        for (var l3 in menu.obj[menu.active_l1]['l2'][menu.active_l2]['l3']) {
            let item = menu.obj[menu.active_l1]['l2'][menu.active_l2]['l3'][l3];
            // Prepare active status
            let active = ""; 
            if (q.indexOf(item['href'])===0) {
                active = "active";
                menu.active_l3 = l3;
            }
            out += '<li><a href="'+path+item['href']+'" class="'+active+'">'+item['name']+'</a></li>';
        }
        $(".menu-l3 ul").html(out);

        if (menu.active_l2 && menu.l2_min && menu.l2_visible) { 
            menu.show_l3();
        }
    },
    
    hide_menu_top: function () {
        console.log("hide_menu_top");
        $(".menu-top").addClass("menu-top-hide");
        menu.menu_top_visible = false;
    },
    
    show_menu_top: function () {
        console.log("show_menu_top");
        $(".menu-top").removeClass("menu-top-hide");
        menu.menu_top_visible = true;
    },

    hide_l1: function () {
        $(".menu-l1").hide();
    },

    hide_l2: function () {
        console.log("hide_l2");
        clearTimeout(menu.auto_hide_timer);
        if (menu.l3_visible) { 
            menu.hide_l3(); 
        }

        if (menu.l2_visible) {
            $("#menu-l2-controls").hide();
            $(".menu-text-l2").hide();
            $(".menu-title-l2 span").hide();
            $(".menu-l2").css("width","0px");
        }

        $(".content-container").css("margin","46px auto 0 auto");
        menu.l2_visible = false;
    },

    hide_l3: function () {
        console.log("hide_l3");
        clearTimeout(menu.auto_hide_timer);
        if (menu.l3_visible) { 
            $(".menu-l3").css("width","0px");
            $(".menu-l3").css("left","0px");
        }
        if (menu.l2_visible) $(".content-container").css("margin","46px auto 0 50px");
        else $(".content-container").css("margin","46px auto 0 auto");
        menu.l3_visible = false;
    },

    min_l2: function () {
        console.log("min_l2");
        clearTimeout(menu.auto_hide_timer);
        if (!(menu.l2_visible && menu.l2_min)) {
            $(".menu-l2").css("width","50px");

            $(".menu-text-l2").hide();
            $(".menu-title-l2 span").hide();
            var ctrl = $("#menu-l2-controls");
            ctrl.html('<svg class="icon"><use xlink:href="#icon-expand"></use></svg>');
            ctrl.attr("title","Expand sidebar").removeClass("ctrl-exp").addClass("ctrl-min");
            ctrl.show();
        }

        var window_width = $(window).width();
        var max_width = $(".content-container").css("max-width").replace("px","");
        if (max_width=='none' || window_width<max_width) {
            $(".content-container").css("margin","46px 0 0 50px");
        } else {
            $(".content-container").css("margin","46px auto 0 50px");
        }

        menu.l2_min = true;
        menu.l2_visible = true;
    },

    // If we expand l2 we also hide l3
    exp_l2: function () {
        console.log("exp_l2");
        if (menu.l3_visible) { 
            menu.hide_l3();
        }
        
        if (!(menu.l2_visible && menu.l2_min == false)) {
            $(".menu-l2").css("width","240px");

            $(".menu-text-l2").show();
            $(".menu-title-l2 span").show();
            var ctrl = $("#menu-l2-controls");
            ctrl.html('<svg class="icon"><use xlink:href="#icon-contract"></use></svg>');
            ctrl.attr("title","Minimise sidebar").removeClass("ctrl-min").addClass("ctrl-exp");
            ctrl.show();

        }
        var left = 240;
        if (menu.width<1150) { 
            left = 50;
            clearTimeout(menu.auto_hide_timer);
            menu.auto_hide_timer = setTimeout(function(){ if (menu.auto_hide && !menu.l3_visible) { menu.auto_hide = false; menu.min_l2(); } } ,4000); // auto hide 
        }
        $(".content-container").css("margin","46px 0 0 "+left+"px");
        
        menu.l2_min = false;
        menu.l2_visible = true;
    },

    // If we show l3, min l2
    show_l3: function () {
        console.log("show_l3");
        if (!menu.l2_min || !menu.l2_visible) menu.min_l2();

        $(".menu-l3").css("left","50px"); 
        $(".menu-l3").css("width","280px");

        var left = 280 + 50;
        if (menu.width<1150) { 
            left = 50;
            clearTimeout(menu.auto_hide_timer);
            menu.auto_hide_timer = setTimeout(function(){ if (menu.auto_hide && menu.l3_visible) { menu.auto_hide = false; menu.hide_l3();} } ,4000); // auto hide 
        }
        $(".content-container").css("margin","46px 0 0 "+left+"px");

        menu.l3_visible = true;
    },

    resize: function() {
        console.log("resize");
        menu.width = $(window).width();
        menu.height = $(window).height();
        
        if (!menu.is_disabled && menu.menu_top_visible) {
            if (menu.mode=='auto') {
                menu.auto_hide = true;
                if (menu.width>=576 && menu.width<1150) {
                    if (menu.active_l3) {
                        menu.show_l3();
                    }
                    else if (menu.active_l2) {
                        menu.min_l2();
                    }
                } else if (menu.width<576) {
                    menu.hide_l2();
                } else {
                    if (menu.active_l3) {
                        menu.show_l3();
                    }
                    else if (menu.active_l2) {
                        menu.exp_l2();
                    }
                }
            }
            if (menu.width>=1150 && menu.l2_visible && (!menu.l2_min || menu.l3_visible)) {
                menu.mode = 'auto'
            }
            
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
            console.log("menu-l1 li div");
            menu.last_active_l1 = menu.active_l1;
            menu.active_l1 = $(this).attr("l1");
            let item = menu.obj[menu.active_l1];
            // Remove active class from all menu items
            $(".menu-l1 li div").removeClass("active");
            $(".menu-l1 li div[l1="+menu.active_l1+"]").addClass("active");
            // If no sub menu then menu item is a direct link
            menu.mode = 'manual'
            if (item['l2']==undefined) {
                window.location = path+item['href']
            } else {
                if (menu.active_l1!=menu.last_active_l1) {
                    // new l1 menu clicked
                    menu.min_l2();
                    menu.auto_hide = true;
                    menu.draw_l2();
                    if (item['l2'][menu.active_l2] != undefined && item['l2'][menu.active_l2]['l3']!=undefined) {
                        menu.show_l3();
                    }
                    else { 
                        menu.hide_l3();
                        menu.exp_l2();
                        
                    }
                } else {
                    // same l1 menu clicked
                    menu.auto_hide = false;
                    if (!menu.l2_visible) {
                        if (item['l2'][menu.active_l2] != undefined && item['l2'][menu.active_l2]['l3']!=undefined) {
                            menu.min_l2();
                            menu.show_l3();
                        } else {
                            menu.exp_l2();
                        }
                    } else if (menu.l2_visible && !menu.l2_min) {
                        menu.min_l2();
                    } else if (menu.l2_visible && !menu.l3_visible && menu.l2_min) { 
                        menu.hide_l2();
                    } else if (menu.l2_visible && menu.l2_min && menu.l3_visible) {
                        menu.min_l2();
                        menu.hide_l3();
                    }
                }
                $(window).trigger('resize');
            }
        });

        $(".menu-l2").on("click","li div",function(event){
            console.log("menu-l2.li div");
            let is_active = ($(this).attr("class") == "active" ? true : false);
            menu.active_l2 = $(this).attr("l2");
            let item = menu.obj[menu.active_l1]['l2'][menu.active_l2];
            // Remove active class from all menu items
            $(".menu-l2 li div").removeClass("active");
            // Set active class to current menu
            $(".menu-l2 li div[l2="+menu.active_l2+"]").addClass("active");
            // If no sub menu then menu item is a direct link
            if (item['l3']!=undefined) {
                menu.mode = 'manual'
                menu.auto_hide = false;
                if (is_active && menu.active_l2 && !menu.l3_visible) {
                    // Expand sub menu
                    menu.show_l3();
                } else {
                    menu.hide_l3();
                }
                $(window).trigger('resize');
            } else {
                if (menu.active_l2 && menu.l3_visible) {
                    menu.hide_l3(); // must be a direct link, dont triger resize here
                }
            }
        });

        $("#menu-l2-controls").click(function(event){
            console.log("menu-l2-controls");
            event.stopPropagation();
            menu.mode = 'manual'
            menu.auto_hide = true;
            if (menu.l2_visible && menu.l2_min) {
                menu.exp_l2();
            } else {
                menu.min_l2();
            }
            $(window).trigger('resize');
        });
        
        $(window).resize(function(){
            menu.resize();
        });
        
        $(window).scroll(function() {
          var scrollTop = $(window).scrollTop();
          var main = 0;
          if ((scrollTop > main) && menu.menu_top_visible && !menu.l2_visible && !menu.l3_visible) { 
              menu.hide_menu_top();
          } else if (scrollTop <= main && !menu.menu_top_visible) {
              menu.show_menu_top();
          }
        });
    },
    
    route: function(q) {
        var route = {
            controller: false,
            action: false,
            subaction: false
        }
        
        var q_parts = q.split("#");
        q_parts = q_parts[0].split("/");
        
        if (q_parts[0]!=undefined) route.controller = q_parts[0];
        if (q_parts[1]!=undefined) route.action = q_parts[0];
        if (q_parts[2]!=undefined) route.subaction = q_parts[0];
                
        return route
    }
};
