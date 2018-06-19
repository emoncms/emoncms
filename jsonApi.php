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
     * all the data returned from the controller
     * @var array
     */
    public $request = array();

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
    public function __construct($data=array(),$param=array(),$links=array())
    {
        exec("git describe --tags --abbrev=0", $git_tags);
        $this->version = implode('', $git_tags);
        $this->data = $data['content'];
        $this->request = array(
            'type' => 'text/plain',
            'params' => $param->params
        );
        $this->route = $data['route']->getDetails();
        $this->links = $links;
    }
    public function json()
    {
        $output = array(
            "meta" => array(
                "version"=>$this->version,
                "status"=>200,
                "request"=>$this->request,
                "route"=>$this->route
            ),
            "data" => $this->data,
            "links" => $this->links
        );
        return json_encode($output);
    }
}
/*

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