<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/

global $path, $session, $route, $user;
if (!isset($session['profile'])) {
    $session['profile'] = 0;
}

// --- LINK BUILDING CODE ---

/**
 * build <li><a> style nav link with 'active' class added if is current page
 *
 * @param array $params assoc array: text,path,title,css,id,icon
 * @return string <li><a> link
 */
function makeListLink($params) {
    global $route;
    $activeClassName = 'active';

    $li_id = getKeyValue('li_id', $params);
    $li_class = array_filter( (array) getKeyValue('li_class', $params));
    $li_style = array_filter( (array) getKeyValue('li_style', $params));
    $li_attr = array_filter( (array) getKeyValue('li_attr', $params));

    $id = getKeyValue('id', $params);
    $text = getKeyValue('text', $params);
    $path = getKeyValue('path', $params);
    $href = getKeyValue('href', $params);
    $title = getKeyValue('title', $params);
    $icon = getKeyValue('icon', $params);
    $active = getAbsoluteUrl(getKeyValue('active', $params));
    $sub_items = array_filter( (array) getKeyValue('sub_items', $params));
    $style = array_filter( (array) getKeyValue('style', $params));
    $class = array_filter( (array) getKeyValue('class', $params));
    $data = array_filter( (array) getKeyValue('data', $params));
    $attr = array_filter( (array) getKeyValue('attr', $params));
    $data['active'] = $active;
    
    if(is_current($path) || is_current($active) || is_active($params)){
        $li_class[] = $activeClassName;
    }

    if(empty($title)) $title = $text;

    $link = makeLink(array(
        'text'=> $text,
        'title'=> $title,
        'class'=> $class,
        'id'=> $id,
        'icon'=> $icon,
        'href'=> $href,
        'path'=> $path,
        'active'=> $active,
        'data'=> $data,
        'style'=> $style,
        'attr'=> $attr
    ));
    $attr = buildAttributes(array_merge($li_attr, array(
        'id' => $li_id,
        'class' => implode(' ', array_unique($li_class)),
        'style' => implode(';', $li_style)
    )));
    if(!empty($attr)) $attr = ' '.$attr;

    if(!empty($sub_items)) {
        foreach($sub_items as $key=>$item) {
            if(is_array($item)) {
                $sub_items[$key] = makeListLink($item);
            }
        }
        $link .= '<ul class="dropdown-menu">'.implode("\n", $sub_items).'</ul>';
    }
    return empty($link) ? '' : sprintf('<li%s>%s</li>', $attr, $link);
}
/**
 * returns true if current view's url matches passed $path(s)
 *
 * @param mixed $_url single or list of urls to check
 * @return boolean
 */
function is_current($_url = array()) {
    $current_url = getAbsoluteUrl($_SERVER['REQUEST_URI']);
    foreach((array) $_url as $search) {
        $search_url = getAbsoluteUrl($search);
        if($search_url === $current_url) {
            return true;
        }
    }
    return false;
}
/**
 * returns true if current view's path matches passed $path(s)
 *
 * @param mixed $path - array can be passed to match multiple paths
 * @return boolean
 */
function path_is_current($path) {
    $current_path = current_route();
    foreach((array) $path as $search) {
        $search_path = parse_url(getAbsoluteUrl($search), PHP_URL_PATH);
        if ($search_path === $current_path || count(explode('/',$search_path)) > 3 
            && strpos($current_path, $search_path) === 0)
        {
            return true;
        }
    }
    return false;
}
/**
 * return the current route path
 *
 * @return void
 */
function current_route() {
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
}
/**
 * return $array[$key] value if not empty
 * else return empty string
 *
 * @param string $key
 * @param array $array
 * @return mixed
 */
function getKeyValue($key, $array) {
    return isset($array[$key]) ? $array[$key] : '';
}
/**
 * return string with new line and multiple tabs
 * eg: tab(3) would return "\n\t\t\t"
 *
 * @param integer $num number of tabs to return
 * @return string
 */
function tab($num){
    $tabspaces = 4;
    return PHP_EOL.str_pad('',$num*$tabspaces," ");
}
/**
 * build <a> link with 'active' class added if is current page
 * $params = assoc array with keys: [text|path|title|class|id|icon|active|href|data]
 *
 * @param array $params associative array
 * @return string <a> tag
 */
