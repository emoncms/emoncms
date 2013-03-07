
var input = {

  'list':function()
  {
    var result = {};
    $.ajax({ url: path+"input/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },

  'set':function(id, fields)
  {
    var result = {};
    $.ajax({ url: path+"input/set.json", data: "inputid="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data){} });
    return result;
  },

  'delete':function(id)
  {
    $.ajax({ url: path+"input/delete.json", data: "inputid="+id, async: false, success: function(data){} });
  },

  // Process

  'add_process':function(inputid,processid,arg,newfeedname)
  {
    var result = {};
    $.ajax({ url: path+"input/process/add.json", data: "inputid="+inputid+"&processid="+processid+"&arg="+arg+"&newfeedname="+newfeedname, async: false, success: function(data){result = data;} });
    return result;
  },

  'processlist':function(inputid)
  {
    var result = {};
    $.ajax({ url: path+"input/process/list.json", data: "inputid="+inputid, async: false, success: function(data){result = data;} });
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
    $.ajax({ url: path+"input/process/moveby.json", data: "inputid="+inputid+"&processid="+processid+"&moveby="+moveby, async: false, success: function(data){result = data;} });
    return result;
  },

  'reset_processlist':function(inputid,processid,moveby)
  {
    var result = {};
    $.ajax({ url: path+"input/process/reset.json", data: "inputid="+inputid, async: false, success: function(data){result = data;} });
    return result;
  }

}

