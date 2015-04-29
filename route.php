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
    public $controller = '';
    public $action = '';
    public $subaction = '';
    public $format = "html";

    public function __construct($q)
    {
        $this->decode($q);
    }

    public function decode($q)
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
        $relativeApplicationPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $absolutePath);

        // Next up we will need to remove the '/emoncms' from the route path '/emoncms/user/view'
        // str_replace('/emoncms', '', '/emoncms/user/view') => '/user/view'
        // running at root path it will just perform nothing: str_replace('', '', '/emoncms/user/view') so it can be skipped
        if ( !empty($relativeApplicationPath) ) {
            $q = str_replace($relativeApplicationPath, '', $q);
        }

        // trim slashes: '/user/view' => 'user/view'
        $q = trim($q, '/');

        // filter out all except a-z and / .
        $q = preg_replace('/[^.\/A-Za-z0-9-]/', '', $q);

        // Split by /
        $args = preg_split('/[\/]/', $q);

        // get format (part of last argument after . i.e view.json)
        $lastarg = sizeof($args) - 1;
        $lastarg_split = preg_split('/[.]/', $args[$lastarg]);
        if (count($lastarg_split) > 1) { $this->format = $lastarg_split[1]; }
        $args[$lastarg] = $lastarg_split[0];

        if (count($args) > 0) { $this->controller = $args[0]; }
        if (count($args) > 1) { $this->action = $args[1]; }
        if (count($args) > 2) { $this->subaction = $args[2]; }
        
        
        $this->method = "GET";
        if ($_SERVER["REQUEST_METHOD"]=="POST") $this->method = "POST";
        if ($_SERVER["REQUEST_METHOD"]=="DELETE") $this->method = "DELETE";
        if ($_SERVER["REQUEST_METHOD"]=="PUT") $this->method = "PUT";
    }
}
