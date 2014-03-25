
var user = {

  'login':function(username,password,rememberme)
  {
    var result ={};
    $.ajax({
      type: "POST",
      url: path+"user/login.json",
      data: "&username="+username+"&password="+encodeURIComponent(password)+"&rememberme="+rememberme,
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
      type: "POST",
      url: path+"user/register.json",
      data: "&username="+username+"&password="+encodeURIComponent(password)+"&email="+email,
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
  
  'passwordreset':function(username,email)
  {
    var result = {};
    $.ajax({ url: path+"user/passwordreset.json", data: "&username="+username+"&email="+email, dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },

  'set':function(data)
  {
    var result = {};
    $.ajax({ url: path+"user/set.json", data: "&data="+JSON.stringify(data) ,dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },

  'newapikeywrite':function()
  {
    var result = {};
    $.ajax({ url: path+"user/newapikeywrite.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  },
  
  'newapikeyread':function()
  {
    var result = {};
    $.ajax({ url: path+"user/newapikeyread.json", dataType: 'json', async: false, success: function(data) {result = data;} });
    return result;
  }

}

