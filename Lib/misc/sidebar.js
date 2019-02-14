// function init_sidebar(options){

//     default_options = {
//         max_wrapper_width: 1150,
//         sidebar_enabled: true,
//         sidebar_visible: true,
//         menu_element: "#example_menu"
//     }
//     options = options || {};
//     options = $.extend({}, default_options, options);
//     $menu_element = $(options.menu_element);
    
//     if($menu_element.length>0){
//         $menu_element.find("i").removeClass("icon-home").addClass("icon-list");        
//         $menu_element.parent().click(function(e){
//             e.preventDefault()
//             if (options.sidebar_visible) {
//                 options.sidebar_enabled = false;
//                 hide_sidebar(options);
//             } else {
//                 options.sidebar_enabled = true;
//                 show_sidebar(options);
//             }
//         });
//         sidebar_resize(options);
//         $(window).resize(function(){
//             sidebar_resize(options);
//         });
//     }
// }

// function sidebar_resize(options) {
//     options = options || {}; 
//     options.width = $(window).width();
//     var height = $(window).height();
//     var nav = $(".navbar").height();
    
//     $(".sidenav").height(height-nav);
    
//     if (options.width<options.max_wrapper_width) {
//         hide_sidebar(options)
//     } else {
//         if (options.sidebar_enabled) show_sidebar(options)
//     }
// }

$(function(){
    // recreate the bootstrap collapse... but slide from left
    $(document).on('click', '[data-toggle="slide-collapse"]', function(event) {
        event.preventDefault(event);
        var collapsed = this.classList.contains('collapsed');
        $btn = $(this);
        target = $btn.data('target');
        if (!collapsed) {
            $('[data-toggle="slide-collapse"][data-target="' + target + '"]').addClass('collapsed');
            hide_sidebar();
        } else {
            $('[data-toggle="slide-collapse"][data-target="' + target + '"]').removeClass('collapsed');
            show_sidebar();
        }
    });
    
    // trigger the events to allow module js scripts to attach actions to the events
    function show_sidebar(options) {
        // @note: assumes the css animation takes 300ms
        $('#sidebar').trigger('show.sidebar.collapse');
        setTimeout(function(){
            $('#sidebar').trigger('shown.sidebar.collapse');
        }, 350);
        $('body').removeClass('collapsed');
    }
    function hide_sidebar(options) {
        // @note: assumes the css animation takes 300ms
        $('#sidebar').trigger('hide.sidebar.collapse');
        setTimeout(function(){
            $('#sidebar').trigger('hidden.sidebar.collapse');
        }, 350);
        $('body').addClass('collapsed');
    }

    // open sidebar if active page link clicked
    $('#left-nav li.active a').on('click', function(event){
        event.preventDefault();
        $('#sidebar-toggle').click();
    });

    // on trigger sidebar hide/show
    $('#sidebar').on('hide.sidebar.collapse show.sidebar.collapse', function(event){
        // resize after slight delay
        var interval = setInterval(function(){
            if (typeof graph_resize === 'function'){
                graph_resize();
            }
            if (typeof graph_draw === 'function'){
                graph_draw();
            }
        }, 75);
        // stop resizing
        setTimeout(function(){
            clearInterval(interval);
        }, 300);
    });

    // on finish sidebar hide/show
    $('#sidebar').on('hidden.sidebar.collapse shown.sidebar.collapse', function(event){
        // resize once finished animating
        if (typeof graph_resize === 'function'){
            graph_resize();
        }
        if (typeof graph_draw === 'function'){
            graph_draw();
        }
        if (typeof resize === 'function'){
            resize();
        }
    });

    $(window).click(function(event) {
        //Hide the footer menus if visible
        // console.log(event.type,event.target.id);
        if(event.target.id !== 'sidebar_user_toggle'){
            var footer_nav = $('#footer_nav');
            var list = $('#sidebar_user_dropdown');
            var toggle = $('#sidebar_user_toggle');
            if(!list.hasClass('collapsed')) {
                list.collapse('hide');
                toggle.addClass('collapsed')
                footer_nav.removeClass('expanded')
            }
        }
    });

});

// open / close sidebar based on swipe
// disabled on devices with a mouse
// @todo: test on more devices (emrys,-02-14)
var sidebarSwipeOptions = typeof hammerOptions !== 'undefined' ? hammerOptions:{};
var mc_switch = new Hammer(document.querySelector('.sidebar-switch'),sidebarSwipeOptions);
var mc_sidebar = new Hammer(document.querySelector('#sidebar'),sidebarSwipeOptions);
var onSidebarSwipe = function(event) {
    // console.log('hammer js ' + event.type + ' event');
    document.querySelector('#sidebar-toggle').click()
}
mc_switch.on('swiperight', onSidebarSwipe);
mc_sidebar.on('swipeleft', onSidebarSwipe);