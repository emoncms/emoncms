Over time, Emoncms is in the process to use its own dedicated css/javascript framework. The navigation menu is one major block of this custom approach.

Below are the essential instructions to deal with the navigation menu inside emoncms.

To focus on the menu component as a framework, a small piece of code is available [here](http://github.com/alexandrecuer/frontend), with a [live static demo](http://alexandrecuer.github.io/frontend) .  

# Emoncms Menu System

The Emoncms menu system is primarily drawn on the client side using javascript, from a menu object collated from individual module *modulename_menu.php* files.

The menu system is comprised of 3 levels:

1. Level 1 is the blue top bar, usually containing: Setup, Apps & Dashboards
2. Level 2 is the dark grey side bar.
3. Level 3 is a darker grey extention on the level 2 sidebar providing a further sub-menu.

### Adding a menu to an emoncms module

Example *feed_menu.php*:

    <?php
    global $session;
    if ($session["write"]) {
        $menu["setup"]["l2"]['feed'] = array(
            "name"=>"Feeds",
            "href"=>"feed/view", 
            "order"=>2, 
            "icon"=>"format_list_bulleted"
        );
    }
    
Here we add a menu item for the feeds page to level 2 (sidebar) of the setup menu. The session write check is used to hide this menu item if the user is not logged in with write access.

### Example menu object

- Includes top bar and sidebar menu's in 3 level menu system
- Links can be normal or hash locations

**menu.obj** as seen in Lib/menu/menu.js:
    
    {
        "setup": {
            "name": "Setup",
            "order": 1,
            "icon": "menu",
            "l2": {
                "feed":{"name":"Feeds","href":"feed/view","order":2,"icon":"format_list_bulleted"},
                "graph":{"name":"Graph","href":"graph","icon":"show_chart","order":3,"l3":[
                    {"name":"A","href":"graph/A"},
                    {"name":"B","href":"graph/B"},
                    {"name":"C","href":"graph/C"}
                ]},
                "input":{"name":"Inputs","href":"input/view","order":1,"icon":"input"}
            }
        },
        "app": {
            "name": "Apps",
            "order": 2,
            "icon": "apps",
            "l2": {
                "1":{"name":"App A","href":"app/view#A","icon":"show_chart","order":1},
                "2":{"name":"App B","href":"app/view#B","icon":"show_chart","order":2},
                "3":{"name":"App C","href":"app/view#C","icon":"show_chart","order":3}
            }
        },
        "dashboard": {
            "name": "Dashboard",
            "order": 3,
            "icon": "dashboard",
            "href": "dashboard/view"
        }
    }
