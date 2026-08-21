<?php
defined('EMONCMS_EXEC') or die('Restricted access');

/*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  ---------------------------------------------------------------------
  Machine readable exports of the API reference, for AI assistants and
  other tools that cannot run the javascript based explorer:

  - api_docs_obj():      merged endpoint definitions from every module
  - api_docs_json():     structured array served as <api docs route>.json
  - api_docs_markdown(): plain markdown reference served as <api docs route>.md
                         and /llms-full.txt
  - api_docs_llms_txt(): short index served as /llms.txt

  Everything is generated from the same module api_obj definitions the
  interactive explorer (Lib/api_explorer_view.php) renders, so these
  exports cannot drift from the documentation pages.

*/

// Route of the combined API documentation pages, relative to the emoncms base.
// Installs that have the emoncms.org site module serve them from site/api,
// everywhere else the api module serves the same pages from api.
// Used by the explorer view and the exports below so that no page links to a
// route the install does not have.
function api_docs_route() {
    static $route = null;
    if ($route === null) {
        $route = is_file("Modules/site/site_controller.php") ? "site/api" : "api";
    }
    return $route;
}

// Merged endpoint definitions from every module that provides them.
// The process module re-documents the input/feed process list endpoints on its
// own page; those duplicates (same path and parameter names) are dropped here.
function api_docs_obj() {
    global $session;
    // feed_api_obj reads $session['public_userid'] to prefix public profile paths
    if (!isset($session['public_userid'])) $session['public_userid'] = 0;

    // Modules are optional installs, only document those that are present
    $modules = array();
    foreach (array("input", "feed", "device", "process", "schedule") as $module) {
        $definitions = "Modules/$module/".$module."_api_obj.php";
        if (!is_file($definitions)) continue;
        require_once $definitions;
        $modules[$module] = call_user_func($module."_api_obj");
    }

    $api = array();
    $seen = array();
    foreach ($modules as $module => $endpoints) {
        foreach ($endpoints as $endpoint) {
            $params = array_keys($endpoint['parameters']);
            sort($params);
            $signature = $endpoint['path']."|".implode(",", $params);
            if (isset($seen[$signature])) continue;
            $seen[$signature] = true;
            $endpoint['module'] = $module;
            $api[] = $endpoint;
        }
    }
    return $api;
}

// Example value for a parameter, matching what the explorer prefills
function api_docs_param_example($name, $param) {
    if (isset($param['type']) && $param['type'] == 'feed') return "1";
    if (isset($param['default'])) return (string) $param['default'];
    if ($name == 'start') return (string) (time() - 3600);
    if ($name == 'end' || $name == 'time') return (string) time();
    return "";
}

// Parameter description with the type information the explorer conveys
// through its input widgets spelled out in words
function api_docs_param_description($param) {
    $description = isset($param['description']) ? $param['description'] : "";
    $type = isset($param['type']) ? $param['type'] : "";
    if ($type == 'feed') $description = trim("Feed id, see feed/list.json. ".$description);
    if ($type == 'bool') $description = trim($description.($description != "" ? ". " : "")."0 or 1");
    if ($type == 'select' && isset($param['options'])) {
        $description = trim($description.($description != "" ? ". " : "")."One of: ".implode(", ", $param['options']));
    }
    return $description;
}

function api_docs_markdown_escape($text) {
    return str_replace("|", "\\|", $text);
}

