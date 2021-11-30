<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css">
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<div class="admin-container">
<h3><?php echo _('Components'); ?></h3>

<p><?php echo _('Install, remove, update or switch components between branches.'); ?></p>
<p><?php echo _('Note that some older components may not be yet fully compatible to this new way of managing modules and require additional rework to work properly.'); ?></p>

<pre id="update-log-bound" class="log" style="min-height:320px; height:calc(30vh); display:none;"><div id="update-log"></div></pre>
<br>

<div id="table_installed">
<?php if ($redis_enabled) { ?>
<!-- No PHP support for now
    <div class="input-prepend input-append">
        <span class="add-on"><?php echo _('Update or switch all components to'); ?></span>
        <button v-if="!all_custom"class="btn btn-success" @click="all('stable')">Stable</button>
        <button v-if="!all_custom" class="btn btn-warning" @click="all('master')">Master</button>
        <button class="btn btn-danger" @click="all_custom = !all_custom">Custom</button>
        <input v-if="all_custom" v-model="custom_branch" type="text" value="menu_v3" style="width:100px">
        <button v-if="all_custom" class="btn" @click="all('custom')">Switch</button>
    </div>
-->	
<?php } ?>
    <table class="table table-bordered">
    <tr>
      <th><?php echo _('Installed'); ?></th>
      <th><?php echo _('Version'); ?></th>
      <th><?php echo _('Describe'); ?></th>
      <th><?php echo _('Local changes'); ?></th>
      <th><?php echo _('Update available'); ?></th>
      <th><?php echo _('Branch'); ?></th>
      <th></th>
    </tr>
    <tr v-for="item, key in components_installed">
      <td>{{ item.name }}<br>
        <span style="font-size:12px"><b><?php echo _('URL:'); ?></b> <a :href="item.url">{{ item.url }}</a></span><br>
        <span style="font-size:12px"><b><?php echo _('Installed path:'); ?></b> {{ item.path }}</span>
      </td>
      <td>{{ item.version }}</td>
      <td>{{ item.describe }}</td>
      <td>
        <span v-if="item.local_changes" :title="item.local_changes" class="label label-important"><?php echo _('Yes'); ?></span>
        <span class="label label-default" v-else><?php echo _('No'); ?></span>
      </td>
      <td>
        <span v-if="item.update_available" :title="item.update_available" class="label label-success"><?php echo _('Yes'); ?></span>
        <span class="label label-default" v-else><?php echo _('No'); ?></span>
      </td>
      <td v-if="!item.local_changes">
        <select v-model="item.branch" @change="switch_branch(key)">
          <option v-for="branch in item.branches_available">{{ branch }}</option>
        </select>
      </td>
      <td v-else>{{ item.branch }}</td>
      <td>
        <button class="btn btn-danger" v-if="item.local_changes" @click="update(key, 'true')"><?php echo _('Reset'); ?></button>
        <button class="btn" v-if="!item.local_changes && item.update_available" @click="update(key, 'false')"><?php echo _('Update'); ?></button>
        <button class="btn" v-if="!item.local_changes && item.name!='Emoncms Core'" @click="uninstall(key, 'false')"><?php echo _('Uninstall'); ?></button>
      </td>
    </tr>
    </table>
</div>

<br>
<div id="table_available">
    <table class="table table-bordered">
    <tr>
      <th><?php echo _('Available'); ?></th>
      <th><?php echo _('Description'); ?></th>
      <th><?php echo _('Branch'); ?></th>
      <th></th>
    </tr>
    <tr v-for="item, key in components_available">
      <td>{{ item.name }}<br>
        <span style="font-size:12px"><b><?php echo _('URL:'); ?></b> <a :href="item.url">{{ item.url }}</a></span><br>
      </td>
      <td>{{ item.description }}</td>
      <td>
        <select v-model="item.branch">
          <option disabled value="">Please select</option>
          <option v-for="branch in item.branches_available">{{ branch }}</option>
        </select>
      </td>
      <td>
        <button class="btn" @click="install(key)"><?php echo _('Install'); ?></button>
      </td>
    </tr>
    </table>
</div>

<a href="<?php echo $path; ?>admin/components" class="btn btn-info"><?php echo _('Refresh components'); ?></a>
</div>
<script>
var components_installed = <?php echo json_encode($components_installed); ?>;
var components_available = <?php echo json_encode($components_available); ?>;