function makeLink($params) {
    $activeClassName = 'active';
    
    $text = getKeyValue('text', $params);
    $path = getKeyValue('path', $params);
    $href = getKeyValue('href', $params);
    $title = getKeyValue('title', $params);
    $id = getKeyValue('id', $params);
    $icon = getKeyValue('icon', $params);
    $active = getKeyValue('active', $params);
    
    $style = array_filter((array) getKeyValue('style', $params));
    $class = array_filter((array) getKeyValue('class', $params));
    $data = array_filter((array) getKeyValue('data', $params));
    $attr = array_filter((array) getKeyValue('attr', $params));

    // create url if pre-built url not passed
    if(empty($href)) $href = getAbsoluteUrl($path);
    
    // append icon to link text
    if (!empty($icon)) {
        $icon = sprintf('<svg class="icon %1$s"><use xlink:href="#icon-%1$s"></use></svg> ', $icon);
        if(empty($text)){
            $text = sprintf('%s', $icon);
        }else{
            $text = sprintf('%s<span class="ml-1 flex-fill">%s</span>', $icon, $text);
        }
        $class[] = 'd-flex flex-nowrap justify-items-between';
    }
    // add active class to link if link is to current page
    if (!empty($active)) {
        if(is_current($active)){
            $class[] = $activeClassName;
        }
    } elseif(is_current($path)){
        $class[] = $activeClassName;
    }
    
    // create the <a> tag attribute list
    $attr = buildAttributes(array_merge($attr, array(
        'id'=>$id,
        'href'=>$href,
        'style'=>implode(';', $style),
        'title'=>$title,
        'class'=>implode(' ', $class),
        'data'=>$data
    )));
    // exit function if no href value available
    if(empty($href)) return $text;

    // return <a> tag with all the attributes and child elements
    return sprintf('<a %s>%s</a>', $attr, $text);
}
/**
 * return html element attribute string (eg. key1="value1" key2="value2")
 * if value is array multiples are grouped (eg. key="value1 value2")
 * if value is array and key is "data" then array values are prefixed with data-[key]. (eg. data-name="value" data-size="value")
 * 
 * example of use:
 * ---------------
 * simple pairs: buildAttributes(array('id'=>'menu-item-2'))  ==> 'id="menu-item-2"'
 * data-* style:  buildAttributes(array('data'=>array('toggle'=>'collapse','target'=>'#sidebar')))
 *  ==> 'data-toggle="collapse" data-target="#sidebar"'
 * grouped style: buildAttributes(array('class'=>array('dark','large')))  ==> 'class="dark large"'
 *
 * @param array $attributes
 * @return string
 */
function buildAttributes($attributes){
    return implode(' ', array_filter( array_map(function($key) use ($attributes) {
        $value = $attributes[$key];
        // print_r($value);
        if (!empty($value)) {
            if(!is_array($value)) {
                // return simple key=value pair
                return $key.'="'.$value.'"';
            } else {
                if(isSequential($value)){
                    // join multi-value properties
                    // eg css="a b c" etc
                    $list[$key] = implode(' ', array_unique($value));
                } else {
                    foreach($value as $key2=>$value2) {
                        if($key==='data') $key2 = 'data-'.$key2;
                        // add the data-* attribute 
                        // eg data-'close' data-'open'
                        $list[$key2] = $value2;
                    }
                }
                // call itself array values as strings
                return buildAttributes($list);
            }
        }
    }, array_keys($attributes))));
}

/**
 * return full url of given relative path of controller/action
 *
 * @param string $_path
 * @return string eg. http://localhost/emoncms/feed/list
 */
function getAbsoluteUrl($_passedPath) {
    if(empty($_passedPath)) return '';
    global $path;
    // if passed path ($_passedPath) begins with /emoncms remove it
    $_passedPathParts = getPathParts($_passedPath);
    // if first path part is 'emoncms' remove it
    if(getKeyValue(0, $_passedPathParts)=='emoncms') {
        array_shift($_passedPathParts);
    }
    // add to global $path 
    $url = $path . implode('/', $_passedPathParts);
    
    // url query parts
    $query_parts = getQueryParts($_passedPath);
    // return emoncms $path with the relative $_passedPath component;
    foreach($query_parts as $key=>$value) {
        $q[] = sprintf("%s=%s", $key, $value);
    }
    // add query parts of url. eg. ?q=bluetooth+mouse&sort=asc
    if(!empty($q)) {
        $query = implode('&', $q);
        $url .= '?' . $query;
    }
    // encode the url parts like a application/x-www-form-urlencoded
    return encodePath($url);
}

