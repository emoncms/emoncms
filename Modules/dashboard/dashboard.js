var dashboard = {

  'add':function(){
	  $.ajax({ url: path+"dashboard/create.json", data: "", async: false, success: function(data){} });
  },

  'remove':function(id){
    $.ajax({ url: path+"dashboard/delete.json", data: "id="+id, async: false, success: function(data){} });
  },
  
  'list':function(){
    var result = {};
    $.ajax({ url: path+"dashboard/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },

  'set':function(id, fields){
    var result = {};
    $.ajax({ url: path+"dashboard/set.json", data: "id="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data){result = data;} });
    return result;
  },

  'setcontent':function(id, content,height){
    var result = {};
    $.ajax({
        type: "POST",
        url :  path+"dashboard/setcontent.json",
        data : "&id="+id+'&content='+encodeURIComponent(content)+'&height='+height,
        dataType: 'json',
        async: false,
        success : function(data) { result = data; }
    });
    return result;
  },

  'clone':function(id){
    var result = {};
    $.ajax({ url: path+"dashboard/clone.json", data: "id="+id, async: false, success: function(data){result = data;} });
    return result;
  }

}