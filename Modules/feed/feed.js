
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

  'remove':function(id)
  {
    $.ajax({ url: path+"feed/delete.json", data: "id="+id, async: false, success: function(data){} });
  },

  'get_kwhatpowers':function(feedid,points)
  {
    var feedIn = [];
    $.ajax({                                      
      url: path+'feed/kwhatpowers.json',                         
      data: "&apikey="+apikey+"&id="+feedid+"&points="+JSON.stringify(points),
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedIn = data_in; } 
    });
    return feedIn;
  }

}