// /**
//  * add a css class name to a given list (if not already there)
//  *
//  * @param string $classname
//  * @param mixed $css array | string
//  * @return string
//  */
// function addCssClass($classname, $css) {
//     if(!is_array($css)) $css = explode(' ', $css);
//     $css = array_unique(array_filter($css));
//     if (!in_array($classname, $css)){
//         $css[] = $classname;
//     }
//     $css = implode(' ', $css);
//     return $css;
// }

/**
 * for development only
 *
 * @param string $key - print sub array if $key supplied
 * @return void - exits php after printing array
 */
function debugMenu($key = '') {
    global $menu;
    echo "<pre>";
    if(!empty($key) && isset($menu[$key]) && $key !== 'includes'){
        printf("%s:\n-------------\n",strtoupper($key));
        print_r($menu[$key]);
    } else {
        print_r($menu);
    }
    exit('eof debugMenu()');
}
/**
 * sort all the menus individually. items without sort are added to bottom a-z
 * calls itself again until $array is a menu item
 *
 * @param array $array
 * @return void
 */
function sortMenu (&$menus) {
    foreach($menus as $name=>&$menu) {
        // includes don't follow same structure
        if ($name === 'includes') return;
        
        // if $menu has numeric keys it has menu items
        // eg $menu[0]
        if (isSequential($menu)) {
            // collect indexes for unordered items
            $orders = array();
            $unordered = array();
            if(is_array($menu)) {
                foreach($menu as $key=>&$item) {
                    encodeMenuItemUrl($item);
                    $item['path'] = getAbsoluteUrl($item['path']);

                    if (isset($item['order'])) {
                        $orders[] = $item['order'];
                    } else {
                        $unordered[] = $key;
                    }
                }
            }
            // get next sort (max_order) for a menu
            $next_order = !empty($orders) ? max($orders)+1: 0;
            
            // set order field for un-ordered menu items
            foreach($unordered as $index) {
                // sort by title if available
                if(isset($menu[$index]['text'])) {
                    $menu[$index]['order'] = $menu[$index]['text'];
                } else {
                // sort by integer if not title available
                    $menu[$index]['order'] = $next_order++;
                }
            }
            // re-index menu based on 'order' value
            if(is_array($menu)) usort($menu, 'sortMenuItems');

        // if $menu has alpha-numeric keys it is a menu group
        // eg. $menu['setup']
        } else {
            // call sort again for sub-menus
            sortMenu($menu);
        }
    }
}

/**
 * url encoding a give menu item's path
 *
 * @param array $item
 * @return void;
 */
function encodeMenuItemUrl(&$item) {
    if(isset($item['path'])) {
        $item['path'] = encodePath($item['path']);
    } else {
        $item['path'] = '';
    }
}
/**
 * return url encoded string
 * 
 * individually encode the parts of given $path string
 *
 * @param string $path
 * @return string
 */
