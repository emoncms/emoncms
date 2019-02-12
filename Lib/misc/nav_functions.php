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
    $title = getKeyValue('title', $params);
    $class = getKeyValue('class', $params);
    $li_class = getKeyValue('li_class', $params);
    $li_id = getKeyValue('li_id', $params);
    $icon = getKeyValue('icon', $params);
    $active = (array) getKeyValue('active', $params);

    if(empty($title)) $title = $text;

    $link = makeLink(array(
        'text'=> $text,
        'title'=> $title,
        'class'=> $class,
        'id'=> $id,
        'icon'=> $icon,
        'path'=> $path,
        'active'=> $active
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
        'id'=>$li_id,
        'class'=>$li_class
    ));
    return sprintf('<li %s>%s</li>', $attr, $link);
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
 *
 * @param string $text
 * @param string $path
 * @param string $title
 * @param string $css
 * @param string $id
 * @return string <a> tag
 */
function makeLink($params) {
    $text = getKeyValue('text', $params);
    $path = getKeyValue('path', $params);
    $title = getKeyValue('title', $params);
    $class = getKeyValue('class', $params);
    $id = getKeyValue('id', $params);
    $icon = getKeyValue('icon', $params);
    $active = getKeyValue('active', $params);
    $href = getKeyValue('href', $params);
    $data = getKeyValue('data', $params);
    if(empty($href)) $href = getAbsoluteUrl($path);

    // append icon to link text
    if (!empty($icon)) {
        $icon = sprintf('<svg class="icon"><use xlink:href="#icon-%s"></use></svg> ',$icon);
        $text = $icon . $text;
    }

    if (true) {
        $class = addCssClass('active',$class);
    }

    $attr = buildAttributes(array(
        'id'=>$id,
        'href'=>$href,
        'title'=>$title,
        'class'=>$class,
        'data'=>$data
    ));
    if(!empty($attr)) $attr = ' '.$attr;
    
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
