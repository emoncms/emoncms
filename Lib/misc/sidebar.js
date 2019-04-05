$(function(){
    // re-create the bootstrap collapse... but slide from left
    $(document).on('click', '[data-toggle="slide-collapse"]', function(event) {
        event.preventDefault(event);
        var collapsed = this.classList.contains('collapsed');
        $btn = $(this);
        target = $btn.data('target');
        $('[data-toggle="slide-collapse"][data-target="' + target + '"]').toggleClass('collapsed', collapsed);
        if (!collapsed) {
            hide_sidebar();
        } else {
            show_sidebar();
        }
    });

    // open sidebar if active page link clicked
    $('#left-nav li a').on('click', function(event){
        event.preventDefault();
        const $link = $(this);
        const $sidebar_inner = $($link.data('sidebar')); // (.sidebar_inner)
        const activeClass = 'active';

        // show 2nd level - if on 3rd level
        let secondlevel_menuitems = $('.sidebar-menu > li.collapse').length;
        let open_secondlevel_menuitems = $('.sidebar-menu > li.collapse.in').length
        let thirdLevelOpen = secondlevel_menuitems !== open_secondlevel_menuitems;

        // alter tab states
        $link.parent().addClass(activeClass).siblings().removeClass(activeClass);

        // hide if not sidebar found
        if ($sidebar_inner.length == 0) {
            hide_sidebar();
        } else {
            if ($('body').hasClass('collapsed')) {
                // closed sidebar
                show_sidebar();
                $sidebar_inner.addClass(activeClass).siblings().removeClass(activeClass)
            } else {
                // already open sidebar
                if ($sidebar_inner.hasClass(activeClass)) {
                    // hide sidebar if clicked item already active and on 2nd level
                    if(!thirdLevelOpen) {
                        hide_sidebar();
                    } else {
                        $('.third-level-indicator').toggleClass('hidden', true);
                        hideMenuItems(event);
                    }
                } else {
                    // enable correct sidebar inner based on clicked tab
                    $sidebar_inner.addClass(activeClass).siblings().removeClass(activeClass);
                }
            }
        }
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
    // hide sidebar on smaller devices
    window.addEventListener('resize', function(event) {
        if ($(window).width() < 870) {
            hide_sidebar();
            document.body.classList.add('narrow');
        }
        if ($(window).width() >= 870 && $(document.body).hasClass('collapsed')) {
            show_sidebar();
            document.body.classList.remove('narrow');
        }
    })
    
    // hide sidebar on load on narrow devices
    if ($(window).width() < 870) {
        document.body.classList.add('narrow','collapsed');
        $('#sidebar').trigger('hidden.sidebar.collapse');
        hide_sidebar();
        // allow narrow screens to expand sidebar after delay to avoid animation of hiding sidebar
        setTimeout(function(){
            document.body.classList.add('has-animation');
        }, 500);
    }
    /** 
     * If menu 3rd level menu shown shrink 2nd level entries
     * 
     * @param {object} [event] mouse event if triggered by click
     */
    function hideMenuItems(event) {
        var clicked = false;
        if (typeof event !== 'undefined') {
            event.preventDefault();
            event.stopPropagation();
            clicked = true;
        }
        let link = $('#menu-setup li.active a');
        if (clicked) {
            // hide the back arrow from active third level links - on click
            $(event.target).parents('.nav').first().find('.active .third-level-indicator').toggleClass('hidden');
        }
        let active_menu = link.parents('.sidebar-menu').first();
        if(active_menu.length !== 1) return; // no menu found
        
        let active_menu_name = active_menu.attr('id').split('-');
        active_menu_name.shift();
        if(typeof path === 'undefined') {
            var path = '';
        }
        let relative_path = window.location.pathname.replace(path,''); // eg /emoncms/feed/list
        let controller = relative_path.replace('/emoncms/','').split('/')[0]; // eg. feed
        let include_id = [active_menu_name,controller,'sidebar','include'].join('-'); // eg. setup-feed-sidebar-include
        let include = $('#' + include_id);

        if (include.length == 1 && clicked) {
            // show 3rd level menu
            include.toggleClass('in');
            // hide 2nd level menu items
            $('#menu-setup li').not('.active').toggleClass('in');
        }
    }

    // show/hide sidebar includes
    $(document).on('click', '#menu-setup li.active a', hideMenuItems);
    // show hide 2nd / 3rd menu items
    setTimeout(hideMenuItems, 100);
    
}); // end of jquery ready()


    
// trigger the events to allow module js scripts to attach actions to the events
function show_sidebar(options) {
    // @note: assumes the css animation takes 300ms
    $('#sidebar').trigger('show.sidebar.collapse');
    setTimeout(function(){
        $('#sidebar').trigger('shown.sidebar.collapse');
    }, 350);
    $('body').removeClass('collapsed').addClass('expanded');
}
function hide_sidebar(options) {
    // @note: assumes the css animation takes 300ms
    $('#sidebar').trigger('hide.sidebar.collapse');
    setTimeout(function(){
        $('#sidebar').trigger('hidden.sidebar.collapse');
    }, 350);
    $('body').addClass('collapsed').removeClass('expanded');
}


// backward compatible empty function
if(typeof init_sidebar !== 'function') var init_sidebar = function(){}