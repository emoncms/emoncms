
var feed = {

  apikey: "",
  
  'create':function(tag, name, engine, options, unit){
    var result = {};
    var data = {
      tag: tag,
      name: name,
      engine: engine,
      options: JSON.stringify(options),
      unit: unit || ''
    }
    $.ajax({ url: path+"feed/create.json", data: data, dataType: 'json', async: false, success: function(data){result = data;} });
    return result;
  },
  
  'list':function(){
    var result = {};
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "?apikey="+feed.apikey;
    
    $.ajax({ url: path+"feed/list.json"+apikeystr, dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },
  
  'list_assoc':function(){
    var result = {};
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "?apikey="+feed.apikey;
    
    $.ajax({ url: path+"feed/list.json"+apikeystr, dataType: 'json', async: false, success: function(data) {result = data;} });
    
    var feeds = {};
    for (z in result) feeds[result[z].id] = result[z];
    
    return feeds;
  },
  
  'list_by_id':function(){
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
  
  'list_by_name':function(){
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

  'set':function(id, fields){
    $.ajax({ url: path+"feed/set.json", data: "id="+id+"&fields="+JSON.stringify(fields), success: function(result) {
      if (result.success!=undefined && !result.success) {
        alert(result.message);
      }
    }});
  },

  'remove':function(id){
    $.ajax({ url: path+"feed/delete.json", data: "id="+id, async: false, success: function(data){
         // Clean processlists of deleted feeds
         $.ajax({ url: path+"input/cleanprocesslistfeeds.json", async: true, success: function(data){} });
    }});
  },

  'clear':function(id){
    let response = false;
    let data = {
      id: id
    }
    $.ajax({ url: path+"feed/clear.json", data: data, async: false, success: function(data){ response = data} });
    return response;
  },
  'trim':function(id,start_time){
    let response = false;
    let data = {
      id: id,
      start_time: start_time
    }
    $.ajax({ url: path+"feed/trim.json", data: data, async: false, success: function(data){ response = data} });
    return response;
  },

  'get_value': function(feedid,time) 
  {
      var result = feed.get_data(feedid,time,time+1000,1,0,0);
      if (result.length>0) return result[0];
      return false;
  },

  'get_data':function(feedid,start,end,interval,skipmissing=0,limitinterval=0){
    var feedIn = [];
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "&apikey="+feed.apikey;
    $.ajax({                                      
      url: path+'feed/data.json',                         
      data: "id="+feedid+"&start="+start+"&end="+end+"&interval="+interval+"&skipmissing="+skipmissing+"&limitinterval="+limitinterval+apikeystr,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedIn = data_in; } 
    });
    return feedIn;
  },
  
  'get_data_async':function(callback,feedid,start,end,interval,skipmissing=0,limitinterval=0){
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "&apikey="+feed.apikey;
    return $.ajax({                                      
      url: path+'feed/data.json',                         
      data: "id="+feedid+"&start="+start+"&end="+end+"&interval="+interval+"&skipmissing="+skipmissing+"&limitinterval="+limitinterval+apikeystr,
      dataType: 'json',
      async: true,                      
      success: function(result) {
        if ( typeof callback === "function" ) {
          callback(result);
        }
      } 
    });
  },

  // Virtual feed process
  'set_process':function(feedid,processlist){
    var result = {};
    $.ajax({ url: path+"feed/process/set.json?id="+feedid, method: "POST", data: "processlist="+processlist, async: false, success: function(data){result = data;} });
    return result;
  },

  'get_process':function(feedid){
    var result = {};
    $.ajax({ url: path+"feed/process/get.json", data: "id="+feedid, async: false, dataType: 'json', success: function(data){result = data;} });
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

  'reset_processlist':function(feedid,processid){
    var result = {};
    $.ajax({ url: path+"feed/process/reset.json", data: "id="+feedid, async: false, success: function(data){result = data;} });
    return result;
  },
  
  'meta':function(feedid)
  {
    var result = {};
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "&apikey="+feed.apikey;
    $.ajax({                                      
      url: path+'feed/getmeta.json',                         
      data: apikeystr+"&id="+feedid,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { result = data_in; } 
    });
    return result;
  }
}

