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

    $id = getKeyValue('id', $params);
    $text = getKeyValue('text', $params);
    $path = getKeyValue('path', $params);
    $href = getKeyValue('href', $params);
    $style = getKeyValue('style', $params);
    $li_style = getKeyValue('li_style', $params);
    $title = getKeyValue('title', $params);
    $class = getKeyValue('class', $params);
    $li_class = getKeyValue('li_class', $params);
    $li_id = getKeyValue('li_id', $params);
    $icon = getKeyValue('icon', $params);
    $active = getKeyValue('active', $params);
    $data = getKeyValue('data', $params);
    $sub_items = getKeyValue('sub_items', $params);

    if(empty($active)){
        $path_parts = explode('/', $path);
        $active = array($path_parts[0]);
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

    // partial match current url with fragment '$active'
    if (!empty($active)) {
        if(is_current($active)){
            $li_class = addCssClass($activeClassName, $li_class);
        }
    } elseif(is_current($path)){
        $li_class = addCssClass($activeClassName, $li_class);
    }

    $attr = buildAttributes(array(
        'id' => $li_id,
        'class' => $li_class,
        'style' => $li_style
    ));
    if(!empty($attr)) $attr = ' '.$attr;

    if(!empty($sub_items)) {
        $link .= '<ul class="dropdown-menu">'.implode("\n", $sub_items).'</ul>';
    }
    return sprintf('<li%s>%s</li>', $attr, $link);
}
function is_current($path) {
    $current_path = getCurrentPath();

    if (is_array($path)) {
        foreach($path as $search) {
            if (!empty($search) && strpos($current_path, $search) > -1) {
                return true;
            }
        }
    } else {
        $url = getAbsoluteUrl($path);
        return $url === $current_path;
    }
}
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
    $style = getKeyValue('style', $params);
    $title = getKeyValue('title', $params);
    $class = getKeyValue('class', $params);
    $id = getKeyValue('id', $params);
    $icon = getKeyValue('icon', $params);
    $active = getKeyValue('active', $params);
    $data = getKeyValue('data', $params);
    
    // create url if pre-built url not passed
    if(empty($href)) $href = getAbsoluteUrl($path);

    // append icon to link text
    if (!empty($icon)) {
        $icon = sprintf('<svg class="icon"><use xlink:href="#icon-%s"></use></svg> ',$icon);
        $text = $icon . $text;
    }
    // add active class to link if link is to current page
    if (!empty($active)) {
        if(is_current($active)){
            $class = addCssClass($activeClassName, $class);
        }
    } elseif(is_current($path)){
        $class = addCssClass($activeClassName, $class);
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
    // pad attributes with space before adding to the output
    if(!empty($attr)) $attr = ' '.$attr;
    
    // exit function if no href value available
    if(empty($href)) return $text;

    // return <a> tag with all the attributes and child elements
    return sprintf('<a%s>%s</a>', $attr, $text);
}
/**
 * return array as key=value pairs
 *
 * @param array $attributes
 * @return string
 */
function buildAttributes($attributes){
    return implode(' ', array_map(function($key) use ($attributes) {
        if (!empty($attributes[$key])) {
            if($key!=='data') {
                return $key.'="'.$attributes[$key].'"';
            } else {
                // add the data-* attribute names to array of data[] items
                foreach($attributes[$key] as $key2=>$value2) {
                    $data['data-'.$key2] = $value2;
                }
                return buildAttributes($data);
            }
        }
    }, array_keys($attributes)));
}

/**
 * return full url of given relative path of controller/action
 *
 * @param string $_path
 * @return string
 */
function getAbsoluteUrl($_path) {
    if(empty($_path)) return '';
    global $path;
    $url = rtrim($path.$_path, '/');
    return $url;
}
/**
 * add a css class name to a given list (if not already there)
 *
 * @param string $classname
 * @param string $css
 * @return string
 */
function addCssClass($classname, $css = '') {
    $css = explode(' ', $css);
    $css = array_unique(array_filter($css));
    if (!in_array($classname, $css)){
        $css[] = $classname;
    }
    $css = implode(' ', $css);
    return $css;
}
/**
 * get current path from $route parts
 *
 * @return string
 */
function getCurrentPath(){
    global $path, $route;
    $spearator = '/';
    $parts[] = $path;
    $parts[] = $route->controller;
    $parts[] = $route->action;
    $parts = array_filter($parts);
    $parts = array_map(function($val) use ($spearator){
        return rtrim($val, $spearator);
    }, $parts);

    // return implode($spearator, $parts);
    return $_SERVER['REQUEST_URI'];
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
    if(!empty($key) && isset($menu[$key])){
        printf("%s:\n-------------\n",strtoupper($key));
        print_r($menu[$key]);
    } else {
        print_r($menu);
    }
    exit();
}


function sortMenuArrays (array &$array = array()) {
    if(!isSequential($array)){
        usort($array, 'sortMenuItems');
    }
    foreach($array as $key=>&$item){
        if(is_array($item)) {
            sortMenuArrays($item);
        }
    }
}

/**
 * return true if all array keys are not a string (aka 'non-associative' array)
 *
 * @param array $array
 * @return boolean
 */ 
function isSequential(array $array = array()) {
    return count(array_filter(array_keys($array), 'is_string'))!== 0;
}

/**
 * used as usort() sorting function
 * 
 * return -1 if $a['sort'] is less than $b['sort']
 * return 0 if $a['sort'] is equal to $b['sort']
 * return 1 if $a['sort'] is greater than $b['sort']
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function sortMenuItems ($a, $b) {
    $key = 'sort';
    if (!isset($a[$key]) || !isset($b[$key])) {
        $key = 'order';
    }
    if (!isset($a[$key]) || !isset($b[$key])) {
        return 0;
    }
    if($a[$key] == $b[$key]) {
        return 0;
    }
    return ($a[$key] < $b[$key]) ? -1 : 1;
}

/**
 * return true if menu item path is the current page
 *
 * @param array $item
 * @return boolean
 */
function is_active($item) {
    global $route;
    if (isset($item['path']) && ($item['path'] == $route->controller || $item['path'] == $route->controller."/".$route->action || $item['path'] == $route->controller."/".$route->action."/".$route->subaction || $item['path'] == $route->controller."/".$route->action."&id=".get('id')))
        return true;
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
    $item['text'] .= ' <b class="caret"></b>';
    // add the correct class to the <li>
    $item['li_class'] .= ' dropdown';
    // create variable if empty
    if(empty($item['class'])) $item['class'] = '';
    // add additional css classes to <li>
    addCssClass('dropdown-toggle', $item['class']);
    // add data-* attributes
    $item['data']['toggle'] = 'dropdown';
    // return <li><a> with sub <ul><li><a>
    return makeListLink($item);
}