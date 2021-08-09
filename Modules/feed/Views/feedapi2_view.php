<?php global $path; ?>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<h3><?php echo _('Feed API'); ?></h3>

<div id="app">

  <select v-model="selected_api" @change="update">
    <option v-for="i,index in api" :value="index">{{ i.description }}</option>
  </select>

  <table class="table">
    <tr>
      <td><b>Description</b></td>
      <td>{{ api[selected_api].description }}</td>
    </tr>
    <tr>
      <td><b>Path</b></td>
      <td>{{ api[selected_api].path }}</td>
    </tr>
    <tr>
      <td><b>Parameters</b></td>
      <td>
        <div v-for="item, name in api[selected_api].parameters">
        <div class="input-prepend">
          <span class="add-on" style="width:100px">{{ name }}</span>
          
          <select v-if="item.type=='feed'" v-model.value="item.default" @change="update">
            <optgroup v-for="node,nodename in nodes" :label="nodename">
              <option v-for="f in node" :value="f.id">{{ f.name }}</option>
            <optgroup>
          </select>     
          
          <select v-else-if="item.type=='bool'" v-model.value="item.default" @change="update">
            <option value=0>No</option>
            <option value=1>Yes</option>
          </select>
          
          <input v-else type="text" v-model.value="item.default" @change="update">
        </div>
        </div>
      </td>
    </tr>
    <tr>
      <td><b>Authentication</b></td>
      <td></td>
    </tr>
    <tr>
      <td><b>Example URL</b></td>
      <td>
        <a :href="api[selected_api].url">{{ api[selected_api].url }}</a>
        <button class="btn btn-small" style="float:right">Try</button>
        <button class="btn btn-small" style="float:right">Copy</button>
      </td>
    </tr>
    <tr>
      <td><b>Response</b></td>
      <td><pre>{{ api[selected_api].response }}</pre></td>
    </tr>
  </table>
</div>

<script>

// ---------------------------------------------

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

// ---------------------------------------------


var host = "<?php echo $path; ?>";

var now = Math.round((new Date()).getTime()*0.001)*1000;

var api = [
  {
    description:"Feed list",
    path: "feed/list.json",
    parameters: {},
    url: "",
    response: ""
  },
  {
    description:"List public feeds for the given user",
    path: "feed/list.json",
    parameters: {
      userid: { default: 1 },
    },
    url: "",
    response: ""
  },
  {
    description:"Get feed field",
    path: "feed/get.json",
    parameters: {
      id: { default: selected_feed, type:"feed" },
      field: { default: 'name' },
    },
    url: "",
    response: ""
  },
  {
    description:"Get all feed fields",
    path: "feed/aget.json",
    parameters: {
      id: { default: selected_feed, type:"feed" }
    },
    url: "",
    response: ""
  },
  {
    description:"Fetch data from a feed",
    path: "feed/data.json",
    parameters: {
      id: { default: selected_feed, type:"feed" },
      start: { default: now-(3600*1000) },
      end: { default: now },
      interval: { default: 60 },
      average: { default: 0, type:"bool" },
      skipmissing: { 'name': 'Skip missing', default: 0, type:"bool" },
      limitinterval: { 'name': 'Limit interval', default: 0, type:"bool" }
    },
    url: "",
    response: ""
  }
];

var app = new Vue({
    el: '#app',
    data: {
        api:api,
        nodes: nodes,
        selected_api: 1
    },
    methods: {
       update: function() {
           build_url();
           get_response();
       }
    }
});

build_url();
get_response();

function build_url() {
    api[app.selected_api].url = host+api[app.selected_api].path;
    
    var parameter_array = []
    for (var z in api[app.selected_api].parameters) {
        parameter_array.push(z+"="+api[app.selected_api].parameters[z].default);
    }
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

