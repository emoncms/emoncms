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
    $('#left-nav li.active a').on('mouseenter', function(event){
        event.preventDefault();
        if($('body').hasClass('collapsed')) {
            // $('#sidebar-toggle').click();
        }
    });
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

    $('#sidebar_user_dropdown').collapse({'toggle':false});
    $(window).click(function(event) {
        //Hide the footer menus if visible
        if(event.target.id !== 'sidebar_user_toggle'){
            var footer_nav = $('#footer_nav').removeClass('expanded');
            var list = $('#sidebar_user_dropdown').collapse('hide');
            var toggle = $('#sidebar_user_toggle').addClass('collapsed');
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