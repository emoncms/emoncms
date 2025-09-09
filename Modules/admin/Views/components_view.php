<?php 
defined('EMONCMS_EXEC') or die('Restricted access');
global $path;
?>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<h3><?php echo tr('Components'); ?></h3>

<p><?php echo tr('Selectively update system components or switch between branches'); ?></p>

<pre id="update-log-bound" class="log" style="display:none; margin-bottom:10px"><div id="update-log"></div></pre>

<div id="app">
    <div class="input-prepend input-append">
        <span class="add-on"><?php echo tr('Update or switch all components to'); ?></span>
        <button v-if="!all_custom"class="btn btn-success" @click="all('stable')">Stable</button>
        <button v-if="!all_custom" class="btn btn-warning" @click="all('master')">Master</button>
        <button class="btn btn-danger" @click="all_custom = !all_custom">Custom</button>
        <input v-if="all_custom" v-model="custom_branch" type="text" value="menu_v3" style="width:100px">
        <button v-if="all_custom" class="btn" @click="all('custom')">Switch</button>
    </div>

    <table class="table table-bordered">
    <tr>
      <th><?php echo tr('Component name'); ?></th>
      <th><?php echo tr('Version'); ?></th>
      <th><?php echo tr('Describe'); ?></th>
      <th><?php echo tr('Local changes'); ?></th>
      <th><?php echo tr('Branch'); ?></th>
      <th></th>
    </tr>
    <tr v-for="item, key in components">
      <td>{{ item.name }}<br>
        <span style="font-size:12px"><b><?php echo tr('URL:'); ?></b> <a :href="item.url">{{ item.url }}</a></span><br>
        <span style="font-size:12px"><b><?php echo tr('Installed path:'); ?></b> {{ item.path }}</span>
      </td>
      <td>{{ item.version }}</td>
      <td>{{ item.describe }}</td>
      <td>
        <span v-if="item.local_changes!=''" :title="item.local_changes" class="label label-important"><?php echo tr('Yes'); ?></span>
        <span class="label label-success" v-else><?php echo tr('No'); ?></span>
      </td>
      <td v-if="item.local_changes==''">
        <select v-model="item.branch" @change="switch_branch(key)">
          <option v-for="branch in item.branches_available">{{ branch }}</option>
        </select>
      </td>
      <td v-else>{{ item.branch }}</td>
      <td><button class="btn" v-if="item.local_changes==''" @click="update(key)"><?php echo tr('Update'); ?></button></td>
    </tr>
    </table>
</div>


<script>
var components = <?php echo json_encode($components); ?>;

var log_end = "";

var app = new Vue({
    el: '#app',
    data: {
        all_custom: false,
        custom_branch: "",
        components: components
    },
    methods: {
        switch_branch: function(name) {
            console.log("switch_branch: "+name+" "+components[name].branch)
            component_update(name,components[name].branch)  
        },
        update: function(name) {
            console.log("update: "+name+" "+components[name].branch)
            component_update(name,components[name].branch)
        },
        all: function(branch) {
            if (branch=='custom') branch = this.custom_branch
            console.log("update all: "+branch)
            update_all_components(branch)
        }
    }
});

function component_update(name,branch) {
    $.ajax({                                      
        url: path+'admin/component-update',                         
        async: true, 
        data: "module="+name+"&branch="+branch,
        dataType: 'json',
        success: function(result) {
            if (result.reauth == true) { window.location = "/"; }
            if (result.success == false)  {
                clearInterval(updates_log_interval);
                refresh_updateLog("<text style='color:red;'>" + result.message + "</text>\n");
            } else {
                log_end = "- component updated"
                refresh_updateLog(result.message);
                refresherStart(getUpdateLog, 1000)
            }
        } 
    });   
}

function update_all_components(branch) {
    $.ajax({                                      
        url: path+'admin/components-update-all',
        async: true,        
        data: "branch="+branch,
        dataType: 'json',
        success: function(result) { 
            if (result.reauth == true) { window.location = "/"; }
            if (result.success == false)  {
                clearInterval(updates_log_interval);
                refresh_updateLog("<text style='color:red;'>" + result.message + "</text>\n");
            } else {
                log_end = "- all components updated"
                refresh_updateLog(result.message);
                refresherStart(getUpdateLog, 1000)
            }
        } 
    });   
}

// -------------------------------------
// Log window
// -------------------------------------

var updates_log_interval = false;

// stop updates if interval == 0
function refresherStart(func, interval){
    clearInterval(updates_log_interval);
    updates_log_interval = setInterval(func, interval);
}

// display content in container and scroll to the bottom
function output_logfile(result, $container){
    $container.html(result);
    scrollable = $container.parent('pre')[0];
    if(scrollable) scrollable.scrollTop = scrollable.scrollHeight;
}

// push value to updates logfile viewer
function refresh_updateLog(result){
    output_logfile(result, $("#update-log"));
    $("#update-log-bound").slideDown();
}

function getUpdateLog() {
  $.ajax({ url: path+"admin/update-log", async: true, dataType: "text", success: function(result)
    {
        var isjson = true;
        try {
            data = JSON.parse(result);
            if (data.reauth == true) { window.location = "/"; }
            if (data.success == false)  { 
                clearInterval(updates_log_interval); 
                refresh_updateLog("<text style='color:red;'>"+ data.message+"</text>");
            }
        } catch (e) {
            isjson = false;
        }
        if (isjson == false )     {
            if (result != "") {
                refresh_updateLog(result); 
                
                if (result.indexOf(log_end)!=-1) {
                    clearInterval(updates_log_interval);   
                    setTimeout(function() {
                        $("#update-log-bound").slideUp();            
                    },3000);
                }
            }
        }
    }
  });
}

</script>
