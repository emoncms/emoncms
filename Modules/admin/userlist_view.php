<?php
    global $path;
?>

<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>

<h2>Users</h2>

<div id="table"></div>

<script>

    var path = "<?php echo $path; ?>";

    var admin = {
        'userlist':function()
        {
            var result = {};
            $.ajax({ url: path+"admin/userlist.json", dataType: 'json', async: false, success: function(data) {result = data;} });
            return result;
        }
    }

    // Extend table library field types
    for (z in customtablefields)
        table.fieldtypes[z] = customtablefields[z];

    table.element = "#table";

    table.fields = {
        'id':{'title':"<?php echo _('Id'); ?>", 'type':"textlink", 'link':"setuser?id="},
        'username':{'title':"<?php echo _('Username'); ?>", 'type':"fixed"},
        'email':{'title':"<?php echo _('Email'); ?>", 'type':"fixed"}
    }

    table.data = admin.userlist();
    table.draw();

</script>