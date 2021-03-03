
var user = {

  'login':function(username,password,rememberme,referrer)
  {
    var result = {};
    var data = {
        username: encodeURIComponent(username),
        password: encodeURIComponent(password),
        rememberme: encodeURIComponent(rememberme),
        referrer: encodeURIComponent(referrer)
    }
    $.ajax({
      type: "POST",
      url: path+"user/login.json",
      data: data,
      dataType: "text",
      async: false,
      success: function(data_in)
      {
         try {
             result = JSON.parse(data_in);
             if (result.success==undefined) result = data_in;
         } catch (e) {
             result = data_in;
         }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        if(xhr.status==404) {
            result = "404 Not Found: Is modrewrite configured on your system?"
        } else {
            result = xhr.status+" "+thrownError;
        }
      }
    });
    return result;
  },

  'register':function(username,password,email,timezone)
  {
    var result = {};
    $.ajax({
      type: "POST",
      url: path+"user/register.json",
      data: "&username="+encodeURIComponent(username)+"&password="+encodeURIComponent(password)+"&email="+encodeURIComponent(email)+"&timezone="+encodeURIComponent(timezone),
      dataType: "text",
      async: false,
      success: function(data_in)
      {
         try {
             result = JSON.parse(data_in);
             if (result.success==undefined) result = data_in;
         } catch (e) {
             result = data_in;
         }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        if(xhr.status==404) {
            result = "404 Not Found: Is modrewrite configured on your system?"
        } else {
            result = xhr.status+" "+thrownError;
        }
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
    $.ajax({ type: "POST", url: path+"user/set.json", data: "&data="+encodeURIComponent(JSON.stringify(data)) ,dataType: "json", async: false, success: function(data) {result = data;} });
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

