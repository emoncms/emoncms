var user_data = user.get();
var last_username = ""+user_data.username
var last_email = ""+user_data.email
var last_language = ""+user_data.language;

var timezones = [];

var profileApp = Vue.createApp({
    data: function() {
        return {
            user: user_data,
            timezones: timezones,
            languages: languages,
            translation_status: translation_status,
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
        }
    },
    methods: {
        gravatar_hash: function(value) {
            if (typeof CryptoJS === 'undefined' || value == null) return "";
            return CryptoJS.MD5(String(value).trim().toLowerCase()).toString();
        },
        show_edit: function(key) {
            this.edit[key] = true;
        },
        save: function(key) {
            user.set(this.user);
            this.edit[key] = false;
            // refresh the page if the language has been changed.
            if (this.user.language!=last_language) {
                window.location.href = path+"user/view";
            }
        },
        save_username: function(username) {
            var self = this;
            if (username!=last_username) {
                $.ajax({
                    url: path+"user/changeusername.json",
                    data: "&username="+username,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success!=undefined) {
                            if (result.success) {
                                last_username = username;
                                self.edit.username = false;
                            } else {
                                alert(result.message)                        
                            }
                        }
                    }
                });
            } else {
                self.edit.username = false;
            }
        },
        save_email: function(email) {
            var self = this;
            if (email!=last_email) {
                $.ajax({
                    url: path+"user/changeemail.json",
                    data: "&email="+encodeURIComponent(email),
                    dataType: 'json',
                    success: function(result) {
                        if (result.success!=undefined) {
                            if (result.success) {
                                last_email = email;
                                self.edit.email = false;
                            } else {
                                alert(result.message)                        
                            }
                        }
                    }
                });
            } else {
                self.edit.email = false;
            }
        },
        change_password: function() {
            var self = this;
            if (self.password.current=='') {
                alert("Current password field empty");
                return false;   
            }
            if (self.password.new=='') {
                alert("New password field empty");
                return false;   
            }        
            if (self.password.repeat=='') {
                alert("Repeat password field empty");
                return false;   
            }
            if (self.password.new != self.password.repeat) {
                alert(str_passwords_do_not_match);
                return false;
            }
            $.ajax({
                type: 'POST',
                url: path+"user/changepassword.json",
                data: "old="+encodeURIComponent(self.password.current)+"&new="+encodeURIComponent(self.password.new),
                dataType: 'json',
                success: function(result) {
                    if (result.success!=undefined) {
                        if (result.success) {
                            self.edit.password = false;
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
}).mount('#app');

$.ajax({ url: path+"user/gettimezones.json", dataType: 'json', async: true, success: function(result) {
    profileApp.timezones = result;
}});

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
        if (result.success) {
            profileApp.user['apikey_'+type] = result[type+'_apikey'];
            $('#modalNewApikey').modal('hide');
        }
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

// Colour scheme toggle (dark / light)
var current_colormode = localStorage.getItem('colormode') || 'dark';
$('#mode-btn-' + current_colormode).addClass('active');

$('#mode-btn-dark, #mode-btn-light').click(function() {
    var mode = $(this).attr('id').replace('mode-btn-', '');
    $('html').removeClass('color-mode-light');
    if (mode === 'light') {
        $('html').addClass('color-mode-light');
    }
    localStorage.setItem('colormode', mode);
    $('#mode-btn-dark, #mode-btn-light').removeClass('active');
    $(this).addClass('active');
    current_colormode = mode;

    console.log("Color mode set to " + mode);
});
