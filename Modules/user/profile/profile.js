var user_data = user.get();
var last_username = ""+user_data.username
var last_email = ""+user_data.email
var last_language = ""+user_data.language;

var timezones = [];
$.ajax({ url: path+"user/gettimezones.json", dataType: 'json', async: true, success: function(result) {
    app.timezones = result;
}});

var app = new Vue({
    el: '#app',
    data: {
        user: user_data,
        timezones: timezones,
        languages: languages,
        edit: {
            username: false,
            email: false,
            password: false,
            gravatar: false,
            name: false,
            location: false,
            timezone: false,
            language: false,
            startingpage: false
        },
        password: {
            current: "",
            new: "",
            repeat: ""
        }
    },
    methods: {
        show_edit: function(key) {
            app.edit[key] = true;
        },
        save: function(key) {
            user.set(app.user);
            app.edit[key] = false;
            // refresh the page if the language has been changed.
            if (app.user.language!=last_language) {
                window.location.href = path+"user/view";
            }
        },
        save_username: function(username) {
            if (username!=last_username) {
                $.ajax({
                    url: path+"user/changeusername.json",
                    data: "&username="+username,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success!=undefined) {
                            if (result.success) {
                                last_username = username;
                                app.edit.username = false;
                            } else {
                                alert(result.message)                        
                            }
                        }
                    }
                });
            } else {
                app.edit.username = false;
            }
        },
        save_email: function(email) {
            if (email!=last_email) {
                $.ajax({
                    url: path+"user/changeemail.json",
                    data: "&email="+encodeURIComponent(email),
                    dataType: 'json',
                    success: function(result) {
                        if (result.success!=undefined) {
                            if (result.success) {
                                last_email = email;
                                app.edit.email = false;
                            } else {
                                alert(result.message)                        
                            }
                        }
                    }
                });
            } else {
                app.edit.email = false;
            }
        },
        change_password: function() {
            if (app.password.current=='') {
                alert("Current password field empty");
                return false;   
            }
            if (app.password.new=='') {
                alert("New password field empty");
                return false;   
            }        
            if (app.password.repeat=='') {
                alert("Repeat password field empty");
                return false;   
            }
            if (app.password.new != app.password.repeat) {
                alert(str_passwords_do_not_match);
                return false;
            }
            $.ajax({
                type: 'POST',
                url: path+"user/changepassword.json",
                data: "old="+encodeURIComponent(app.password.current)+"&new="+encodeURIComponent(app.password.new),
                dataType: 'json',
                success: function(result) {
                    if (result.success!=undefined) {
                        if (result.success) {
                            app.edit.password = false;
                            alert(result.message)       
                        } else {
                            alert(result.message)
                        }
                    }
                }
            });
        },
        delete_account: function() {
            $('#myModal').modal('show');
            $.ajax({type:"POST",url: path+"user/deleteall.json", data: "mode=dryrun", dataType: 'text', success: function(result){
                $("#deleteall-output").html(result);
            }});
        },
        new_apikey: function(type) {
            $("#apikey_type").html(type);
            $('#modalNewApikey').modal('show');
        }
    }
});

//QR COde Generation
var urlCleaned = window.location.href.replace("user/view" ,"");
var qrcode = new QRCode(document.getElementById("qr_apikey"), {
    text: urlCleaned + "app?readkey=" + user_data.apikey_read  + "#myelectric",
    width: 192,
    height: 192,
    colorDark : "#000000",
    colorLight : "#ffffff",
    correctLevel : QRCode.CorrectLevel.H
}); //Re-designed on-board QR generation using javascript

$("#confirmdelete").click(function() {
    var password = $("#delete-account-password").val();
    
    $.ajax({type:"POST", url: path+"user/deleteall.json", data: "mode=permanentdelete&password="+encodeURIComponent(password), dataType: 'text', success: function(result){
        $("#deleteall-output").html(result);
        
        if (result!="invalid password") {
            $("#canceldelete").hide();
            $("#confirmdelete").hide();
            $("#logoutdelete").show();
            $(".delete-account-s1").hide();
            $(".delete-account-s2").show();
        }
    }});
});

$("#logoutdelete").click(function() {
    $.ajax({url: path+"user/logout.json", dataType: 'text', success: function(result){
        window.location = path;
    }});
});

$("#confirm_generate_apikey").click(function() {
    var type = $("#apikey_type").html();
    $.ajax({ url: path+"user/newapikey"+type+".json", dataType: 'json', success: function(result){
        app.user['apikey_'+type] = result;
        $('#modalNewApikey').modal('hide');
    }});
});

// Theme selection used in conjunction with code in Lib/emoncms.js
$(".themecolor[name='"+current_themecolor+"']").addClass("color-box-active");
$(".themecolor").click(function() {
    themecolor = $(this).attr("name");
    $("html").removeClass('theme-'+current_themecolor).addClass('theme-'+themecolor);
    localStorage.setItem('themecolor', themecolor);
    $(".themecolor[name='"+current_themecolor+"']").removeClass("color-box-active"); 
    $(".themecolor[name='"+themecolor+"']").addClass("color-box-active");    
    current_themecolor = themecolor
});
$(".sidebarcolor[name='"+current_themesidebar+"']").addClass("color-box-active");
$(".sidebarcolor").click(function() {
    themesidebar = $(this).attr("name");
    $("html").removeClass('sidebar-'+current_themesidebar).addClass('sidebar-'+themesidebar);
    localStorage.setItem('themesidebar', themesidebar);
    $(".sidebarcolor[name='"+current_themesidebar+"']").removeClass("color-box-active"); 
    $(".sidebarcolor[name='"+themesidebar+"']").addClass("color-box-active"); 
    current_themesidebar = themesidebar
});
