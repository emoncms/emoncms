<?php

/*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Route
{
    /**
     * @var string
     */
    public $controller = '';

    /**
     * @var string
     */
    public $action = '';

    /**
     * @var string
     */
    public $subaction = '';

    /**
     * @var string
     */
    public $subaction2 = '';
    
    /**
     * @var string
     */
    public $method = 'GET';

    /**
     * @var string
     */
    public $format = 'html';

    /**
     * <ul><li><a> list of links to be shown below main menu
     *
     * @var string
     */
    public $sidebar = '';

    /**
     * @var bool
     */
    public $is_ajax = false;

    /**
     * @param string $q
     * @param string $documentRoot
     * @param string $requestMethod
     */
    public function __construct($q, $documentRoot, $requestMethod)
    {
        $this->decode($q, $documentRoot, $requestMethod);
        //this can be faked by the client. not to be trusted.
        $this->is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * @param  string $q
     * @param string $documentRoot
     * @param string $requestMethod
     */
    public function decode($q, $documentRoot, $requestMethod)
    {
        // filter out the applications relative root

        // If we're running in a subdirectory "emoncms", $q would look like '/emoncms/user/view' instead or just 'user/view'
        // for the example of viewing a users profile. We need to remove the first directory to get the "clean" routing path
        // within the application no matter at which path it's hosted.

        // First get the absolute physical path
        // Example running at root: '/var/www' or subdirectory: '/var/www/emoncms'
        $absolutePath = realpath(dirname(__FILE__));

        // Next up, we need to find the relative path to the www root and remove everything except the part we will use to route
        // for example this will perform the following:
        // Running at root: str_replace('/var/www', '', '/var/www') => ''
        // Running at subdirectory: str_replace('/var/www', '', '/var/www/emoncms') => '/emoncms'
        $relativeApplicationPath = str_replace($documentRoot, '', $absolutePath);

        // Next up we will need to remove the '/emoncms' from the route path '/emoncms/user/view'
        // str_replace('/emoncms', '', '/emoncms/user/view') => '/user/view'
        // running at root path it will just perform nothing: str_replace('', '', '/emoncms/user/view') so it can be skipped
        if (!empty($relativeApplicationPath)) {
            $q = str_replace($relativeApplicationPath, '', $q);
        }

        // trim slashes: '/user/view' => 'user/view'
        $q = trim($q, '/');

        // filter out all except alphanumerics and / . _ -
        $q = preg_replace('/[^.\/_A-Za-z0-9-]/', '', $q);

        // Split by /
        $args = preg_split('/[\/]/', $q);

        // get format (part of last argument after . i.e view.json)
        $lastArgIndex = sizeof($args) - 1;
        $lastArgSplit = preg_split('/[.]/', $args[$lastArgIndex]);
        if (count($lastArgSplit) > 1) {
            $this->format = $lastArgSplit[1];
        }
        $args[$lastArgIndex] = $lastArgSplit[0];

        if (count($args) > 0) {
            $this->controller = $args[0];
        }
        if (count($args) > 1) {
            $this->action = $args[1];
        }
        if (count($args) > 2) {
            $this->subaction = $args[2];
        }
        if (count($args) > 3) {
            $this->subaction2 = $args[3];
        }
        // allow for method to be added as post variable
        if(post('_method')=='DELETE') {
            $this->method = 'DELETE';
        } elseif(post('_method')=='PUT') {
            $this->method = 'PUT';
        } elseif(in_array($requestMethod, array('POST', 'DELETE', 'PUT'))) {
            $this->method = $requestMethod;
        } elseif($requestMethod === 'OPTIONS') {
            // "CORS PREFLIGHT REQUESTS" EXPECT THESE HEADERS. no content required
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: Authorization');
            header('Access-Control-Allow-Methods: GET');
            exit();
        }
    }

    /**
     * @return bool
     */
    public function isRouteNotDefined()
    {
        return empty($this->controller) && empty($this->action);
    }
    
    // --- LINK BUILDING CODE ---

    /**
     * build <li><a> style nav link with 'active' class added if is current page
     *
     * @param string $text text or html to use as the link content
     * @param string $path relative path to destination
     * @param string $title title to use as a tooltip
     * @param string $css css class names to add to the <li> tag
     * @param string $id unique id for a <li> tag
     * @return string <li><a> link
     */
    public function makeListLink($text='',$path='',$title='',$css='',$id='') {
        $link = $this->makeLink($text, $path, $title, $css, $id);
        $url = $this->getAbsoluteUrl($path);
        $current_path = $this->getCurrentPath();
        $css = $this->addCssClass($url === $current_path ? 'active': '', $css);
        return sprintf('<li class="%s">%s</li>', $css, $link);
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
    public function makeLink($text='',$path='',$title='',$css='',$id='') {
        $url = $this->getAbsoluteUrl($path);
        return sprintf('<a id="%s" href="%s" title="%s" class="%s">%s</a>', $id, $url, $title, $css, $text);
    }
    /**
     * return full url of given relative path of controller/action
     *
     * @param string $_path
     * @return string
     */
    private function getAbsoluteUrl($_path) {
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
    private function addCssClass($classname, $css = '') {
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
    private function getCurrentPath(){
        global $path;
        $spearator = '/';
        $parts[] = $path;
        $parts[] = $this->controller;
        $parts[] = $this->action;
        $parts = array_filter($parts);
        $parts = array_map(function($val) use ($spearator){
            return rtrim($val, $spearator);
        }, $parts);
        return implode($spearator, $parts);
    }
}
