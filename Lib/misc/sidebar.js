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
            sidebar_resize(options);
        });
    }
}

function sidebar_resize(options) {
    options.width = $(window).width();
    var height = $(window).height();
    var nav = $(".navbar").height();
    
    $(".sidenav").height(height-nav);
    
    if (options.width<options.max_wrapper_width) {
        hide_sidebar(options)
    } else {
        if (options.sidebar_enabled) show_sidebar(options)
    }
}

function show_sidebar(options) {
    options = options || {};
    options.sidebar_visible = true;
    $(".sidenav").css("left","250px");
    if (options.width>=options.max_wrapper_width) $("#wrapper").css("padding-left","250px");
    $("#wrapper").css("margin","0");
    $(".sidenav-open").hide();
    $(".sidenav-close").hide();
    $(".menu-overlay").fadeIn(500);
}

function hide_sidebar(options) {
    options = options || {};
    options.sidebar_visible = false;
    $(".sidenav").css("left","0");
    $("#wrapper").css("padding-left","0");
    $("#wrapper").css("margin","0 auto");
    $(".sidenav-open").show();
    $(".menu-overlay").fadeOut(200);
}

$(document).on('click', '[data-toggle="slide-collapse"].collapsed', function(event) {
    $btn = $(this);
    target = $btn.data('target');
    $('[data-toggle="slide-collapse"][data-target="' + target + '"]').removeClass('collapsed');
    event.preventDefault();
    hide_sidebar();
});

$(document).on('click', '[data-toggle="slide-collapse"]:not(".collapsed")', function(event) {
    $btn = $(this);
    target = $btn.data('target');
    $('[data-toggle="slide-collapse"][data-target="' + target + '"]').addClass('collapsed');
    event.preventDefault();
    show_sidebar();
});