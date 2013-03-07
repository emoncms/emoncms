
var user = {

  'login':function(username,password)
  {
    var result ={};
    $.ajax({
      url: path+"user/login.json",
      data: "&username="+username+"&password="+password,
      dataType: 'json',
      async: false,
      success: function(data)
      {
        result = data;
      } 
    });
    return result;
  },

  'register':function(username,password,email)
  {
    var result = {};
    $.ajax({
      url: path+"user/register.json",
      data: "&username="+username+"&password="+password+"&email="+email,
      dataType: 'json',
      async: false, 
      success: function(data)
      {
        result = data;
      } 
    });
    return result;
  },

  'get':function()
  {
    var result = {};
    $.ajax({ url: path+"user/get.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },

  'set':function(data)
  {
    var result = {};
    $.ajax({ url: path+"user/set.json", data: "&data="+JSON.stringify(data) ,dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  }

}

