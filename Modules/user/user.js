
var user = {

  'login':function(username,password,rememberme)
  {
    var result ={};
    $.ajax({
      type: "POST",
      url: path+"user/login.json",
      data: "&username="+encodeURIComponent(username)+"&password="+encodeURIComponent(password)+"&rememberme="+encodeURIComponent(rememberme),
      dataType: "json",
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
      data: "&username="+encodeURIComponent(username)+"&password="+encodeURIComponent(password)+"&email="+encodeURIComponent(email),
      dataType: "json",
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
    $.ajax({ url: path+"user/get.json", dataType: "json", async: false, success: function(data) {result = data;} });
    
    if (result.success!==undefined) {
        alert(result.message);
    }
    return result;
  },
  
  'passwordreset':function(username,email)
  {
    var result = {};
    $.ajax({ url: path+"user/passwordreset.json", data: "&username="+encodeURIComponent(username)+"&email="+encodeURIComponent(email), dataType: "json", async: false, success: function(data) {result = data;} });
    return result;
  },

  'set':function(data)
  {
    var result = {};
    $.ajax({ url: path+"user/set.json", data: "&data="+encodeURIComponent(JSON.stringify(data)) ,dataType: "json", async: false, success: function(data) {result = data;} });
    return result;
  },

  'newapikeywrite':function()
  {
    var result = {};
    $.ajax({ url: path+"user/newapikeywrite.json", dataType: "json", async: false, success: function(data) {result = data;} });
    return result;
  },
  
  'newapikeyread':function()
  {
    var result = {};
    $.ajax({ url: path+"user/newapikeyread.json", dataType: "json", async: false, success: function(data) {result = data;} });
    return result;
  },
  
  'timezoneoffset':function()
  {
    var result = {};
    $.ajax({ url: path+"user/timezone.json", dataType: "json", async: false, success: function(data) {result = data;} });
    return result;
  }

}

