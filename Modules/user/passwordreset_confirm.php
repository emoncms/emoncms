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
<style>
  .main { max-width: 340px; margin: 0 auto; padding-top: 40px; }
  .main .form-group { margin-bottom: 12px; }
  .main input[type=password] { width: 100%; }
</style>

<div class="main">
    <h3>Choose a new password</h3>

<?php if (!$key_valid) { ?>
    <div class="alert alert-danger"><?php echo tr("This password reset link is invalid or has expired, please request a new one"); ?></div>
<?php } else { ?>
    <div id="reset-form">
        <div class="form-group">
            <label>New password
                <input id="reset-password" type="password" autocomplete="new-password" />
            </label>
        </div>
        <div class="form-group">
            <label>Confirm new password
                <input id="reset-password2" type="password" autocomplete="new-password" />
            </label>
        </div>
        <button id="reset-submit" class="btn btn-primary" type="button">Set new password</button>
    </div>

    <div id="reset-message" class="pt-2"></div>
<?php } ?>

    <p class="pt-2"><a href="<?php echo $path; ?>user/login">Back to login</a></p>
</div>

<?php if ($key_valid) { ?>
<script>
var path = "<?php echo $path; ?>";
var reset_key = <?php echo json_encode($key); ?>;

$("#reset-submit").on("click", function () {
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
                $("#reset-message").html("<div class='alert alert-success'>" + data.message + "</div>");
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
