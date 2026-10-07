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

// The token is validated server side, both on this page load and again on
// redemption; it is echoed below into a script variable, so escape it rather
// than trusting the query string.
$key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');

// Set by the controller from User::passwordreset_key_is_valid()
$key_valid = !empty($key_valid);

?>
<?php
load_css("Modules/user/login_block.css");
?>

<div class="login-page" data-bs-theme="light">
    <div class="card login-card">
        <div class="login-head">
            <div class="login-brand">
                <img src="<?php echo $path; ?>Theme/emoncms-logo.svg" alt="" width="50" height="43">
                <span><b>emon</b>cms</span>
            </div>
            <p>Open-source energy visualisation</p>
        </div>

        <div class="card-body">
            <h4 class="login-title"><?php echo tr('Choose a new password'); ?></h4>

<?php if (!$key_valid) { ?>
            <div class="alert alert-danger"><?php echo tr("This password reset link is invalid or has expired, please request a new one"); ?></div>
<?php } else { ?>
            <form id="reset-form" onsubmit="return false;">
                <div class="login-field">
                    <label class="form-label" for="reset-password"><?php echo tr('New password'); ?></label>
                    <input class="form-control" id="reset-password" type="password" placeholder="<?php echo tr('Enter a new password'); ?>" autocomplete="new-password" />
                </div>
                <div class="login-field">
                    <label class="form-label" for="reset-password2"><?php echo tr('Confirm new password'); ?></label>
                    <input class="form-control" id="reset-password2" type="password" placeholder="<?php echo tr('Enter the new password again'); ?>" autocomplete="new-password" />
                </div>
                <div id="reset-message"></div>
                <button id="reset-submit" class="btn btn-primary login-btn" type="submit"><?php echo tr('Set new password'); ?></button>
            </form>
            <div id="reset-done"></div>
<?php } ?>

            <p class="login-switch"><a href="<?php echo $path; ?>user/login"><?php echo tr('Back to log in'); ?></a></p>
        </div>
    </div>
</div>

<?php if ($key_valid) { ?>
<script>
var path = "<?php echo $path; ?>";
var reset_key = <?php echo json_encode($key); ?>;

$("#reset-form").on("submit", function () {
    var password = $("#reset-password").val();
    var password2 = $("#reset-password2").val();

    $("#reset-message").html("");

    if (password.length < 4) {
        $("#reset-message").html("<div class='alert alert-danger'>Password must be at least 4 characters</div>");
        return;
    }
    if (password !== password2) {
        $("#reset-message").html("<div class='alert alert-danger'>Passwords do not match</div>");
        return;
    }

    $.ajax({
        type: "POST",
        url: path + "user/passwordreset-confirm.json",
        data: { key: reset_key, password: password },
        dataType: "json",
        success: function (data) {
            if (data && data.success) {
                // The token is spent: hide the form so it cannot be resubmitted
                $("#reset-form").hide();
                $("#reset-done").html("<div class='alert alert-success'>" + data.message + "</div>");
            } else {
                var msg = (data && data.message) ? data.message : "Password reset failed";
                $("#reset-message").html("<div class='alert alert-danger'>" + msg + "</div>");
            }
        },
        error: function () {
            $("#reset-message").html("<div class='alert alert-danger'>Password reset failed, please try again</div>");
        }
    });
});
</script>
<?php } ?>
