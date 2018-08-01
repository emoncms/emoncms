<?php

/*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

    global $path;
    $v=1;

    $languages = get_available_languages();
    $languages_name = languagecode_to_name($languages);
    //languages order by language name
    $languages_new = array();
    foreach ($languages_name as $key=>$lang){
       $languages_new[$key]=$languages[$key];
    }
    $languages= array_values($languages_new);
    $languages_name= array_values($languages_name);


function languagecode_to_name($langs) {
    static $lang_names = null;
    if ($lang_names === null) {
        $json_data = file_get_contents(__DIR__.'/language_country.json');
        $lang_names = json_decode($json_data, true);
    }
    foreach ($langs as $key=>$val){
      $lang[$key]=$lang_names[$val];
    }
   asort($lang);
   return $lang;
}

?>

<script type="text/javascript" src="<?php echo $path; ?>Modules/user/profile/md5.js?v=<?php echo $v; ?>"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/qrcode.js?v=<?php echo $v; ?>"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/clipboard.js?v=<?php echo $v; ?>"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/user/user.js?v=<?php echo $v; ?>"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/listjs/list.js?v=<?php echo $v; ?>"></script>

<div class="row-fluid">
    <div class="span4">
        <h3><?php echo _('My account'); ?></h3>

        <div id="account">
            <div class="account-item">
                <span class="muted"><?php echo _('User ID'); ?></span><br><span class="userid"></span>
            </div>

            <div class="account-item">
                <span class="muted"><?php echo _('Username'); ?></span>
                <span id="username-view"><br><span class="username"></span> <a id="edit-username" style="float:right"><?php echo _('Edit'); ?></a></span>
                <div id="edit-username-form" class="input-append" style="display:none">
                    <input class="span2" type="text" style="width:150px">
                    <button class="btn" type="button"><?php echo _('Save'); ?></button>
                </div>
                <div id="change-username-error" class="alert alert-error" style="display:none; width:170px"></div>
            </div>
            <div class="account-item">
                <span class="muted"><?php echo _('Email'); ?></span>
                <span id="email-view"><br><span class="email"></span> <a id="edit-email" style="float:right"><?php echo _('Edit'); ?></a></span>
                <div id="edit-email-form" class="input-append" style="display:none">
                    <input class="span2" type="text" style="width:150px">
                    <button class="btn" type="button"><?php echo _('Save'); ?></button>
                </div>
                <div id="change-email-error" class="alert alert-error" style="display:none; width:170px"></div>
            </div>

            <div class="account-item">
                <a id="changedetails"><?php echo _('Change Password'); ?></a>
            </div>

        </div>

        <div id="change-password-form" style="display:none">
            <div class="account-item">
                <span class="muted"><?php echo _('Current password'); ?></span>
                <br><input id="oldpassword" type="password" />
            </div>
            <div class="account-item">
                <span class="muted"><?php echo _('New password'); ?></span>
                <br><input id="newpassword" type="password" />
            </div>
            <div class="account-item">
                <span class="muted"><?php echo _('Repeat new password'); ?></span>
                <br><input id="repeatnewpassword" type="password" />
            </div>
            <div id="change-password-error" class="alert alert-error" style="display:none; width:170px"></div>
            <input id="change-password-submit" type="submit" class="btn btn-primary" value="<?php echo _('Save'); ?>" />
            <input id="change-password-cancel" type="submit" class="btn" value="<?php echo _('Cancel'); ?>" />
        </div>

        <br>
        <div id="account">
          <div class="account-item">
              <span class="muted"><?php echo _('Write API Key'); ?></span> <button style="float:right" class="btn btn-small" id="copyapiwritebtn"><?php echo _('Copy'); ?></button>
              <span class="writeapikey" id="copyapiwrite"></span>
          </div>
          <div class="account-item">
              <span class="muted"><?php echo _('Read API Key'); ?></span> <button style="float:right" class="btn btn-small" id="copyapireadbtn"><?php echo _('Copy'); ?></button>
              <span class="readapikey" id="copyapiread"></span>
              <span id="msg"></span>
          </div>
        </div>
	<br>
        <div class="account-item">
            <button class="btn btn-danger" id="deleteall">Delete my account</button>
        </div>

    </div>
    <div class="span8">
        <h3><?php echo _('My Profile'); ?></h3>
        <div id="table"></div>

        <div id="beta-section" class="well hidden">
            <h4><?php echo _('Beta Features'); ?>:</h4>
            <form id="beta-form" class="form-horizontal" style="margin-bottom:.2em">
            
                <!-- start option toggle -->
                <div class="control-group">
                    <label class="control-label"><?php echo _('Device Module Beta'); ?></label>
                    <div class="controls" data-prop="deviceModule">
                        <div class="options btn-group" data-toggle="buttons-radio">
                            <button type="button" autocomplete="off" class="btn" data-toggle="button" data-value="true">On</button>
                            <button type="button" autocomplete="off" class="btn active" data-toggle="button" data-value="false">Off</button>
                        </div>
                    </div>
                </div>
                <!-- end option toggle -->

                <button class="btn" data-saved-text="<?php echo _('Saved') ?>" data-error-text="<?php echo _('Error') ?>" data-loading-text="<?php echo _('Saving...') ?>">Save</button>
                <span style="padding:1em" class="text-info" id="beta-errors"></span>
            </form>
            <form id="delete-opts" action="<?php echo $path.'user/beta.json'?>" class="pull-right" style="margin-top:-2em">
                <input type="hidden" name="_method" value="DELETE">
                <button class="btn"><?php echo _('Reset') ?></button>
            </form>
        </div>

        <h3><?php echo _('Mobile app'); ?></h3>
        <div class="account-item">
            <table>
            <tr>
              <td style="width:192px">
                <p><?php echo _('Scan QR code from the iOS or Android app to connect:');?></p>
                <div id="qr_apikey"></div>
                <p style="padding-top:10px"><?php echo _('Or using a barcode scanner scan to view MyElectric graph');?></p>
              </td>
              <td style="padding-left:20px">
                <div><a href="https://itunes.apple.com/us/app/emoncms/id1169483587?ls=1&mt=8"><img alt="Download on the App Store" src="<?php echo $path; ?>Modules/user/images/appstore.png" /></a></div>
                <br/>
	              <div><a href="https://play.google.com/store/apps/details?id=org.emoncms.myapps"><img alt="Get it on Google Play" src="<?php echo $path; ?>Modules/user/images/en-play-badge.png" /></a></div>
	            </td>
	          </tr>
	          </table>
        </div>    
         
    </div>
</div>

<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="false">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"><?php echo _('WARNING deleting an account is permanent'); ?></h3>
    </div>
    <div class="modal-body">
        <div class="delete-account-s1">
        <p><?php echo _('Are you sure you want to delete your account?'); ?></p>
        </div>

        <div class="delete-account-s2" style="display:none">
        <p><b>Your account has been successfully deleted.</b></p>
        </div>
        
        <pre id="deleteall-output"></pre>
        
        <div class="delete-account-s1">
            <p>Confirm password to delete:<br>
            <input id="delete-account-password" type="password" /></p>
        </div>
    </div>
    <div class="modal-footer">
        <button id="canceldelete" class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="confirmdelete" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
        <button id="logoutdelete" class="btn btn-primary" style="display:none"><?php echo _('Logout'); ?></button>
    </div>
</div>

<script>

    var path = "<?php echo $path; ?>";
    var lang = <?php echo json_encode($languages); ?>;
    var lang_name = <?php echo json_encode($languages_name); ?>;

    list.data = user.get();

    $(".writeapikey").html(list.data.apikey_write);
    $(".readapikey").html(list.data.apikey_read);

    //QR COde Generation
    var urlCleaned = window.location.href.replace("user/view" ,"");
    var qrcode = new QRCode(document.getElementById("qr_apikey"), {
        text: urlCleaned + "app?readkey=" + list.data.apikey_read  + "#myelectric",
        width: 192,
        height: 192,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    }); //Re-designed on-board QR generation using javascript

    // Need to add an are you sure modal before enabling this.
    // $("#newapikeyread").click(function(){user.newapikeyread()});
    // $("#newapikeywrite").click(function(){user.newapikeywrite()});

    // Clipboard code
    document.getElementById("copyapiwritebtn").addEventListener("click", function() {
      copyToClipboardMsg(document.getElementById("copyapiwrite"), "msg");
    });
    document.getElementById("copyapireadbtn").addEventListener("click", function() {
      copyToClipboardMsg(document.getElementById("copyapiread"), "msg");
    });

    var currentlanguage = list.data.language;

    list.fields = {
        'gravatar':{'title':"<?php echo _('Gravatar'); ?>", 'type':'gravatar'},
        'name':{'title':"<?php echo _('Name'); ?>", 'type':'text'},
        'location':{'title':"<?php echo _('Location'); ?>", 'type':'text'},
        'bio':{'title':"<?php echo _('Bio'); ?>", 'type':'text'},
        'timezone':{'title':"<?php echo _('Timezone'); ?>", 'type':'timezone'},
        'language':{'title':"<?php echo _('Language'); ?>", 'type':'language', 'options':lang, 'label':lang_name},
        'startingpage':{'title':"<?php echo _('Starting page'); ?>", 'type':'text'}
    }

    $.ajax({ url: path+"user/gettimezones.json", dataType: 'json', async: true, success: function(result) {
        list.timezones = result;
    }});

    list.init();

    $("#table").bind("onSave", function(e){
        user.set(list.data);

        // refresh the page if the language has been changed.
        if (list.data.language!=currentlanguage) window.location.href = path+"user/view";
    });

    //------------------------------------------------------
    // Username
    //------------------------------------------------------
    $(".userid").html(list.data['id']);
    $(".username").html(list.data['username']);
    $("#input-username").val(list.data['username']);

    $("#edit-username").click(function(){
        $("#username-view").hide();
        $("#edit-username-form").show();
        $("#edit-username-form input").val(list.data.username);
    });

    $("#edit-username-form button").click(function(){

        var username = $("#edit-username-form input").val();

        if (username!=list.data.username)
        {
            $.ajax({
                url: path+"user/changeusername.json",
                data: "&username="+username,
                dataType: 'json',
                success: function(result)
                {
                    if (result.success)
                    {
                        $("#username-view").show();
                        $("#edit-username-form").hide();
                        list.data.username = username;
                        $(".username").html(list.data.username);
                        $("#change-username-error").hide();
                    }
                    else
                    {
                        $("#change-username-error").html(result.message).show();
                    }
                }
            });
        }
        else
        {
            $("#username-view").show();
            $("#edit-username-form").hide();
            $("#change-username-error").hide();
        }
    });

    //------------------------------------------------------
    // Email
    //------------------------------------------------------
    $(".email").html(list.data['email']);
    $("#input-email").val(list.data['email']);

    $("#edit-email").click(function(){
        $("#email-view").hide();
        $("#edit-email-form").show();
        $("#edit-email-form input").val(list.data.email);
    });

    $("#edit-email-form button").click(function(){

        var email = $("#edit-email-form input").val();

        if (email!=list.data.email)
        {
            $.ajax({
                url: path+"user/changeemail.json",
                data: "&email="+encodeURIComponent(email),
                dataType: 'json',
                success: function(result)
                {
                    if (result.success)
                    {
                        $("#email-view").show();
                        $("#edit-email-form").hide();
                        list.data.email = email;
                        $(".email").html(list.data.email);
                        $("#change-email-error").hide();
                    }
                    else
                    {
                        $("#change-email-error").html(result.message).show();
                    }
                }
            });
        }
        else
        {
            $("#email-view").show();
            $("#edit-email-form").hide();
            $("#change-email-error").hide();
        }
    });

    //------------------------------------------------------
    // Password
    //------------------------------------------------------
    $("#changedetails").click(function(){
        $("#changedetails").hide();
        $("#change-password-form").show();
    });

    $("#change-password-submit").click(function(){

        var oldpassword = $("#oldpassword").val();
        var newpassword = $("#newpassword").val();
        var repeatnewpassword = $("#repeatnewpassword").val();

        if (newpassword != repeatnewpassword)
        {
            $("#change-password-error").html("<?php echo _('Passwords do not match'); ?>").show();
        }
        else
        {
            $.ajax({
                type: 'POST',
                url: path+"user/changepassword.json",
                data: "old="+encodeURIComponent(oldpassword)+"&new="+encodeURIComponent(newpassword),
                dataType: 'json',
                success: function(result)
                {
                    if (result.success)
                    {
                        $("#oldpassword").val('');
                        $("#newpassword").val('');
                        $("#repeatnewpassword").val('');
                        $("#change-password-error").hide();

                        $("#change-password-form").hide();
                        $("#changedetails").show();
                    }
                    else
                    {
                        $("#change-password-error").html(result.message).show();
                    }
                }
            });
        }
    });

    $("#change-password-cancel").click(function(){
        $("#oldpassword").val('');
        $("#newpassword").val('');
        $("#repeatnewpassword").val('');
        $("#change-password-error").hide();

        $("#change-password-form").hide();
        $("#changedetails").show();
    });
    
    
    $("#deleteall").click(function(){
        $('#myModal').modal('show');
        
        $.ajax({type:"POST",url: path+"user/deleteall.json", data: "mode=dryrun", dataType: 'text', success: function(result){
            $("#deleteall-output").html(result);
        }});
    });

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

    /**
     * save user preferences to device
     * currently only saving opt in choices for beta feature releases
     */
    $(function(){
        // show the buttons if the user is able to join the beta trial
        // highlight the 'Off' button if no value is set
        $optInSection = $('#beta-section')
        $.get(path+'user/beta.json')
        .done(function(data){
            if (data.success) {
                //show options if applicable
                $optInSection.removeClass('hidden')
                setOptInButtonStates(data.optin)
            }
        })
        function setOptInButtonStates(optIn){
            // get the optIn options
            optIn = typeof optIn == 'string' ? JSON.parse(optIn) : optIn
            // create empty object if no preference saved
            optIn = optIn || {}
            // default to false
            $optInSection.find('.controls').each(function(n,elem){
                let prop = $(elem).data('prop')
                optIn[prop] = optIn[prop] || false
            })
            // set the buttons for Device Module
            $betaButtons = $optInSection.find('.options button')
            $.each($betaButtons, function(n,elem){
                let $button = $(elem)
                $button.removeClass('active')
                let prop = $button.parents('.controls').data('prop')
                if (optIn.hasOwnProperty(prop) && elem.dataset.value == optIn[prop].toString()) {
                    $(elem).addClass('active')
                }
            })
        }
        // delete all saved options
        $('#delete-opts').submit(function(event){
            event.preventDefault();
            $.post(this.action, $(this).serialize())
            .done(function(data){
                if (data && data.success) {
                    setOptInButtonStates()
                    $('#beta-errors').text(data.message)
                }
            })
        })
        // send user preference to controller via ajax
        // display status & progress to user
        $('#beta-form').submit(function(event){
            event.preventDefault()

            let url = path+"user/beta.json"
            let states = ['ready','saved','error']
            let state = 0

            $form = $(event.target)
            $btn = $('[data-loading-text]')
            $msg = $('#beta-errors')

            $btn.button('loading') // set the loading text 

            // ajax promise functions
            success = function(data,textStatus,xhr) {
                // ajax success
                // display error if returned value is not desired
                if(data && !data && !data.success) {
                    error(xhr, 'not successful', data.message)
                } else {
                    state = 1
                    $btn.button('saved').removeClass('btn-primary').addClass('btn-success')
                    $msg.text('') // clear the error messages
                }
            }
            error = function(xhr,textStatus,errorThrown) {
                // ajax error or called by success
                // display error to user
                state = 2
                $btn.removeClass('btn-primary').addClass('btn-danger').button('error')
                $msg.text(errorThrown)
            }
            always = function(){
                // reset form state after ajax call
                timeout = state == 2 ? 4000 : 1300
                setTimeout(function(){
                    $btn.button('reset').removeClass('btn-danger btn-success')
                    state = 0
                },timeout)
            }

            data = $form.serialize()
            optIn = {}
            $optInSection.find('.controls').each(function(n,elem){
                let prop = $(elem).data('prop')
                optIn[prop] = $(elem).find('.btn.active').data('value') == true
            })
            data = $.extend({}, data, {optIn:optIn})
            $.post(url, data)
            .done(success)
            .fail(error)
            .always(always)

            return false;
        })
        
    })

</script>
