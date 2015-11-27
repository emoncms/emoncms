
var feed = {

  apikey: "",
  
  'create':function(tag, name, datatype, engine, options){
    var result = {};
    $.ajax({ url: path+"feed/create.json", data: "tag="+tag+"&name="+name+"&datatype="+datatype+"&engine="+engine+"&options="+JSON.stringify(options), dataType: 'json', async: false, success: function(data){result = data;} });
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
    var result = {};
    $.ajax({ url: path+"feed/set.json", data: "id="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data){} });
    return result;
  },

  'remove':function(id){
    $.ajax({ url: path+"feed/delete.json", data: "id="+id, async: false, success: function(data){} });
  },

  'get_data':function(feedid,start,end,interval,skipmissing,limitinterval){
    var feedIn = [];
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "&apikey="+feed.apikey;
  //if (skipmissing == undefined) skipmissing = 1;
  //if (limitinterval == undefined) limitinterval = 1;
    $.ajax({                                      
      url: path+'feed/data.json',                         
      data: "id="+feedid+"&start="+start+"&end="+end+"&interval="+interval+"&skipmissing="+skipmissing+"&limitinterval="+limitinterval+apikeystr,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedIn = data_in; } 
    });
    return feedIn;
  },

  'get_kwhatpowers':function(feedid,points){
    var feedIn = [];
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "&apikey="+feed.apikey;
    $.ajax({                                      
      url: path+'feed/kwhatpowers.json',                         
      data: "id="+feedid+"&points="+JSON.stringify(points)+apikeystr,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedIn = data_in; } 
    });
    return feedIn;
  },

  'histogram':function(feedid,start,end){
    var feedIn = [];
    var apikeystr = ""; if (feed.apikey!="") apikeystr = "&apikey="+feed.apikey;
    $.ajax({                                      
      url: path+'feed/histogram.json',                         
      data: "id="+feedid+"&start="+start+"&end="+end+"&res=1"+apikeystr,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedIn = data_in; } 
    });
    return feedIn;
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
  }
}

