function init_sidebar(options){

    default_options = {
        max_wrapper_width: 1150,
        sidebar_enabled: true,
        sidebar_visible: true,
        menu_element: "#example_menu"
    }
    options = options || {};
    options = $.extend({}, default_options, options);
    $menu_element = $(options.menu_element);
    
    if($menu_element.length>0){
        $menu_element.find("i").removeClass("icon-home").addClass("icon-list");        
        $menu_element.parent().click(function(e){
            e.preventDefault()
            if (options.sidebar_visible) {
                options.sidebar_enabled = false;
                hide_sidebar(options);
            } else {
                options.sidebar_enabled = true;
                show_sidebar(options);
            }
        });
        sidebar_resize(options);
        $(window).resize(function(){
            init_sidebar(options);
        });
    }
}

function sidebar_resize(options) {
    var width = $(window).width();
    var height = $(window).height();
    var nav = $(".navbar").height();
    
    $(".sidenav").height(height-nav);
    
    if (width<options.max_wrapper_width) {
        hide_sidebar(options)
    } else {
        if (options.sidebar_enabled) show_sidebar(options)
    }
}

function show_sidebar(options) {
    var width = $(window).width();
    options.sidebar_visible = true;
    $(".sidenav").css("left","250px");
    if (width>=options.max_wrapper_width) $("#wrapper").css("padding-left","250px");
    $("#wrapper").css("margin","0");
    $(".sidenav-open").hide();
    $(".sidenav-close").hide();
}

function hide_sidebar(options) {
    options.sidebar_visible = false;
    $(".sidenav").css("left","0");
    $("#wrapper").css("padding-left","0");
    $("#wrapper").css("margin","0 auto");
    $(".sidenav-open").show();
}