function encodePath($path){
    // split url into parts
    $parts = parse_url($path);
    // encode url path parts
    if(isset($parts['path'])) {
        $path_parts = [];
        foreach(getPathParts($path) as $p) {
            $path_parts[] = urlencode($p);
        }
        $parts['path'] = implode('/', $path_parts);
    }
    
    if(isset($parts['query'])) {
        $query_parts = getQueryParts($path);
        $query_parts = array_map('urldecode', $query_parts);
        $parts['query'] = http_build_query($query_parts);
    }

    $url = (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') . 
    ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') . 
    (isset($parts['host']) ? "{$parts['host']}" : '') . 
    (isset($parts['port']) ? ":{$parts['port']}" : '') . 
    (isset($parts['path']) ? "/{$parts['path']}" : '') . 
    (isset($parts['query']) ? "?{$parts['query']}" : '');

    return $url;
}

/**
 * return true if all array keys are not a string (aka 'non-associative' array)
 *
 * @param array $array
 * @return boolean
 */ 
function isSequential($array = array()) {
    $array = (array) $array;
    $string_keys = array_filter(array_keys($array), 'is_string');
    return count($string_keys) == 0;
}

/**
 * used as usort() sorting function
 * 
 * return -1 if $a['order'] is less than $b['order']
 * return 0 if $a['order'] is equal to $b['order']
 * return 1 if $a['order'] is greater than $b['order']
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function sortMenuItems ($a, $b) {
    $orderby = 'order';
    $ac = getKeyValue($orderby, $a);
    $bc = getKeyValue($orderby, $b);
    return strcmp($ac, $bc);
}

/**
 * return true if menu item path is the current page or $passed_path
 *
 * @param array $item - menu item with ['path'] property
 *                      if not array passed attempt to locate the menu item by passed string
 *                      defaults to current route if empty
 * @param string $passed_path - check menu item against this url
 * @return boolean
 */
function is_active($item = null, $passed_path = null) {
    global $route, $path;
    $slash = '/';
    $base = !empty($passed_path) ? $passed_path: $path;
    // if passed item is not an array look it up by path
    if (!is_array($item)) $item = getSidebarItem($item);
    if (!$item) $item = getCurrentMenuItem();
    // remove the full $path from the link's absolute url
    $_path = str_replace($base, '', getKeyValue('path', $item));
    $_active = str_replace($base, '', getKeyValue('active', $item));
    $q = !empty($route->query) ? "?".$route->query: '';
    // check for different combos of controllers and actions for a match
    if ($_path === implode($slash, array_filter(array($route->controller, $route->action, $route->subaction, $route->subaction2))) ||
        $_path === implode($slash, array_filter(array($route->controller, $route->action))).$q ||
        $_active === implode($slash, array_filter(array($route->controller))) ) {
        return true;
    }
    return false;
}
/**
 * return true if passed $item has desired properties for bing a menu item
 *
 * @param array $item
 * @return boolean
 */
function is_menu_item($item) {
    if(is_array($item) && (
        !empty($item['id'])   ||
        !empty($item['path']) ||
        !empty($item['href']) ||
        !empty($item['icon']) ||
        !empty($item['text']) ||
        !empty($item['title'])
    )) {
        return true;
    }
    return false;
}
/**
 * call the makeListLink() function after modifiying the menu item to act as a dropdown
 *
 * @param array $item - $menu array item
 * @return void
 */
function makeDropdown($item){
    global $session;
    // add empty text value to avoid title from being used
    if(empty($item['text'])) $item['text'] = '';
    
    // add empty title value to avoid text with icon from being used
    if(empty($item['title'])) $item['title'] = $item['text'];
    
    // add the dropdown indicator
    // $item['text'] .= ' <b class="caret"></b>';

    // add the correct class to the <li>
    $item['li_class'][] = 'dropdown';
    
    if(is_current_menu($item['sub_items'])){
        $item['li_class'][] = 'active';
    }
    
    // create variable if empty
    if(!isset($item['class'])) $item['class'] = '';
    
    // add additional css classes to <li>
    settype($item['class'], 'array');
    $item['class'][] = 'dropdown-toggle';

    // add data-* attributes
    $item['data']['toggle'] = 'dropdown';
    
    // return <li><a> with sub <ul><li><a>

    return makeListLink($item);
}

/**
 * return a clickable link that opens / closes sidebar
 *
 * @param [type] $item
 * @return void
 */
function sidebarCollapseBtn($item) {
    global $sidebar_collapsed;
    if(!empty($sidebar_collapsed) && $sidebar_collapsed){
        $item['class'][] = 'collapsed';
    }
    $item['data']['toggle'] = 'slide-collapse';
    $item['data']['target'] = '#sidebar';
    $item['href'] = '#';
    echo makeLink($item);
}
/**
 * return array that holds the current pages's menu item
 *
 * @return void
 */
function getCurrentMenuItem(){
    global $menu;
    if(!empty(getCurrentMenuItemIndex())) {
        list($group,$_menu,$index) = getCurrentMenuItemIndex()[0];
        return !empty($menu[$group][$_menu][$index]) ? $menu[$group][$_menu][$index]: array();
    }
    return array();
}
/**
 * return menu that contains the current menu item
 * returns empty array if not found
 * returns first occurance of path only
 *
 * @return void
 */
function getCurrentMenu() {
    global $menu;
    $index = getCurrentMenuItemIndex();
    if(!empty($index)) {
        list($group,$_menu,$index) = $index[0];
        return $menu[$group][$_menu];
    } else {
        return array();
    }
}
/**
 * return an array of indexes that identify the current page's menu item
 *
 * @return void
 */
function getCurrentMenuItemIndex(){
    global $menu;
    $keys = array();
    foreach($menu as $key=>$item){
        foreach($item as $key2=>$item2){
            foreach($item2 as $key3=>$item3){
                $path = getKeyValue('path',$item3);
                if(is_current($path)){
                    $keys[] = array($key, $key2, $key3);
                }
            }
        }
    }
    return $keys;
}

/**
 * return an array of indexes
 * where the matching "path" is found the menu
 *
 * @param string $path
 * @param string $group index for menu group - all groups checked if empty
 * @return array
 */
function getRouteMenuItemIndex($path='', $group='') {
    global $menu;
    echo "getindex: $path,$group\n";
    if (empty($path)) $_path = current_route();
    $_menu = empty($group) ? $menu : $menu[$group];

    $search = getAbsoluteUrl($path);
    $results = array();

    foreach ($_menu as $key=>$item){
        foreach ($item as $key2=>$item2) {
            if (is_menu_item($item2)) {
                if($search == getAbsoluteUrl(getKeyValue('path',$item2))){
                    $results[] = array($key, $key2, null);
                }
            } else {
                foreach($item2 as $key3=>$item3){
                    if($search == getKeyValue('path',$item3)){
                        $results[] = array($key, $key2, $key3);
                    }
                }
            }
        }
    }
    return $results;
}

/**
 * return a menu item matching a given path
 * @note: was using getRouteMenuItemIndex() to return the menu items. now uses searchArray()
 *
 * @param string $path
 * @return array
 */
function getSidebarItem($path) {
    global $menu;
    $results = array();
    foreach($menu['sidebar'] as $name=>$sidebar) {
        foreach (searchArray($sidebar, 'path', getAbsoluteUrl($path)) as $result) {
            $results[] = $result;
        }
    }
    $results = array_values(array_filter($results));
    if(count($results)==1) $results = $results[0];
    return $results;
}

/**
 * return array of matching elements based on key an value
 *
 * @param array $array
 * @param string $key
 * @param mixed $value
 * @return array
 */
function searchArray($array, $key, $value) {
    $results = array();
    if (is_array($array)) {
        // if match found add to results
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }
        // search within array items as another source
        foreach ($array as $subarray) {
            $results = array_merge($results, searchArray($subarray, $key, $value));
        }
    }
    return $results;
}
/**
 * return true if current page requires a sidebar
 *
 * @return void
 */
