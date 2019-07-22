// JSHint settings
/* globals path */

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
    // now returns promise if async == true
    'set':function(id, fields, async)
    {
        async = async || false
        var result = {};
        var options = { 
            url: path+"input/set.json",
            data: "inputid="+id+"&fields="+JSON.stringify(fields)
        }
        if(!async) options.async = false
        var jqxhr = $.ajax(options)
        .done(function(response){
            result = response
            if (!result.success) alert(result.message);
        })
        .error(function(msg){
            alert('Error saving data. '+msg)
        })
        // return the synchronous result or the asynchronous promise
        return !async ? result : jqxhr;
    },

    'remove':function(id)
    {
        $.ajax({ url: path+"input/delete.json", data: "inputid="+id, async: false, success: function(data){} });
    },
    
    'delete_multiple':function(ids)
    {
        $.ajax({ url: path+"input/delete.json", data: "inputids="+JSON.stringify(ids), async: false, success: function(data){} });
    },

    delete_multiple_async: function(ids) {
        return $.getJSON(path + "input/delete.json", {inputids: JSON.stringify(ids)})
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

