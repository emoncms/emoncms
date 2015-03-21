var schedule = {
    
    'list':function()
    {
        var result = {};
        $.ajax({ url: path+"schedule/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
    },

    'get':function(id)
    {
        var result = {};
        $.ajax({ url: path+"schedule/get.json", data: "id="+id, async: false, success: function(data){ result = data;} });
        return result;
    },

    'set':function(id, fields)
    {
        var result = {};
        $.ajax({ url: path+"schedule/set.json", data: "id="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data) {result = data;} });
        return result;
    },

    'remove':function(id)
    {
        $.ajax({ url: path+"schedule/delete.json", data: "id="+id, async: false, success: function(data){} });
    },
    
    'test':function(id)
    {
        var result = {};
        $.ajax({ url: path+"schedule/test.json", data: "id="+id, async: false, success: function(data){result = data;} });
        return result;
    }
}
