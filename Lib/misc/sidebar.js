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
    options = options || {}; 
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
    $('#sidebar').trigger('show.sidebar.collapse');
    setTimeout(function(){
        $('#sidebar').trigger('shown.sidebar.collapse');
    }, 350);
    $('body').removeClass('collapsed');
}

$(function(){
    function hide_sidebar(options) {
        $('#sidebar').trigger('hide.sidebar.collapse');
        setTimeout(function(){
            $('#sidebar').trigger('hidden.sidebar.collapse');
        }, 350);
        $('body').addClass('collapsed');
    }

    $(document).on('click', '[data-toggle="slide-collapse"].collapsed', function(event) {
        $btn = $(this);
        target = $btn.data('target');
        $('[data-toggle="slide-collapse"][data-target="' + target + '"]').removeClass('collapsed');
        event.preventDefault();
        show_sidebar();
    });

    // $(document).on('click', '[data-toggle="slide-collapse"]:not(".collapsed")', function(event) {
    $(document).on('click', 'body:not(".collapsed") #sidebar', function(event) {
        event.preventDefault(event);
        $btn = $(this);
        target = $btn.data('target');
        $('[data-toggle="slide-collapse"][data-target="' + target + '"]').addClass('collapsed');
        hide_sidebar();
    });

    // open sidebar if opened
    $('#left-nav li.active a').on('click', function(event){
        event.preventDefault();
        $('#sidebar-toggle').click();
    });

    // on trigger sidebar hide/show
    $('#sidebar').on('hide.sidebar.collapse show.sidebar.collapse', function(event){
        // resize after slight delay
        var interval = setInterval(function(){
            if (typeof graph_resize === 'function') graph_resize();
            if (typeof graph_draw === 'function') graph_resize();
            // if (typeof resize === 'function') resize();
        }, 75);
        // stop resizing
        setTimeout(function(){
            clearInterval(interval);
        }, 300);
    });

    // on finish sidebar hide/show
    $('#sidebar').on('hidden.sidebar.collapse shown.sidebar.collapse', function(event){
        // resize once finished animating
        if (typeof graph_resize === 'function') graph_resize();
        if (typeof graph_draw === 'function') graph_resize();
        if (typeof resize === 'function') resize();
    });

});


var sidebarSwipeOptions = hammerOptions||{};
var mc_switch = new Hammer(document.querySelector('.sidebar-switch'),sidebarSwipeOptions);
var mc_sidebar = new Hammer(document.querySelector('#sidebar'),sidebarSwipeOptions);
var onSidebarSwipe = function(event) {
    // console.log(event.type);
    document.querySelector('[data-toggle="slide-collapse"]').click()
}
mc_switch.on('swiperight', onSidebarSwipe);
mc_sidebar.on('swipeleft', onSidebarSwipe);