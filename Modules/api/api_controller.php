<?php

/*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  ---------------------------------------------------------------------
  Combined API documentation for every installed module:

  - api       interactive explorer, with the API keys and authentication
              cards on top (Lib/api_explorer_view.php)
  - api.md    the same reference as plain markdown, also served as /llms-full.txt
  - api.json  the same reference as structured JSON

  The per module pages (input/api, feed/api, device/api, process/api) show
  their own endpoints only and link back here. emoncms.org serves these same
  pages from site/api, see api_docs_route() in Lib/api_docs_export.php.

  The reference itself is public: it documents the API rather than exposing
  any data. API keys are only rendered for a logged in session.

*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function api_controller()
{
    global $route;

    // No sub actions, api/anything is not a documentation page
    if ($route->action != "") return array('content'=>EMPTY_ROUTE);

    require_once "Lib/api_docs_export.php";

    if ($route->format == 'json') {
        return array('content'=>api_docs_json());
    }

    if ($route->format == 'md' || $route->format == 'txt' || $route->format == 'text') {
        return array('content'=>api_docs_markdown());
    }

    if ($route->format == 'html') {
        return view("Lib/api_explorer_view.php", array(
            "title"=>tr("Emoncms API"),
            "sub"=>tr("Post data from your own devices and scripts, read back the timeseries data recorded in feeds"),
            "api"=>api_docs_obj(), "standalone"=>true,
            "apikeys"=>session_apikeys()
        ));
    }

    return array('content'=>EMPTY_ROUTE);
}