function currentPageRequiresSidebar(){
    $currentMenuItem = getCurrentMenuItem();
    $currentPath = getKeyValue('path',$currentMenuItem);
    return pathRequiresSidebar($currentPath);
}
/**
 * return true if passed path requires a sidebar
 *
 * @param string $path
 * @return void
 */
function pathRequiresSidebar($path){
    global $route, $menu;
    $counter = 0;
    foreach($menu['setup'] as $item):
        if(is_current($item)){
            $counter++;
        }
    endforeach;
    if(!empty($menu[$route->controller])): foreach($menu[$route->controller] as $item):
        if(is_current($item)){
            $counter++;
        }
    endforeach; endif;
    if(!empty($menu['includes'][$route->controller])): foreach($menu['includes'][$route->controller] as $item):
        $counter++;
    endforeach; endif;
    return $counter > 0;
}
/**
 * return array of url path split by forward slash (/)
 * empty elements removed from array
 *
 * @param string $url if empty current route used
 * @return array
 */
function getPathParts($path='') {
    if(empty($path)) $path = current_route();
    $path = parse_url($path, PHP_URL_PATH);
    // separate the path by a forward slash
    $pathParts = explode('/', $path);
    return array_values(array_filter($pathParts));
}
/**
 * return the first part of the route path (controller name)
 * eg: if $path is /emoncms/input/view function will return "input"
 *
 * @param string $path
 * @return string
 */
