
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


  // if ($route->action == 'data') $result = $feed->get_data(get('id'),get('start'),get('end'),get('dp'));
  'get_data':function(feedid,start,end,dp)
  {
    var feedIn = [];
    $.ajax({                                      
      url: path+'feed/data.json',                         
      data: "&apikey="+apikey+"&id="+feedid+"&start="+start+"&end="+end+"&dp="+dp,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedIn = data_in; } 
    });
    return feedIn;
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
  },

  'histogram':function(feedid,start,end)
  {
    var feedIn = [];
    $.ajax({                                      
      url: path+'feed/histogram.json',                         
      data: "&apikey="+apikey+"&id="+feedid+"&start="+start+"&end="+end+"&res=1",
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedIn = data_in; } 
    });
    return feedIn;
  }

}

