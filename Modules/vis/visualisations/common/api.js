  
  //-------------------------------------------------------------------------------
  // Get feed list
  //-------------------------------------------------------------------------------
  function get_feed_list(apikey)
  {
    var list = [];
    $.ajax({                                      
      type: "GET",
      url: path+"feed/list.json?apikey="+apikey,           
      dataType: 'json',
      async: false,
      success: function(data) { list = data; }
    });
    return list;
  }

  //-------------------------------------------------------------------------------
  // Get feed data
  //-------------------------------------------------------------------------------
  function get_feed_data(feedID,start,end,interval,skipmissing,limitinterval)
  {
    var feedIn = [];
    var query = "&id="+feedID+"&start="+start+"&end="+end+"&interval="+interval+"&skipmissing="+skipmissing+"&limitinterval="+limitinterval;
    if (apikey!="") query+= "&apikey="+apikey;

    $.ajax({                                    
      url: path+'feed/data.json',                         
      data: query,  
      dataType: 'json',                           
      async: false,
      success: function(datain) { feedIn = datain; }
    });
    return feedIn;
  }
  
  //-------------------------------------------------------------------------------
  // Get feed data async with callback
  //-------------------------------------------------------------------------------
//CHAVEIRO: TODO CHECK WHERE IS USED
  function ___get_feed_data_async(feedID,start,end,interval,pfn)
  {
    var feedIn = [];
    var query = "&id="+feedID+"&start="+start+"&end="+end+"&interval="+interval;
    if (apikey!="") query+= "&apikey="+apikey;

    $.ajax({                                    
      url: path+'feed/data.json',                         
      data: query,  
      dataType: 'json',                           
      success: function(datain) { 
        if ( typeof pfn === "function" ) {
            pfn(datain);
        }
      }
    });
  }

  //-------------------------------------------------------------------------------
  // Get histogram data
  //-------------------------------------------------------------------------------
  function get_histogram_data(feedID,start,end)
  {
    var feedIn = [];
    $.ajax({                                    
      url: path+'feed/histogram.json',                         
      data: "&apikey="+apikey+"&id="+feedID+"&start="+start+"&end="+end,  
      dataType: 'json',                           
      async: false,
      success: function(datain) { feedIn = datain; }
    });
    return feedIn;
  }

  //-------------------------------------------------------------------------------
  // Get kwh per day at power range
  //-------------------------------------------------------------------------------
  function get_kwhatpower(feedid,rmin,rmax)
  {
    var feedIn = [];
    $.ajax({                                      
      url: path+'feed/kwhatpower.json',                         
      data: "&apikey="+apikey+"&id="+feedid+"&min="+rmin+"&max="+rmax,
      dataType: 'json',
      async: false,                      
      success: function(data_in) { feedIn = data_in; } 
    });
    return feedIn;
  }

  //-------------------------------------------------------------------------------
  // Get feed data
  //-------------------------------------------------------------------------------
  function get_multigraph(apikey)
  {
    var query = path+"vis/multigraphget.json";
    if (apikey!="") query+= "?apikey="+apikey;
    console.log(query);
    var feedlist = [];
    $.ajax({                                      
      type: "GET",
      url: query, //+apikey_write,     
      async: false,    
      dataType: 'json',     
      success: function(data){feedlist = data;}
    });
    return feedlist;
  }

  //-------------------------------------------------------------------------------
  // Get feed data
  //-------------------------------------------------------------------------------
  function save_multigraph(write_apikey,feedlist)
  {
    var feedlist_save = eval(JSON.stringify(feedlist));
    for(var i in feedlist_save) { feedlist_save[i].plot.data = null; }

    $.ajax({                                      
      type: "POST",
      url: path+"vis/multigraphsave.json?apikey="+write_apikey,        
      data: "&data="+JSON.stringify(feedlist_save),
      success: function(msg) {console.log(msg);}
    });
  }
