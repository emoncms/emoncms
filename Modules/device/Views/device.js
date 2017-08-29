var device = {
    'list':function()
    {
        var result = {};
        $.ajax({ url: path+"device/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
    },

    'get':function(id)
    {
        var result = {};
        $.ajax({ url: path+"device/get.json", data: "id="+id, async: false, success: function(data) {result = data;} });
        return result;
    },

    'set':function(id, fields)
    {
        var result = {};
        $.ajax({ url: path+"device/set.json", data: "id="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data) {result = data;} });
        return result;
    },

    'remove':function(id)
    {
        $.ajax({ url: path+"device/delete.json", data: "id="+id, async: false, success: function(data){} });
    },

    'create':function(nodeid, name, description, type)
    {
        $.ajax({ url: path+"device/create.json", data: "nodeid="+nodeid+"&name="+name+"&description="+description+"&type="+type, async: false, success: function(data){} });
    },

    'listtemplates':function()
    {
        var result = {};
        $.ajax({ url: path+"device/template/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
    },

    'inittemplate':function(id)
    {
        var result = {};
        $.ajax({ url: path+"device/template/init.json", data: "id="+id, dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
    }
}
