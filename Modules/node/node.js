
var node = {

  apikey: "",

  'getall':function()
  {
    var result = {};
    $.ajax({ url: path+"node/getall.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },
  
  'setdecoder':function(nodeid,decoder)
  {
    var result = {};
    $.ajax({ url: path+"node/setdecoder.json", data: "nodeid="+nodeid+"&decoder="+JSON.stringify(decoder), async: false, success: function(data){} });
    return result;
  }
}