// Full plain markdown API reference
function api_docs_markdown() {
    global $path;
    $api = api_docs_obj();

    $md = "# Emoncms API\n\n";
    $md .= "> Emoncms is an open source web application for processing, logging and visualising energy, temperature and other environmental data. ";
    $md .= "This is the complete HTTP API reference for ".$path.", generated from the same definitions as the interactive explorer at ".$path.api_docs_route().". ";
    $md .= "A structured JSON version is available at ".$path.api_docs_route().".json.\n\n";
    $md .= "Base URL: `".$path."`\n\n";
    $md .= "All endpoints are HTTP GET unless marked POST. Responses are JSON unless an endpoint's notes say otherwise. ";
    $md .= "Failures usually respond `{\"success\": false, \"message\": \"...\"}`. ";
    $md .= "Example URLs below are shown unencoded for readability: URL-encode parameter values (JSON in particular) in real requests, or let your HTTP library encode them for you.\n\n";

    $md .= "## Authentication\n\n";
    $md .= "Every account has two API keys, shown on ".$path.api_docs_route()." when logged in:\n\n";
    $md .= "- **Read only key**: for reading data and configuration, safe to use in dashboards and shared links.\n";
    $md .= "- **Read & write key**: lets devices and scripts post data and change configuration.\n\n";
    $md .= "Each endpoint below states which key it requires. Pass the key in one of three ways:\n\n";
    $md .= "1. POST body parameter: `apikey=APIKEY` (recommended for POST requests)\n";
    $md .= "2. HTTP header: `Authorization: Bearer APIKEY`\n";
    $md .= "3. URL query parameter: `?apikey=APIKEY`\n\n";
    $md .= "Endpoints marked *public ok* can also be used without a key for feeds a user has marked public, ";
    $md .= "either with the owner's userid or via public profile routing: `".$path."USERNAME/feed/list.json`.\n\n";
    $md .= "For devices that cannot use HTTPS, emoncms also supports AES-128-CBC encrypted posting using the write key as a pre-shared key: see the encrypted post endpoint in the input section.\n\n";

    $md .= "## Quick start (Python)\n\n";
    $md .= "```python\n";
    $md .= "import requests\n\n";
    $md .= "base = \"".$path."\"\n";
    $md .= "apikey = \"YOUR_WRITE_APIKEY\"\n\n";
    $md .= "# Post a reading to an input called power1 on a device called emontx\n";
    $md .= "r = requests.get(base + \"input/post\", params={\n";
    $md .= "    \"node\": \"emontx\",\n";
    $md .= "    \"fulljson\": '{\"power1\":100}',\n";
    $md .= "    \"apikey\": apikey\n";
    $md .= "})\n";
    $md .= "print(r.json())  # {\"success\": true}\n\n";
    $md .= "# List feeds, then read the last week of daily data from the first one\n";
    $md .= "feeds = requests.get(base + \"feed/list.json\", params={\"apikey\": apikey}).json()\n";
    $md .= "data = requests.get(base + \"feed/data.json\", params={\n";
    $md .= "    \"id\": feeds[0][\"id\"],\n";
    $md .= "    \"start\": \"-1 week\", \"end\": \"now\", \"interval\": \"daily\",\n";
    $md .= "    \"apikey\": apikey\n";
    $md .= "}).json()  # [[unixtime_ms, value], ...]\n";
    $md .= "```\n\n";

    // Endpoints, grouped by module then group, preserving definition order
    $module_titles = array(
        "input" => "Input API",
        "feed" => "Feed API",
        "device" => "Device API",
        "process" => "Process API",
        "schedule" => "Schedule API"
    );
    $module_intros = array(
        "input" => "Inputs receive posted data. Each input holds the latest value and a process list describing what to do with new values, typically logging to a feed.",
        "feed" => "Feeds are stored time series. Fixed interval PHPFina feeds (engine 5) and variable interval PHPTimeSeries feeds (engine 2) are created by input process lists or directly via feed/create.json.",
        "device" => "Devices group the inputs posted to one node name and can initialise inputs, feeds and process lists from a template.",
        "process" => "Process lists are configured through the input and feed modules, these endpoints list the available process functions.",
        "schedule" => "Schedules describe recurring time periods, e.g. an off peak tariff window. Input and feed processes use them to switch behaviour by time of use."
    );

    $current_module = "";
    $current_group = "";
    foreach ($api as $e) {
        if ($e['module'] != $current_module) {
            $current_module = $e['module'];
            $current_group = "";
            $md .= "## ".$module_titles[$current_module]."\n\n";
            $md .= $module_intros[$current_module]."\n\n";
        }
        if ($e['group'] != $current_group) {
            $current_group = $e['group'];
            $md .= "### ".$current_group."\n\n";
        }

        $md .= "#### ".$e['description']."\n\n";

        $http = (isset($e['http']) && $e['http'] == 'post') ? "POST" : "GET";
        $keyline = $e['mode'] == 'write' ? "requires the write API key" : "requires the read API key";
        if (!empty($e['public'])) $keyline .= ", public ok: no key needed for public feeds";
        $md .= "`".$http." ".$e['path']."`, ".$keyline."\n\n";

        // Example request built from the same defaults the explorer prefills
        $query = array();
        $body = array();
        foreach ($e['parameters'] as $name => $param) {
            $value = api_docs_param_example($name, $param);
            if (!empty($param['post'])) $body[] = $name."=".$value;
            else $query[] = $name."=".$value;
        }
        $apikey_placeholder = $e['mode'] == 'write' ? "APIKEY_WRITE" : "APIKEY_READ";
        if ($http == "POST") {
            $md .= "```\nPOST ".$path.$e['path'].(count($query) ? "?".implode("&", $query) : "")."\n";
            $md .= "body: ".implode("&", array_merge($body, array("apikey=".$apikey_placeholder)))."\n```\n\n";
        } else if (count($e['parameters']) || empty($e['notry'])) {
            $md .= "```\nGET ".$path.$e['path']."?".implode("&", array_merge($query, array("apikey=".$apikey_placeholder)))."\n```\n\n";
        }

        if (count($e['parameters'])) {
            $md .= "| Parameter | Example | Description |\n|---|---|---|\n";
            foreach ($e['parameters'] as $name => $param) {
                $example = api_docs_param_example($name, $param);
                $description = api_docs_param_description($param);
                $md .= "| `".$name."` | `".api_docs_markdown_escape($example)."` | ".api_docs_markdown_escape($description)." |\n";
            }
            $md .= "\n";
        }

        if (isset($e['notes'])) {
            $md .= $e['notes']."\n\n";
        }
    }

    $md .= "## More resources\n\n";
    $md .= "- Interactive API explorer: ".$path.api_docs_route()."\n";
    $md .= "- Emoncms guide: https://docs.openenergymonitor.org/emoncms/index.html\n";
    $md .= "- OpenEnergyMonitor documentation: https://docs.openenergymonitor.org\n";
    $md .= "- Community forum: https://community.openenergymonitor.org\n";
    $md .= "- Emoncms source code: https://github.com/emoncms/emoncms\n";

    return $md;
}

