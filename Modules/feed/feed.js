
var feed = {

  'list':function()
  {
    var result = {};
    $.ajax({ url: path+"feed/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },

  'set':function(id, fields)
  {
    var result = {};
    $.ajax({ url: path+"feed/set.json", data: "id="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data){} });
    return result;
  },

  'delete':function(id)
  {
    $.ajax({ url: path+"feed/delete.json", data: "id="+id, async: false, success: function(data){} });
  }

}

