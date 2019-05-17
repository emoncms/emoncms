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
                        hideMenuItems(event);
                        // @todo: make the sidebar show 2nd level and not hide_sidebar()
                        hide_sidebar();
                    }
                } else {
                    // enable correct sidebar inner based on clicked tab
                    $sidebar_inner.addClass(activeClass).find('li a').each(function(){
                        $(this)[0].tabIndex = "0";
                    })
                    $sidebar_inner.siblings('.sidebar-inner').each(function(i,n) {
                        $(this).removeClass(activeClass);
                        $(this).find('li a').each(function(j,m){
                            $(this)[0].tabIndex = "-1";
                        })
                    })
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
        if (!event) return;

        var $menu = $(event.target).parents('ul').first();
        var clicked = false;
        if (typeof event !== 'undefined') {
            event.preventDefault();
            event.stopPropagation();
            clicked = true;
        }
        let link = $menu.find('li.active a');
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
            $menu.find('li').not('.active').toggleClass('in');
        }
    }
    function getQueryStringValue (key) {  
        return decodeURIComponent(window.location.search.replace(new RegExp("^(?:.*[&\\?]" + encodeURIComponent(key).replace(/[\.\+\*]/g, "\\$&") + "(?:\\=([^&]*))?)?.*$", "i"), "$1"));  
    }
    // add current page to user's bookmark list
    $('#set-bookmark, #remove-bookmark').click(function(event){
        event.preventDefault();
        var bookmarks = [];
        var $nav = $('#footer_nav');
        var currentPage = window.location.href.replace(path,'');
        var key = 'bookmarks';
        var $button = $(this);
        var $icon = $button.find('.icon');
        var remove = $icon.is('.star');
        var currentTitle = $('h2').first().text();
        if(currentTitle.length==0) currentTitle = $('h3').first().text();
        if(getQueryStringValue("name")) {
            currentTitle = decodeURI(getQueryStringValue("name").replace('+',' '));
        }
        // get current bookmarks
        $.get(path+'user/preferences.json',{preferences:key})
        .done(function(data){
            // catch json parsing errors
            bookmarks = data || [];
            if(!remove) {
            // remove current page to bookmarks
                var unique = false;
                for(b in bookmarks) {
                    if (bookmarks[b]['path'] && bookmarks[b]['path'] !== currentPage){
                        unique = true;
                    }
                }
                if(unique || bookmarks.length === 0){
                    bookmarks.push({
                        'path': currentPage,
                        'text': currentTitle
                    })
                }
            } else {
            // remove current page from bookmarks
                var newBookmarks = [];
                for(b in bookmarks) {
                    if (bookmarks[b]['path'] && bookmarks[b]['path'] !== currentPage){
                        newBookmarks.push(bookmarks[b]);
                    }
                }
                bookmarks = newBookmarks;
            }
            // save new user preferences
            $.post(path+'user/preferences.json', {
                preferences: JSON.stringify({
                    bookmarks: bookmarks
                })
            }, function(data) {
                var $menu = $('#sidebar_bookmarks');
                var $template = $('#bookmark_link');
                if(data.success) {
                    // saved successfully change icon
                    $('#set-bookmark, #remove-bookmark').parent().toggleClass('d-none');
                    if(!remove) {
                        // add new item to menu
                        $($template.html()).appendTo($menu)
                        .find('a').attr({
                            href: path+currentPage,
                            title: currentTitle
                        })
                        .text(currentTitle).hide().fadeIn();
                        $menu.trigger('bookmarks:updated');
                        $nav.fadeIn();

                    } else {
                        // remove entry from menu
                        $.each($menu.find('li'), function(n, elem){
                            $li = $(elem);
                            var relative = $li.find('a').attr('href').replace(path,'');
                            if(relative === currentPage) {
                                $li.animate({height:0},function(){
                                    $(this).remove();
                                    $menu.trigger('bookmarks:updated');
                                    setTimeout(function(){
                                        if($menu.find('li').length == 0) {
                                            $nav.fadeOut();
                                        }
                                        // $nav.toggleClass('d-none', $menu.find('li').length == 0);
                                    },400)
                                });
                            }
                        });
                    }
                }
            });
        });
    });

    // show/hide sidebar includes
    $(document).on('click', '#menu-emoncms li.active a', hideMenuItems);
    // show hide 2nd / 3rd menu items
    // setTimeout(hideMenuItems, 100);
    

    // save a cookie to remember user's choice to hide or show the bookmarks
    $('#sidebar_bookmarks').on('show hide', function(event) {
        var bookmarks_collapsed = event.type !== 'show';
        docCookies.setItem('bookmarks_collapsed', bookmarks_collapsed)
    })

    $(document).keyup(function(e) {
        if (e.keyCode == 27) { // escape key maps to keycode `27`
            $('.navbar .dropdown').removeClass('open')
        }
    });

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



// get/set document cookies
var docCookies = {
    getItem: function (sKey) {
      if (!sKey) { return null; }
      return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
    },
    setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
      if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) { return false; }
      var sExpires = "";
      if (vEnd) {
        switch (vEnd.constructor) {
          case Number:
          //   sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
            sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; expires=" + (new Date(vEnd * 1e3 + Date.now())).toUTCString();
            break;
          case String:
            sExpires = "; expires=" + vEnd;
            break;
          case Date:
            sExpires = "; expires=" + vEnd.toUTCString();
            break;
        }
      }
      document.cookie = encodeURIComponent(sKey) + "=" + encodeURIComponent(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
      return true;
    },
    removeItem: function (sKey, sPath, sDomain) {
      if (!this.hasItem(sKey)) { return false; }
      document.cookie = encodeURIComponent(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "");
      return true;
    },
    hasItem: function (sKey) {
      if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) { return false; }
      return (new RegExp("(?:^|;\\s*)" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
    },
    keys: function () {
      var aKeys = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/);
      for (var nLen = aKeys.length, nIdx = 0; nIdx < nLen; nIdx++) { aKeys[nIdx] = decodeURIComponent(aKeys[nIdx]); }
      return aKeys;
    }
  };


