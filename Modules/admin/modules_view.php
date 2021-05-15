<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>

<h3>Components</h3>

<div id="app">

  <table class="table table-bordered">
    <tr>
      <th>Component name</th>
      <th>Version</th>
      <th>Describe</th>
      <th>Local changes</th>
      <th>Branch</th>
      <th></th>
    </tr>
    <tr v-for="item, key in components">
      <td>{{ item.name }}<br><span style="font-size:12px"><b>Location:</b> {{ item.location }}</span><br><span style="font-size:12px"><b>URL:</b> <a :href="item.url">{{ item.url }}</a></span></td>
      <td>{{ item.version }}</td>
      <td>{{ item.describe }}</td>
      <td><span v-if="item.local_changes!=''" :title="item.local_changes" class="label label-important">Yes</span><span class="label label-success" v-else>No</span></td>
      <td v-if="item.local_changes==''">
        <select v-model="item.branch" @change="switch_branch(key)">
          <option v-for="branch in item.branches_available">{{ branch }}</option>
        </select>
      </td>
      <td v-else>{{ item.branch }}</td>
      <td><button class="btn" v-if="item.local_changes==''" @click="update(key)">Update</button></td>
    </tr>

  </table>

</div>

<script>

var components = <?php echo json_encode($components); ?>;

var app = new Vue({
    el: '#app',
    data: {
        components: components
    },
    methods: {
        switch_branch: function(name) {
            console.log("switch_branch: "+name+" "+components[name].branch)
            switch_module_branch(name,components[name].branch)  
        },
        update: function(name) {
            console.log("switch_branch: "+name+" "+components[name].branch)
            switch_module_branch(name,components[name].branch)
        }
    }
});

function switch_module_branch(module,branch) {
    $.ajax({                                      
        url: path+'admin/switch-module-branch',                         
        data: "module="+module+"&branch="+branch,
        dataType: 'text',
        success: function(result) { 
            console.log(result)
        } 
    });   
}

</script>
