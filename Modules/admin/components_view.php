<?php global $path; ?>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<h3><?php echo _('Components'); ?></h3>

<p><?php echo _('Selectively update system components or switch between branches'); ?></p>

<pre id="update-log-bound" class="log" style="display:none"><div id="update-log"></div></pre>

<div id="app">

<div class="input-prepend input-append">
    <span class="add-on"><?php echo _('Update or switch all components to'); ?></span>
    <button v-if="!all_custom"class="btn btn-success" @click="all('stable')">Stable</button>
    <button v-if="!all_custom" class="btn btn-warning" @click="all('master')">Master</button>
    <button class="btn btn-danger" @click="all_custom = !all_custom">Custom</button>
    <input v-if="all_custom" v-model="custom_branch" type="text" value="menu_v3" style="width:100px">
    <button v-if="all_custom" class="btn" @click="all('custom')">Switch</button>
</div>

  <table class="table table-bordered">
    <tr>
      <th><?php echo _('Component name'); ?></th>
      <th><?php echo _('Version'); ?></th>
      <th><?php echo _('Describe'); ?></th>
      <th><?php echo _('Local changes'); ?></th>
      <th><?php echo _('Branch'); ?></th>
      <th></th>
    </tr>
    <tr v-for="item, key in components">
      <td>{{ item.name }}<br><span style="font-size:12px"><b><?php echo _('Location:'); ?></b> {{ item.location }}</span><br><span style="font-size:12px"><b><?php echo _('URL:'); ?></b> <a :href="item.url">{{ item.url }}</a></span></td>
      <td>{{ item.version }}</td>
      <td>{{ item.describe }}</td>
      <td><span v-if="item.local_changes!=''" :title="item.local_changes" class="label label-important"><?php echo _('Yes'); ?></span><span class="label label-success" v-else><?php echo _('No'); ?></span></td>
      <td v-if="item.local_changes==''">
        <select v-model="item.branch" @change="switch_branch(key)">
          <option v-for="branch in item.branches_available">{{ branch }}</option>
        </select>
      </td>
      <td v-else>{{ item.branch }}</td>
      <td><button class="btn" v-if="item.local_changes==''" @click="update(key)"><?php echo _('Update'); ?></button></td>
    </tr>

  </table>

</div>

<a href="<?php echo $path; ?>admin/view" class="btn btn-info"><?php echo _('Return to Administration Page'); ?></a>

<script>

var components = <?php echo json_encode($components); ?>;

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
        data: "module="+name+"&branch="+branch,
        dataType: 'text',
        success: function(result) { 
            console.log(result)
            start_update_log()
            log_end = "- component updated"
        } 
    });   
}

function update_all_components(branch) {
    $.ajax({                                      
        url: path+'admin/components-update-all',                         
        data: "branch="+branch,
        dataType: 'text',
        success: function(result) { 
            console.log(result)
            start_update_log();
            log_end = "- all components updated"
        } 
    });   
}

// -------------------------------------
// Log window
// -------------------------------------

var interval = false
var log_end = "";

function start_update_log(log_end) {
    clearInterval(interval)
    interval = setInterval(update_log,500)
    $("#update-log-bound").slideDown();
}

function update_log() {
    $.ajax({ url: path+"admin/update-log", async: true, dataType: "text", success: function(result) {
        if (result=="Admin re-authentication required") {
            window.location = "/";
        }
        $("#update-log").html(result)
        scrollable = $("#update-log").parent('pre')[0];
        if(scrollable) scrollable.scrollTop = scrollable.scrollHeight;
        
        if (result.indexOf(log_end)!=-1) {
            clearInterval(interval);
            setTimeout(function() {
                $("#update-log-bound").slideUp();            
            },3000);
        }
    }});
}

</script>