// Structured array for the .json rendition of the API reference
function api_docs_json() {
    global $path;

    // An endpoint with no parameters would otherwise encode as "parameters": [],
    // clients reading the reference expect an object throughout
    $endpoints = api_docs_obj();
    foreach ($endpoints as &$endpoint) {
        $endpoint['parameters'] = (object) $endpoint['parameters'];
    }
    unset($endpoint);

    return array(
        "name" => "Emoncms API",
        "base_url" => $path,
        "description" => "HTTP API for emoncms, the open source energy monitoring platform. Post data from devices and scripts, read back recorded time series data and manage configuration.",
        "docs" => array(
            "interactive" => $path.api_docs_route(),
            "markdown" => $path.api_docs_route().".md",
            "llms_txt" => $path."llms.txt"
        ),
        "authentication" => array(
            "description" => "Every account has a read only API key and a read & write API key, shown on ".$path.api_docs_route()." when logged in. Each endpoint's mode states which key it requires.",
            "methods" => array(
                "POST body parameter: apikey=APIKEY (recommended for POST requests)",
                "HTTP header: Authorization: Bearer APIKEY",
                "URL query parameter: ?apikey=APIKEY"
            ),
            "public" => "Endpoints marked public:true can be used without a key for feeds marked public, with the owner's userid or via public profile routing: ".$path."USERNAME/feed/list.json"
        ),
        "conventions" => array(
            "http" => "Endpoints are HTTP GET unless marked http:post, where parameters marked post:true must be sent in the POST body.",
            "responses" => "JSON unless an endpoint's notes say otherwise. Failures usually respond {\"success\": false, \"message\": \"...\"}.",
            "parameter_types" => array(
                "feed" => "numeric feed id, see feed/list.json",
                "bool" => "0 or 1",
                "select" => "one of the values listed in options",
                "default" => "text or number, see the parameter description"
            )
        ),
        "endpoints" => $endpoints
    );
}

// Short /llms.txt index, pointing AI assistants at the full reference
function api_docs_llms_txt() {
    global $path;
    $txt = "# Emoncms\n\n";
    $txt .= "> Emoncms is an open source web application for processing, logging and visualising energy, temperature and other environmental data. ";
    $txt .= "Data is posted to and read from ".$path." over a simple HTTP API, authenticated with per account API keys.\n\n";
    $txt .= "## API reference\n\n";
    $txt .= "- [Full API reference, markdown](".$path.api_docs_route().".md): every endpoint with parameters, example requests and notes. Use this to write API client code.\n";
    $txt .= "- [Full API reference, JSON](".$path.api_docs_route().".json): the same reference as structured JSON.\n";
    $txt .= "- [Interactive API explorer](".$path.api_docs_route()."): getting started guide and browsable reference, requires javascript.\n\n";
    $txt .= "## Documentation\n\n";
    $txt .= "- [Emoncms guide](https://docs.openenergymonitor.org/emoncms/index.html)\n";
    $txt .= "- [OpenEnergyMonitor documentation](https://docs.openenergymonitor.org)\n";
    $txt .= "- [Community forum](https://community.openenergymonitor.org)\n";
    $txt .= "- [Emoncms source code](https://github.com/emoncms/emoncms)\n";
    return $txt;
}
