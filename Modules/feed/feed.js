
var feed = {

  apikey: "",
  
  'create':function(name, datatype, engine, options)
  {
    var result = {};
    $.ajax({ url: path+"feed/create.json", data: "name="+name+"&datatype="+datatype+"&engine="+engine+"&options="+JSON.stringify(options), dataType: 'json', async: false, success: function(data){result = data;} });
    return result;
  },
  
  'list':function()
  {
    var result = {};
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "?apikey="+feed.apikey;
    
    $.ajax({ url: path+"feed/list.json"+apikeystr, dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },
  
  'list_assoc':function()
  {
    var result = {};
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "?apikey="+feed.apikey;
    
    $.ajax({ url: path+"feed/list.json"+apikeystr, dataType: 'json', async: false, success: function(data) {result = data;} });
    
    var feeds = {};
    for (z in result) feeds[result[z].id] = result[z];
    
    return feeds;
  },
  
  'list_by_id':function()
  {
    var feeds = {};
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "?apikey="+feed.apikey;
    
    $.ajax({ url: path+"feed/list.json"+apikeystr, dataType: 'json', async: false, success: function(data) {feeds = data;} });
    
    var tmp = {};
    for (z in feeds)
    {
      tmp[feeds[z]['id']] = parseFloat(feeds[z]['value']);
    }
    var feeds = tmp;
    
    return feeds;
  },
  
  'list_by_name':function()
  {
    var feeds = {};
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "?apikey="+feed.apikey;
    
    $.ajax({ url: path+"feed/list.json"+apikeystr, dataType: 'json', async: false, success: function(data) {feeds = data;} });
    
    var tmp = {};
    for (z in feeds)
    {
      tmp[feeds[z]['name']] = parseFloat(feeds[z]['value']);
    }
    var feeds = tmp;
    
    return feeds;
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

  'get_data':function(feedid,start,end,dp)
  {
    var feedIn = [];
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "&apikey="+feed.apikey;
    $.ajax({                                      
      url: path+'feed/data.json',                         
      data: apikeystr+"&id="+feedid+"&start="+start+"&end="+end+"&dp="+dp,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedIn = data_in; } 
    });
    return feedIn;
  }
}

