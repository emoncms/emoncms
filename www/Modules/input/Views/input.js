
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

    'add_process':function(inputid,processid,arg)
    {
        var result = {};
        $.ajax({ url: path+"input/process/add.json", data: "inputid="+inputid+"&processid="+processid+"&arg="+arg, async: false, success: function(data){result = data;} });
        return result;
    },

    'processlist':function(inputid)
    {
        var result = {};
        $.ajax({ url: path+"input/process/list.json", data: "inputid="+inputid, async: false, dataType: 'json', success: function(data){result = data;} });
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

    'getallprocesses':function(inputid)
    {
        var result = {};
        $.ajax({ url: path+"input/getallprocesses.json", data: "inputid="+inputid, async: false, dataType: 'json', success: function(data){result = data;} });
        return result;
    },

    'delete_process':function(inputid,processid)
    {
        var result = {};
        $.ajax({ url: path+"input/process/delete.json", data: "inputid="+inputid+"&processid="+processid, async: false, success: function(data){result = data;} });
        return result;
    },

    'move_process':function(inputid,processid,moveby)
    {
        var result = {};
        $.ajax({ url: path+"input/process/move.json", data: "inputid="+inputid+"&processid="+processid+"&moveby="+moveby, async: false, success: function(data){result = data;} });
        return result;
    },

    'reset_processlist':function(inputid,processid,moveby)
    {
        var result = {};
        $.ajax({ url: path+"input/process/reset.json", data: "inputid="+inputid, async: false, success: function(data){result = data;} });
        return result;
    }

}

