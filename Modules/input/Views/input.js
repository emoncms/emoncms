
var input = {

    'list':function()
    {
        var result = {};
        $.ajax({ url: path+"input/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
    },

    'list_assoc':function()
    {
        var result = {};
        $.ajax({ url: path+"input/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });

        var inputs = {};
        for (z in result) inputs[result[z].id] = result[z];

        return inputs;
    },

    'set':function(id, fields)
    {
        var result = {};
        $.ajax({ url: path+"input/set.json", data: "inputid="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data){} });
        return result;
    },

    'remove':function(id)
    {
        $.ajax({ url: path+"input/delete.json", data: "inputid="+id, async: false, success: function(data){} });
    },

    // Process

    'set_process':function(inputid,processlist)
    {
        var result = {};
        $.ajax({ url: path+"input/process/set.json?inputid="+inputid, method: "POST", data: "processlist="+processlist, async: false, success: function(data){result = data;} });
        return result;
    },

    'get_process':function(inputid)
    {
        var result = {};
        $.ajax({ url: path+"input/process/get.json", data: "inputid="+inputid, async: false, dataType: 'json', success: function(data){result = data;} });
        var processlist = [];
        if (result!="")
        {
            var tmp = result.split(",");
            for (n in tmp)
            {
                var process = tmp[n].split(":"); 
                processlist.push(process);
            }
        }
        return processlist;
    },

    'reset_processlist':function(inputid,processid)
    {
        var result = {};
        $.ajax({ url: path+"input/process/reset.json", data: "inputid="+inputid, async: false, success: function(data){result = data;} });
        return result;
    }

}