function getPathController($path='') {
    // getPathParts return array with 'emoncms' as the first
    $parts = getPathParts($path);
    array_shift($parts); // drop off the first segment
    return array_shift($parts); // return the original 2nd segment
}

/**
 * return array of url query parameters in given url
 * empty elements removed from array
 *
 * @param string $url
 * @return array
 */
function getQueryParts($path) {
    $query = parse_url($path, PHP_URL_QUERY);
    $query_items = array();
    foreach(explode('&', $query) as $item) {
        if (strpos($item,'=') > -1) {
            list($key, $value) = explode('=', $item);
            $query_items[$key] = $value;
        }
    }
    return array_filter($query_items);
}

/**
 * return true if current route is in given list of menu items
 * 
 * @param array $menu
 * @return bool
 */
function is_current_menu($_menu = array()) {
    foreach($_menu as $k=>$item){
        $_path = getKeyValue('path', $item);
        $_active = getKeyValue('active', $item);
        if(is_current($_path) || is_current($_active)|| is_active($item)) return true;
    }
    return false;
}

/**
 * return true if current route is in given list of menus
 * 
 * matches on path only eg. /feed/list
 * ignores url query eg. ?sort=asc
 *
 * @param array $menu
 * @return bool
 */
function is_current_group($group) {
    if(empty($group)) return false;
    foreach($group as $menu) {
        if (is_current_menu($menu)) return true;
    }
    return false;
}

/**
 * return true if passed menu item has children/siblings that are currently being viewed
 *
 * @param array $item
 * @return boolean
 */
function hasActiveChildren($item = '') {
    if(empty($item)) {
        $item = getCurrentMenuItem();
    }
    $children = getChildMenuItems($item);
    // echo "\n-emrys--".count($children);
    foreach($children as $child) {
        $path = getKeyValue('path', $child);
        if(is_current($path) || is_active($path)){
            return true;
        }
    }
    return false;
}
/**
 * return true if current route points to active menu item within sub-menu
 *
 * @param mixed $input string or array of menu items
 * @return bool
 */
function thirdLevelActive($input) {
    // function accepts multiple inputs types. ensure they are all the same
    $items = array(); // list to store all the inputs;

    if (is_menu_item($input)) {
        // if single menu item passed in add it to the list
        $items[] = $input;
    } elseif (!is_array($input)) {
        // if <html> string passed in add it ot the list
        $items[] = $input;
    }

    foreach($items as $item){
        if (is_menu_item($item)) {
            return true;
            // if menu item has no children, 3rd level hidden
            if(empty(getChildMenuItems($item))) {
                return false;
            }
            // if is 3rd level link and is currently active, 3rd level visible
            if(is_third_level($item) && is_active($item)) {
                return true;
            }
            // if menu item is active and has children, 3rd level visible
            if(is_active($item) && !empty(getChildMenuItems($item))) {
                return true;
            }
        } else {
            return true;
        }
    }
    return false;
}
/**
 * return true if passed item's path is in a 3rd level menu
 *
 * @param [type] $item
 * @return boolean
 */
function is_third_level($item) {
    global $menu;
    list($group,$m,$k) = getCurrentMenuItemIndex()[0];
    $path = getAbsoluteUrl(current_route());
    
    /* example structure in menu files of 3rd level menu
    --------------------------------------------------------
    $menu['sidebar']['includes']['setup']['sync'][] = array(
        'text' => _("Inputs"),
        'path' => 'sync/view/inputs'
    );
    */
    if(!empty($_menu = $menu[$group]['includes'])) {
        if(is_array($_menu)) {
            foreach($_menu as $_name=>$items) {
                if(is_array($items)) {
                    foreach($items as $k=>$sub_items) {
                        if(is_array($sub_items)) {
                            foreach($sub_items as $third_level) {
                                $_path = getKeyValue('path', $third_level);
                                $_url = getAbsoluteUrl($_path);
                                if($_url==$path) {
                                    // passed menu item matches 3rd level menu item
                                    return true;
                                }
                            }
                        } else {
                            return true;
                        }
                    }
                }
            }
        }
    }
    return false;
}

