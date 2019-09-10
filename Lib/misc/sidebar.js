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
 
    highlightBookmarkButton();

    // open sidebar if active page link clicked
    $('#left-nav li a').on('click', function(event) {
        // if the link has a [data-is-link] attribute navigate to the link
        if(!event.currentTarget.dataset.isLink) {
            event.preventDefault();
        } else {
            var href = event.currentTarget.href;
            window.location.href = href;
            return false;
        }

        const $link = $(this);
        const $sidebar_inner = $($link.data('sidebar')); // (.sidebar_inner)

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
                if ($('body').hasClass('auto')) $('body').toggleClass('auto manual')
                
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
                    if ($('body').hasClass('auto')) $('body').toggleClass('auto manual')
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
        var timeout = false
        timeout = setTimeout(function(){
            if(timeout) clearTimeout(timeout)
            // if (typeof graph_resize === 'function'){
            //     graph_resize();
            // }
            // if (typeof graph_draw === 'function'){
            //     graph_draw();
            // }
            // @note: assumes the css animation takes 200ms
            if(event.type === 'hide') {
                $('#sidebar').trigger('hidden.sidebar.collapse')
            }else{
                $('#sidebar').trigger('shown.sidebar.collapse')
            }
        }, 200);
    });

    $(document).on('window.resized', function(){
        if ($('body').hasClass('auto')) {
            if ($(window).width() < 870) {
                hide_sidebar();
                document.body.classList.add('narrow');
            }
            if ($(window).width() >= 870 && $(document.body).hasClass('collapsed')) {
                show_sidebar();
                document.body.classList.remove('narrow');
            }
        } else {
            if (!$(document.body).hasClass('collapsed')) {
                if ($(window).width() < 870) {
                    $(".content-container").css("margin","2.7rem 0 0 0");
                } else {
                    $('body').toggleClass('manual auto')
                    $(".content-container").css("margin","2.7rem 0 0 15rem");
                }   
            }
        }
    })

    // hide sidebar on load on narrow devices
    if ($(window).width() < 870) {
        document.body.classList.add('narrow','collapsed');
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
        
        let relative_path = window.location.href.replace(path,''); // eg subtracts http://localhost/emoncms from http://localhost/emoncms/feed/list
        let controller = relative_path.split('/')[0].replace(/(.*)#.*/,'$1'); // eg. feed
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
    $('#set-bookmark, #remove-bookmark').click(function(event) {
        event.preventDefault();
        var bookmarks = [];
        var $nav = $('#footer_nav');
        var currentPage = window.location.href.replace(path,'');
        var key = 'bookmarks';
        var $button = $(this);
        var $icon = $button.find('.icon');
        var remove = $icon.is('.star');
        var currentTitle = $('#sidebar .sidebar-menu li.active a').first().text();
        if(currentTitle.length==0) currentTitle = $('h1').first().text();
        if(currentTitle.length==0) currentTitle = $('h2').first().text();
        if(currentTitle.length==0) currentTitle = $('h3').first().text();
        if(currentTitle.length==0) currentTitle = document.title;
        if(currentTitle.toLowerCase().trim() === 'graphs') {
            let graphName = $('#graphName').val()
            if(graphName.length > 0) currentTitle = graphName
        }
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
                preferences: {
                    bookmarks: JSON.stringify(bookmarks)
                }
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
    $('#sidebar').trigger('show.sidebar.collapse');
    $('body').removeClass('collapsed').addClass('expanded');
    
    if ($(window).width() < 870) {
        $(".content-container").css("margin","2.7rem 0 0 0");
    } else {
        $(".content-container").css("margin","2.7rem 0 0 15rem");
    }
}
function hide_sidebar(options) {
    $('#sidebar').trigger('hide.sidebar.collapse');
    $('body').addClass('collapsed').removeClass('expanded');
    $(".content-container").css("margin","2.7rem auto 0 auto");
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

// if bookmarks has url hash fragment (eg. controller/view/#fragment) - js must be used to show
// @todo : this might not be needed to be done in php now?
// @see : https://github.com/emoncms/emoncms/blob/master/Theme/basic/menu_view.php#L66
function highlightBookmarkButton() {
    if (typeof user_bookmarks !== 'undefined') {
        var currentPageIsBookmarked = false
        for (n in user_bookmarks) {
            let bookmark = user_bookmarks[n]
            if (path + bookmark.path === window.location.href) {
                currentPageIsBookmarked = true
            }
        }
        if (currentPageIsBookmarked) {
            $('#remove-bookmark').parent().removeClass('d-none')
            $('#set-bookmark').parent().addClass('d-none')
        } else {
            $('#remove-bookmark').parent().addClass('d-none')
            $('#set-bookmark').parent().removeClass('d-none')
        }
    }
}

window.addEventListener('hashchange', function(event) {
    highlightBookmarkButton()
});