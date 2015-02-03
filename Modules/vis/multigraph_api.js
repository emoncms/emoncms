
var multigraph = {

  'new':function(id)
  {
    var result = {};
    $.ajax({ url: path+"vis/multigraph/new.json", async: false, success: function(data){result = data;} });
    return result;
  },

  'set':function(id,feedlist,name)
  {
    var result = {};
    $.ajax({ url: path+"vis/multigraph/set.json", data: "id="+id+"&name="+encodeURIComponent(name)+"&feedlist="+encodeURIComponent(JSON.stringify(feedlist)), async: false, success: function(data){result = data;} });
    return result;
  },

  'get':function(id)
  {
    var result = {};
    $.ajax({ url: path+"vis/multigraph/get.json", data: "id="+id, dataType: 'json', async: false, success: function(data){result = data;} });
    return result;
  },

  'remove':function(id)
  {
    var result = {};
    $.ajax({ url: path+"vis/multigraph/delete.json", data: "id="+id, async: false, success: function(data){result = data;} });
    return result;
  },

  'getlist':function()
  {
    var result = {};
    $.ajax({ url: path+"vis/multigraph/getlist.json", async: false, dataType: 'json', success: function(data){result = data;} });
    return result;
  },
  
  'getname':function(id)
  {
    var result = {};
    $.ajax({ url: path+"vis/multigraph/getname.json", data: "id="+id, dataType: 'json', async: false, success: function(data){result = data;} });
    return result;
  },
  

}