/**
 * if a menu item has a [data][sidebar] property, return matching sidebar menu
 *
 * @param array $parent parent menu item
 * @return array
 */
function getChildMenuItems($parent) {
    global $menu;
    $children = array();
    if(!empty($parent['data']['sidebar'])) {
        $child_selector = $parent['data']['sidebar'];
        $menu_key = str_replace('#sidebar_','',$child_selector);
        
        foreach($menu['sidebar'] as $menu_key2 => $sub_menu) {
            if(!empty($menu['sidebar']['includes'][$menu_key2][$menu_key])){
                $children[] = $menu['sidebar']['includes'][$menu_key2][$menu_key];
            }
            foreach($sub_menu as $item) {
                if ($menu_key == $menu_key2) {
                    $children[] = $item;
                }
            }
        }
    }
    return $children;
}
/**
 * check if any menu items match the current request path
 *
 * @param [type] $child
 * @return boolean
 */
function hasActiveParents($child) {
    global $menu;
    $parents = getParents($child);
    foreach($parents as $parent) {
        $parentUrl = getKeyValue('path',$parent);
        $routeUrl = getAbsoluteUrl(current_route());
        if($parentUrl === $routeUrl) {
            return true;
        }
    }
    return false;
}
/**
 * return list of menu items that share the same controller 
 *
 * @param array $child a menu item
 * @return array
 */
function getParents($child) {
    global $menu;
    $parents = array();
    foreach($menu['sidebar'] as $sub_menu) {
        foreach($sub_menu as $item) {
            $path1 = getKeyValue('path',$child);
            $path2 = getKeyValue('path',$item);

            $url1 = getAbsoluteUrl($path1);
            $url2 = getAbsoluteUrl($path2);

            $controller1 = getPathController($path1);
            $controller2 = getPathController($path2);

            // save matching controllers. ignore self
            if($controller1 === $controller2 && $url1 != $url2) {
                $parents[] = $item;
            }
        }
    }
    return $parents;
}

/**
 * return true if current route is bookmarked by user
 *
 * @return void
 */
function currentPageIsBookmarked(){
    // @todo: change this to retreive current bookmarks when user logs in
    global $mysqli, $user, $session, $route;
    require_once "Modules/dashboard/dashboard_model.php";
    $current_path = str_replace('/emoncms/','',current_route());
    if(!empty($route->query)) $current_path.='?'.$route->query;

    if($bookmarks = $user->getUserBookmarks($session['userid'])) {
        foreach($bookmarks as $b) {
            if (!empty($b['path']) && $b['path']===$current_path){
                return true;
            }
        }
    }
    
    if($dashboard_bookmarks = getUserBookmarkedDashboards($session['userid'])) {
        foreach($dashboard_bookmarks as $b) {
            if (!empty($b['path']) && $b['path']===$current_path){
                return true;
            }
        }
    }
    return false;
}


function getUserBookmarkedDashboards($userid){
    // @todo: should this be in user model or dashboard model??
    global $mysqli, $user;
    $bookmarks = array();
    require_once "Modules/dashboard/dashboard_model.php";
    $dashboard = new Dashboard($mysqli);
    $default_dashboard = array();
    foreach($dashboard->get_list($userid,false,false) as $item){
        if($item['main']===true){
            $default_dash = $item;
        }
        if($item['published']===true){
            $fav_dash[] = $item;
        }
    }
    $orderbase = 1  ;

    // ADD DEFAULT DASHBOARD
    if (!empty($default_dash)) {
        $bookmarks[] = array(
            'text' => _('Main Dashboard'),
            'title'=> sprintf('%s - %s',$default_dash['name'], $default_dash['description']),
            'icon' => 'star',
            'order'=> $orderbase,
            'path' => 'dashboard/view?id='.$default_dash['id']
        );
    }
    // ADD BOOKMARKED DASHBOARDS
    if (!empty($fav_dash)) {
        foreach($fav_dash as $fav) {
            $bookmarks[] = array(
                'text' => $fav['name'],
                'path' => 'dashboard/view?id='.$fav['id'],
                'order'=> $orderbase++,
                'title'=> $fav['description']
            );
        }
    }
    return $bookmarks;
}