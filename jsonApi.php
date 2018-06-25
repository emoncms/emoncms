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

class JsonApi
{
    /**
     * version reported by the git tag
     * @var string
     */
    public $version = "";
    /**
     * version reported by the git tag
     * @var string
     */
    public $errors = array();
    /**
     * all the data returned from the controller
     * @var array
     */
    public $request = array();
    /**
     * request status (not the http status but the controller->action() status)
     * @var array
     */
    public $success = "";
    /**
     * message to show to the user
     * @var string
     */
    public $message = "";
    /**
     * the data to be returned
     * @var array
     */
    public $data = array();

    /**
     * details regarding what actions took place on the request
     * @var array
     */
    public $route = array();

    /**
     * @param array $data all data created by the controller
     * @param array $param all parameters passed
     * @param array $links links to provide the api response (keys: [self,first,prev,next,last] or [related])
     */
    public function __construct($data=array(),$param=array(),$links=array(),$errors=array())
    {
        global $emoncms_version_number, $appname;
        $this->version = !empty($emoncms_version_number) ? $emoncms_version_number : "unknown";
        $this->appname = !empty($appname) ? $appname: 'EmonCMS';
        $this->data = $data['content'];
        $this->request = array(
            'type' => 'text/plain',
            'params' => property_exists((object) $param,'params') ? $param->params : ""
        );
        $this->route = $data['route']->getDetails();
        $this->links = $links;
        $this->success = !isset($this->data['success']) || (isset($this->data['success']) && $this->data['success']===TRUE);
        $this->message = isset($this->data['message']) ? $this->data['message'] : "";
        $this->errors = $errors;
    }
    public function json()
    {
        $output = array(
            "meta" => array(
                "appname"=>$this->appname,
                "version"=>$this->version,
                "success"=>$this->success,
                "message"=>$this->message,
                "request"=>$this->request,
                "route"=>$this->route
            ),
            "data" => $this->data,
            "links" => $this->links,
            "errors" => $this->errors
        );
        return json_encode($output);
    }
}
/*
Example of what an error array should contain.
http://jsonapi.org/examples/
{
  "errors": [
    {
      "status": "422",
      "source": { "pointer": "/data/attributes/first-name" },
      "title":  "Invalid Attribute",
      "detail": "First name must contain at least three characters."
    }
  ]
}

*/