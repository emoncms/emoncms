<?php 

/*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  */

defined('EMONCMS_EXEC') or die('Restricted access');
global $user, $path, $session;
$apikey_read = $user->get_apikey_read($session['userid']);
$apikey_write = $user->get_apikey_write($session['userid']);
  
?>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<style>[v-cloak] { display: none; }</style>

<h3><?php echo $title; ?></h3>

<div id="app" v-cloak>

  <select v-model="selected_api" @change="update">
    <option v-for="i,index in api" :value="index">{{ i.description }}</option>
  </select>

  <table class="table">
    <tr>
      <td><b><?php echo _("Description"); ?></b></td>
      <td>{{ api[selected_api].description }}</td>
    </tr>
    <tr>
      <td><b><?php echo _("Path"); ?></b></td>
      <td>{{ api[selected_api].path }}</td>
    </tr>
    <tr>
      <td><b><?php echo _("Parameters"); ?></b></td>
      <td>
        <div v-for="item, name in api[selected_api].parameters">
        <div class="input-prepend">
          <span class="add-on" style="width:100px">{{ name }}</span>
          
          <select v-if="item.type=='feed'" v-model.value="selected_feed" @change="update">
            <optgroup v-for="node,nodename in nodes" :label="nodename">
              <option v-for="f in node" :value="f.id">{{ f.name }}</option>
            <optgroup>
          </select>     
          
          <select v-else-if="item.type=='bool'" v-model.value="item.default" @change="update">
            <option value=0><?php echo _("No"); ?></option>
            <option value=1><?php echo _("Yes"); ?></option>
          </select>
          
          <input v-else type="text" v-model.value="item.default" @change="update">
        </div>
        </div>
      </td>
    </tr>
    <tr>
      <td><b><?php echo _("Authentication"); ?></b></td>
      <td>
        <button v-if="!auth_visible" class="btn btn-small" @click="show_auth">Show</button>
        <button v-else class="btn btn-small" @click="hide_auth">Hide</button>
      </td>
    </tr>
    <tr>
      <td><b><?php echo _("Example URL"); ?></b></td>
      <td>
        <a :href="api[selected_api].url">{{ api[selected_api].url }}</a>
        <button class="btn btn-small" style="float:right" @click="try_api">Try</button>
        <!--<button class="btn btn-small" style="float:right" @click="copy_api">Copy</button>-->
      </td>
    </tr>
    <tr>
      <td><b><?php echo _("Response"); ?></b></td>
      <td>
        <pre v-if="api[selected_api].response!=''">{{ api[selected_api].response }}</pre>
        <div v-else-if="api[selected_api].mode=='write'"><?php echo _("This API end point will write data, click Try to test"); ?></div>
      </td>
    </tr>
  </table>
</div>

<script>

var apikey_read = "<?php echo $apikey_read; ?>";
var apikey_write = "<?php echo $apikey_write; ?>";

// ---------------------------------------------------------------------
// Fetch feeds to create dropdown feed selector
// ---------------------------------------------------------------------
var feeds = [];
var nodes = {};
var selected_feed = 0;

$.ajax({ url: path+"feed/list.json", dataType: 'json', async: false, success: function(result) {
    feeds = result;
    if (feeds.length) {
        selected_feed = feeds[0].id;
    }
    
    nodes = {};
    for (var z in feeds) {
        var node = feeds[z].tag;
        if (nodes[node]==undefined) nodes[node] = [];
        nodes[node].push(feeds[z]);
    }
}});

// ---------------------------------------------------------------------
// Pre-prepare api object
// ---------------------------------------------------------------------
var api = <?php echo json_encode($api); ?>;
var now = Math.round((new Date()).getTime()*0.001)*1000;

for (var i in api) {
    if (api[i].response == undefined) api[i].url = "";
    if (api[i].response == undefined) api[i].response = "";
    
    for (var p in api[i].parameters) {
        if (p=="start") api[i].parameters[p].default = now - 3600*1000;
        if (p=="end") api[i].parameters[p].default = now;
    }
}

// ---------------------------------------------------------------------
// Vue.js definition
// ---------------------------------------------------------------------
var app = new Vue({
    el: '#app',
    data: {
        api:api,
        nodes: nodes,
        selected_api: <?php echo $selected_api; ?>,
        selected_feed: selected_feed,
        auth_visible: false
    },
    methods: {
       update: function() {
           build_url();
           if (api[app.selected_api].mode == "read") {
               get_response();
           }
       },
       show_auth: function() {
           app.auth_visible = true;
           build_url();
       },
       hide_auth: function() {
           app.auth_visible = false;
           build_url();
       },
       try_api: function() {
           get_response();
       },
       copy_api: function() {
       
       }
    }
});

// ---------------------------------------------------------------------
// Build URL and get response
// ---------------------------------------------------------------------
build_url();
if (api[app.selected_api].mode == "read") {
    get_response();
}

function build_url() {
    // Host path and api path
    api[app.selected_api].url = path+api[app.selected_api].path;
    
    // Compile parameters
    var parameter_array = []
    for (var p in api[app.selected_api].parameters) {
        var param = api[app.selected_api].parameters[p];
        var value = "";
        // apply default value if available
        if (param.default != undefined) value = param.default;
        // apply selected feed if type is feed
        if (param.type != undefined && param.type == "feed") {
            value = app.selected_feed
        }
        parameter_array.push(p+"="+value);
    }
    
    if (app.auth_visible) {
        if (api[app.selected_api].mode=="read") {
            parameter_array.push("apikey="+apikey_read);
        } else {
            parameter_array.push("apikey="+apikey_write); 
        }
    }
    
    // Add parameters to URL
    if (parameter_array.length) {
        api[app.selected_api].url += "?"+parameter_array.join("&");
    }
}

function get_response() {
    $.ajax({ url: api[app.selected_api].url, dataType: 'json', async: true, success: function(result) {
        api[app.selected_api].response = result;
    }});
}

</script>

