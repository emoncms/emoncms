// Get feed list
function get_feed_list(apikey){
  var list = [];
  $.ajax({                    
    type: "GET",
    url: path+"feed/list.json?apikey="+apikey,       
    dataType: 'json',
    async: false,
    success: function(dt) { list = dt; }
  });
  return list;
}

// Get feed data
function get_feed_data(feedID,start,end,interval,skipmissing,limitinterval){
  var feedIn = [];
  var query = "id="+feedID+"&start="+start+"&end="+end+"&interval="+interval+"&skipmissing="+skipmissing+"&limitinterval="+limitinterval;
  if (apikey!="") query+= "&apikey="+apikey;
  $.ajax({                  
    url: path+'feed/data.json',             
    data: query,  
    dataType: 'json',               
    async: false,
    success: function(dt) { feedIn = dt; }
  });
  return feedIn;
}

// Get feed data
function get_feed_data_DMY(feedID,start,end,mode){
  var feedIn = [];
  var query = "id="+feedID+"&start="+parseInt(start)+"&end="+parseInt(end)+"&mode="+mode;
  if (apikey!="") query+= "&apikey="+apikey;
  $.ajax({                  
    url: path+'feed/data.json',             
    data: query,  
    dataType: 'json',               
    async: false,
    success: function(dt) { feedIn = dt; }
  });
  return feedIn;
}

// Get feed data async with callback
function get_feed_data_async(callback,context,feedID,start,end,interval,skipmissing,limitinterval){
  var query = "id="+feedID+"&start="+start+"&end="+end+"&interval="+interval+"&skipmissing="+skipmissing+"&limitinterval="+limitinterval;
  if (apikey!="") query+= "&apikey="+apikey;
  return $.ajax({                  
    url: path+'feed/data.json',             
    data: query,  
    dataType: 'json',   
    async: true,  
    success: function(dt) { 
      if ( typeof callback === "function" ) {
        callback(context,dt);
      }
    }
  });
}

// Get feed data async with callback
function get_feed_data_DMY_async(callback,context,feedID,start,end,mode){
  var query = "id="+feedID+"&start="+start+"&end="+end+"&mode="+mode;
  if (apikey!="") query+= "&apikey="+apikey;
  return $.ajax({                  
    url: path+'feed/data.json',             
    data: query,
    dataType: 'json',   
    async: true,  
    success: function(dt) { 
      if ( typeof callback === "function" ) {
        callback(context,dt);
      }
    }
  });
}

// Get histogram data
function get_histogram_data(feedID,start,end){
  var feedIn = [];
  $.ajax({                  
    url: path+'feed/histogram.json',             
    data: "id="+feedID+"&start="+start+"&end="+end+"&apikey="+apikey,  
    dataType: 'json',               
    async: false,
    success: function(dt) { feedIn = dt; }
  });
  return feedIn;
}

// Get kwh per day at power range
function get_kwhatpower(feedid,rmin,rmax){
  var feedIn = [];
  $.ajax({                    
    url: path+'feed/kwhatpower.json',             
    data: "id="+feedid+"&min="+rmin+"&max="+rmax+"&apikey="+apikey,
    dataType: 'json',
    async: false,            
    success: function(dt) { feedIn = dt; } 
  });
  return feedIn;
}

// Get feed data
function get_multigraph(apikey){
  var apikey = "";
  if (apikey!="") apikey= "apikey="+apikey;
  var feedlist = [];
  $.ajax({                    
    type: "GET",
    url: path+"vis/multigraphget.json",
    data: apikey, 
    async: false,  
    dataType: 'json',   
    success: function(dt){feedlist = dt;}
  });
  return feedlist;
}

// Get feed data
function save_multigraph(write_apikey,feedlist){
  var feedlist_save = eval(JSON.stringify(feedlist));
  for(var i in feedlist_save) { feedlist_save[i].plot.data = null; }
  $.ajax({                    
    type: "POST",
    url: path+"vis/multigraphsave.json",
    data: "data="+JSON.stringify(feedlist_save)+"&apikey="+write_apikey,
    success: function(msg) {console.log(msg);}
  });
}
