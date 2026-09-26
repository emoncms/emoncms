<?php 
defined('EMONCMS_EXEC') or die('Restricted access');
global $path;
load_js("Lib/js/vue.global.prod-3.5.22.min.js");

?>
<?php load_css("Modules/admin/static/admin_styles.css"); ?>

<div class="admin-page">
    <div class="page-header">
        <h3><?php echo tr('Components'); ?></h3>
    </div>
    <p class="page-lead"><?php echo tr('Selectively update system components or switch between branches'); ?></p>

    <div id="update-log-bound" class="panel" style="display:none">
        <div class="panel-header panel-header-static">
            <span class="panel-accent"></span>
            <span class="panel-name"><?php echo tr('Update Log'); ?></span>
        </div>
        <pre class="log"><div id="update-log"></div></pre>
    </div>

    <div id="app" class="panel" v-cloak>
        <div class="panel-header panel-header-static cmp-header">
            <span class="panel-accent"></span>
            <span class="panel-name"><?php echo tr('Components'); ?></span>
            <span class="panel-badge">{{ Object.keys(components).length }}</span>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><?php echo tr('Update or switch all components to'); ?></span>
                <button v-if="!all_custom" class="btn btn-success" @click="all('stable')">Stable</button>
                <button v-if="!all_custom" class="btn btn-warning" @click="all('master')">Master</button>
                <button class="btn btn-danger" @click="all_custom = !all_custom">Custom</button>
                <input v-if="all_custom" class="form-control cmp-custom" v-model="custom_branch" type="text" placeholder="branch">
                <button v-if="all_custom" class="btn btn-default" @click="all('custom')">Switch</button>
            </div>
        </div>
        <div class="admin-table">
        <table class="cmp-table">
            <colgroup><col><col class="cmp-col-version"><col class="cmp-col-describe"><col class="cmp-col-changes"><col class="cmp-col-branch"><col class="cmp-col-actions"></colgroup>
            <thead>
                <tr>
                    <th><?php echo tr('Component name'); ?></th>
                    <th><?php echo tr('Version'); ?></th>
                    <th><?php echo tr('Describe'); ?></th>
                    <th><?php echo tr('Local changes'); ?></th>
                    <th><?php echo tr('Branch'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, key) in components" :key="key">
                    <td>
                        <div class="cmp-name text-truncate"><span class="col-primary">{{ item.name }}</span><a class="cmp-repo" :href="repoLink(item.url)" :title="item.url">{{ repoName(item.url) }}</a></div>
                        <div class="cmp-meta text-truncate" :title="item.path"><span v-if="protocol(item.url)=='SSH'" class="cmp-proto cmp-proto-ssh" title="<?php echo tr('SSH remote: updates only work if the service-runner user has a GitHub SSH key without a passphrase'); ?>">SSH</span><span v-else-if="protocol(item.url)" class="cmp-proto cmp-proto-https" title="<?php echo tr('HTTPS remote: updates need no key'); ?>">HTTPS</span>{{ item.path }}</div>
                    </td>
                    <td class="col-secondary">{{ item.version }}</td>
                    <td class="cmp-describe">{{ item.describe }}</td>
                    <td>
                        <span v-if="item.local_changes!=''" :title="item.local_changes" class="badge bg-danger"><?php echo tr('Yes'); ?></span>
                        <span class="badge bg-success" v-else><?php echo tr('No'); ?></span>
                    </td>
                    <td v-if="item.local_changes==''">
                        <select class="form-select input-165" v-model="item.branch" @change="switch_branch(key)">
                            <option v-for="branch in item.branches_available" :key="branch">{{ branch }}</option>
                        </select>
                    </td>
                    <td v-else class="col-secondary">{{ item.branch }}</td>
                    <td class="text-end"><button class="btn btn-default btn-sm" v-if="item.local_changes==''" @click="update(key)"><?php echo tr('Update'); ?></button></td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
var components = <?php echo json_encode($components); ?>;

var log_end = "";

var app = Vue.createApp({
    data() {
        return {
            all_custom: false,
            custom_branch: "",
            components: components
        };
    },
    methods: {
        // git@github.com:emoncms/app.git and https://github.com/emoncms/app.git to emoncms/app
        repoName: function(url) {
            var m = (url || "").match(/github\.com[:\/](.+?)(\.git)?$/);
            return m ? m[1] : url;
        },
        protocol: function(url) {
            if (/^https?:\/\//.test(url || "")) return "HTTPS";
            if (/^(ssh:\/\/|[\w.-]+@[\w.-]+:)/.test(url || "")) return "SSH";
            return "";
        },
        repoLink: function(url) {
            var m = (url || "").match(/github\.com[:\/](.+?)(\.git)?$/);
            return m ? "https://github.com/" + m[1] : url;
        },
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
}).mount('#app');

function component_update(name,branch) {
    $.ajax({                                      
        url: path+'admin/component/update',                         
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
        url: path+'admin/component/update-all',
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
  $.ajax({ url: path+"admin/update/log", async: true, dataType: "text", success: function(result)
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
