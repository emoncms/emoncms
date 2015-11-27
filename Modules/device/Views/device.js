var device = {
    'list':function()
    {
        var result = {};
        $.ajax({ url: path+"device/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
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

    'create':function(id)
    {
        $.ajax({ url: path+"device/create.json", data: "id="+id, async: false, success: function(data){} });
    },

    'listtemplates':function()
    {
        var result = {};
        $.ajax({ url: path+"device/listtemplates.json", dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
    },

    'inittemplate':function(id)
    {
        var result = {};
        $.ajax({ url: path+"device/inittemplate.json", data: "id="+id, dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
    }
}
