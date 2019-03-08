<?php
/*
    All Emoncms code is released under the GNU General Public License v3.
    See COPYRIGHT.txt and LICENSE.txt.
    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project: http://openenergymonitor.org
*/

global $path, $session, $route;
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
    $li_class = (array) getKeyValue('li_class', $params);
    $li_style = (array) getKeyValue('li_style', $params);

    $id = getKeyValue('id', $params);
    $text = getKeyValue('text', $params);
    $path = getKeyValue('path', $params);
    $href = getKeyValue('href', $params);
    $title = getKeyValue('title', $params);
    $icon = getKeyValue('icon', $params);
    $active = getKeyValue('active', $params);

    $sub_items = (array) getKeyValue('sub_items', $params);
    $style = (array) getKeyValue('style', $params);
    $class = (array) getKeyValue('class', $params);
    $data = (array) getKeyValue('data', $params);
    $data = array_filter($data);// clean out empty entries

    // partial match current url with fragment '$active'
    if(empty($active)){
        $path_parts = explode('/', $path);
        $active = array($path_parts[0]);
    }
    if(is_current($path) || is_current($active) || is_active($path)){
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
        'style'=> $style
    ));

    $attr = buildAttributes(array(
        'id' => $li_id,
        'class' => implode(' ', $li_class),
        'style' => implode(';', $li_style)
    ));
    if(!empty($attr)) $attr = ' '.$attr;

    if(!empty($sub_items)) {
        foreach($sub_items as $key=>$item) {
            if(is_array($item)) {
                $sub_items[$key] = makeListLink($item);
            }
        }
        $link .= '<ul class="dropdown-menu">'.implode("\n", $sub_items).'</ul>';
    }
    return sprintf('<li%s>%s</li>', $attr, $link);
}
/**
 * returns true if current view's url matches passed $path(s)
 *
 * @param mixed $path - array can be passed to match multiple paths
 * @return boolean
 */
function is_current($path) {
    $current_url = getAbsoluteUrl($_SERVER['REQUEST_URI']);
    foreach((array) $path as $search) {
        $search_url = getAbsoluteUrl($search);
        if($search_url === $current_url) return true;
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
    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    foreach((array) $path as $search) {
        $search_path = parse_url(getAbsoluteUrl($search), PHP_URL_PATH);
        if (strpos($current_path, $search_path) === 0) return true;
        if($search_path === $current_path) return true;
    }
    return false;
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
    
    $style = (array) getKeyValue('style', $params);
    $class = (array) getKeyValue('class', $params);
    $data = (array) getKeyValue('data', $params);
    // create url if pre-built url not passed
    if(empty($href)) $href = getAbsoluteUrl($path);
    
    // append icon to link text
    if (!empty($icon)) {
        $icon = sprintf('<svg class="icon %1$s"><use xlink:href="#icon-%1$s"></use></svg> ', $icon);
        $text = sprintf('%s<span>%s</span>', $icon, $text);
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
    $attr = buildAttributes(array(
        'id'=>$id,
        'href'=>$href,
        'style'=>$style,
        'title'=>$title,
        'class'=>$class,
        'data'=>$data
    ));
      
    // exit function if no href value available
    if(empty($href)) return $text;

    // return <a> tag with all the attributes and child elements
    return sprintf('<a %s>%s</a>', $attr, $text);
}
/**
 * return array as html element attribute string (eg. key="value" key="value")
 *
 * @param array $attributes
 * @return string
 */
function buildAttributes($attributes){
    return implode(' ', array_filter( array_map(function($key) use ($attributes) {
        $value = $attributes[$key];
        if (!empty($value)) {
            if(!is_array($value)) {
                return $key.'="'.$value.'"';
            } else {
                // add the data-* attribute names to array of data[] items
                foreach($value as $key2=>$value2) {
                    if($key==='data') $key2 = 'data-'.$key2;
                    if(!isSequential($value)) {
                        $list[$key2] = $value2;
                    } else {
                        $list[$key] = $value2;
                    }
                }
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

/**
 * add a css class name to a given list (if not already there)
 *
 * @param string $classname
 * @param mixed $css array | string
 * @return string
 */
function addCssClass($classname, $css) {
    if(!is_array($css)) $css = explode(' ', $css);
    $css = array_unique(array_filter($css));
    if (!in_array($classname, $css)){
        $css[] = $classname;
    }
    $css = implode(' ', $css);
    return $css;
}

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
            foreach($menu as $key=>&$item) {
                encodeMenuItemUrl($item);
                $item['path'] = getAbsoluteUrl($item['path']);

                if (isset($item['order'])) {
                    $orders[] = $item['order'];
                } else {
                    $unordered[] = $key;
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
            usort($menu, 'sortMenuItems');

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
    $base = !empty($passed_path) ? $passed_path: $path;
    // if passed item is not an array look it up by path
    if (!is_array($item)) $item = getSidebarItem($item);
    if (!$item) $item = getCurrentMenuItem();
    // remove the full $path from the link's absolute url
    $_path = str_replace($base, '', getKeyValue('path', $item));
    // check for different combos of controllers and actions for a match
    if (
        $_path == $route->controller ||
        $_path == $route->controller."/".$route->action ||
        $_path == $route->controller."/".$route->action."/".$route->subaction ||
        $_path == $route->controller."/".$route->action."/".$route->subaction."/".$route->subaction2 ||
        $_path == $route->controller."/".$route->action."?".$route->query
    ) {
        return true;
    } else {
        return false;
    }
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
    if (empty($item['li_class'])) $item['li_class'] = '';
    $item['li_class'] = addCssClass('dropdown', $item['li_class']);
    
    // create variable if empty
    if(!isset($item['class'])) $item['class'] = '';
    
    // add additional css classes to <li>
    addCssClass('dropdown-toggle', $item['class']);
    
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
    $match = array();
    $index = getCurrentMenuItemIndex();
    foreach($index as $matches){
        if(isset($matches[$matches[0]][$matches[1]])){
            $match[] = $menu[$matches[0]][$matches[1]];
        }
    }
    return $match;
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
            $path = !empty($item2['path']) ? $item2['path']: '';
            if(is_current($path)){
                $keys[] = array($key, $key2);
            }
        }
    }
    return $keys;
}
/**
 * return a menu item matching a given path
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
 * @param string $url
 * @return array
 */
function getPathParts($path='') {
    $path = parse_url($path, PHP_URL_PATH);
    // separate the path by a forward slash
    $pathParts = explode('/', $path);
    return array_values(array_filter($pathParts));
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
 * matches on path only eg. /feed/list
 * ignores url query eg. ?sort=asc
 *
 * @param array $menu
 * @return bool
 */
function is_current_menu($menu) {
    if(empty($menu)) return false;
    foreach($menu as $item) {
        $_path = parse_url(getKeyValue('path', $item), PHP_URL_PATH);
        if (path_is_current($_path)) return true;
    }
    return false;
}