var table_installed = new Vue({
    el: '#table_installed',
    data: {
        all_custom: false,
        custom_branch: "",
        components_installed: components_installed
    },
    methods: {
        switch_branch: function(name) {
            console.log("switch_branch: "+name+" "+components_installed[name].branch)
            component_update(name,components_installed[name].branch)  
        },
        update: function(name, reset) {
            console.log("update: "+name+" "+components_installed[name].branch + " " + reset)
            component_update(name,components_installed[name].branch, reset)
        },
        uninstall: function(name, reset) {
            console.log("uninstall: "+name+" " + reset)
            component_uninstall(name, reset)
        },
        all: function(branch) {
            if (branch=='custom') branch = this.custom_branch
            console.log("update all: "+branch)
            update_all_components(branch)
        }
    }
});

if (components_available == null || components_available.success == false ) { $("#table_available").hide(); }

var table_available = new Vue({
    el: '#table_available',
    data: {
        components_available: (components_available == null || components_available.success == false ? null : components_available)
    },
    methods: {
        install: function(name) {
            console.log("install: "+name+" "+components_available[name].branch)
            component_install(name,components_available[name].branch)
        }
    }
});

function component_update(name,branch,reset) {
    refresh_updateLog("");
    $.ajax({                                      
        url: path+'admin/component-update',                         
        async: true, 
        data: "module="+name+"&branch="+branch+"&reset="+reset,
        dataType: 'json',
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  {
                clearInterval(updates_log_interval);
                refresh_updateLog("\n<text style='color:red;'>" + result.message + "</text>\n", true);
                alert(result.message);
            } else {
                hide_tables();
                log_end = "- component updated"
                refresh_updateLog(result.message);
                refresherStart(getUpdateLog, 1000)
            }
        } 
    });   
}

function update_all_components(branch) {
    refresh_updateLog("");
    $.ajax({                                      
        url: path+'admin/components-update-all',
        async: true,        
        data: "branch="+branch,
        dataType: 'json',
        success: function(result) { 
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  {
                clearInterval(updates_log_interval);
                refresh_updateLog("\n<text style='color:red;'>" + result.message + "</text>\n", true);
                alert(result.message);
            } else {
                hide_tables();
                log_end = "- all components updated"
                refresh_updateLog(result.message);
                refresherStart(getUpdateLog, 1000)
            }
        } 
    });   
}


function component_install(name,branch) {
    refresh_updateLog("");
    $.ajax({                                      
        url: path+'admin/component-install',                         
        async: true, 
        data: "module="+name+"&branch="+(branch == undefined ? "" : branch),
        dataType: 'json',
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  {
                clearInterval(updates_log_interval);
                refresh_updateLog("\n<text style='color:red;'>" + result.message + "</text>\n", true);
                alert(result.message);
            } else {
                hide_tables();
                log_end = "- component installed"
                refresh_updateLog(result.message);
                refresherStart(getUpdateLog, 1000)
            }
        } 
    });   
}


function component_uninstall(name,reset) {
    refresh_updateLog("");
    $.ajax({                                      
        url: path+'admin/component-uninstall',                         
        async: true, 
        data: "module="+name+"&reset="+reset,
        dataType: 'json',
        success: function(result) {
            if (result.reauth == true) { window.location.reload(true); }
            if (result.success == false)  {
                clearInterval(updates_log_interval);
                refresh_updateLog("\n<text style='color:red;'>" + result.message + "</text>\n", true);
                alert(result.message);
            } else {
                hide_tables();
                log_end = "- component uninstalled"
                refresh_updateLog(result.message);
                refresherStart(getUpdateLog, 1000)
            }
        } 
    });   
}

function hide_tables() {
    $("#table_installed").slideUp();
    $("#table_available").slideUp();
}

// -------------------------------------
// Log window
// -------------------------------------

var updates_log_interval = false;
var log_end = "";

// stop updates if interval == 0
function refresherStart(func, interval){
    clearInterval(updates_log_interval);
    updates_log_interval = setInterval(func, interval);
}

// display content in container and scroll to the bottom
function output_logfile(result, $container, append){
    if (append) { 
        $container.append(result);
    } else {
        $container.html(result);
    }
    scrollable = $container.parent('pre')[0];
    if(scrollable) scrollable.scrollTop = scrollable.scrollHeight;
}

// push value to updates logfile viewer
function refresh_updateLog(result, append = false){
    output_logfile(result, $("#update-log"), append);
    $("#update-log-bound").slideDown();
}

function getUpdateLog() {
  $.ajax({ url: path+"admin/update-log", async: true, dataType: "text", success: function(result)
    {
        var isjson = true;
        try {
            data = JSON.parse(result);
            if (data.reauth == true) { window.location.reload(true); }
            if (data.success == false)  { 
                clearInterval(updates_log_interval); 
                console.log("getUpdateLog: "+data.message);
                //refresh_updateLog("\n<text style='color:red;'>"+ data.message+"</text>\n", true);
            }
        } catch (e) {
            isjson = false;
        }
        if (isjson == false )     {
            if (result != "") {
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
