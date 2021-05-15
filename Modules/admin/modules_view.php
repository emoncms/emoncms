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
    <tr v-for="item in components">
      <td>{{ item.name }}<br><span style="font-size:12px">{{ item.url }}</span></td>
      <td>{{ item.version }}</td>
      <td>{{ item.describe }}</td>
      <td></td>
      <td>
        <select v-model="item.branch">
          <option>stable</option>
          <option>master</option>
          <option>menu_v3</option>
        </select>
      </td>
      <td><button class="btn">Update</button></td>
    </tr>

  </table>

</div>

<script>

var components = <?php echo json_encode($components); ?>;

    var app = new Vue({
        el: '#app',
        data: {
            components: components
        }
    });

</script>
