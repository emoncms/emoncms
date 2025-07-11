<?php
defined('EMONCMS_EXEC') or die('Restricted access');
global $path;
?>
<link rel="stylesheet" href="<?php echo $path ?>Modules/admin/static/admin_styles.css?v=1">
<style>
    .afeed {
        color: #00aa00;
        font-weight: bold;
    }
</style>

<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>

<div class="admin-container">
    <h2><?php echo tr("Users"); ?></h2>

    <p><?php echo tr("Number of users:"); ?> <span id="numberofusers"></span></p>

    <div class="pagination">
        <ul>
        </ul>
    </div>

    <div class="input-prepend">
        <span class="add-on"><?php echo tr("Order by"); ?></span>
        <select id="orderby" style="width:150px">
            <option value="id" selected><?php echo tr("Id"); ?></option>
            <option value="username"><?php echo tr("Username"); ?></option>
            <option value="email"><?php echo tr("Email"); ?></option>
            <option value="email_verified"><?php echo tr("Email Verified"); ?></option>
        </select>

        <select id="order" style="width:120px">
            <option value="ascending" selected><?php echo tr("Ascending"); ?></option>
            <option value="decending"><?php echo tr("Descending"); ?></option>
        </select>
    </div>

    <div class="input-prepend input-append" style="padding-left:20px">
        <span class="add-on"><?php echo tr("User search"); ?></span>
        <input id="user-search-key" type="text" />
        <button class="btn" id="user-search"><?php echo tr("Search"); ?></button>
    </div>

    <div class="input-prepend input-append" style="float:right">
        <button class="btn" id="open-add-user-modal"><i class="icon icon-plus"></i> <?php echo tr("Add new user"); ?></button>
    </div>

    <table class="table">
        <tr>
            <th><?php echo tr("Edit"); ?></th>
            <th><?php echo tr("Id"); ?></th>
            <th><?php echo tr("Username"); ?></th>
            <th><?php echo tr("Email"); ?></th>
            <th><?php echo tr("Feeds"); ?></th>
        </tr>
        <tbody id="users"></tbody>
    </table>

    <div class="pagination">
        <ul>
        </ul>
    </div>

    <!------------------------------------------------------------------------------------------------------------------------------------------------- -->
    <!-- FEED EXPORT                                                                                                                                   -->
    <!------------------------------------------------------------------------------------------------------------------------------------------------- -->
    <div id="addNewUserModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="addNewUserModalLabel" aria-hidden="true" data-backdrop="static" style="width:300px">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="addNewUserModalLabel">Add new user</h3>
        </div>
        <div class="modal-body">

            <p>
                <lable>Username:</label><br>
                    <input id="new-username" type="text" style="width:250px" />
            </p>
            <p>
                <lable>Password:</label><br>
                    <input id="new-password" type="text" style="width:250px" />
            </p>
            <p>
                <lable>Email:</label><br>
                    <input id="new-email" type="text" style="width:250px" />
            </p>

            <div class="alert alert-error" id="add-user-error" style="display:none"></div>

        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo tr('Close'); ?></button>
            <button class="btn btn-info" id="add-user"><?php echo tr('Add user'); ?></button>
        </div>
    </div>

</div>

<script>
    var users = {};

    var admin = {

        'numberofusers': function() {
            var result = 0;
            $.ajax({
                url: path + "admin/numberofusers.json",
                dataType: 'text',
                async: false,
                success: function(data) {
                    result = data;
                }
            });
            return result;
        },

        'userlist': function(page, perpage, orderby, order, searchq) {
            console.log("userlist: " + page + " " + perpage + " " + orderby + " " + searchq);
            var searchstr = "";
            if (searchq != false) searchstr = "&search=" + encodeURIComponent(searchq);
            var result = {};
            $.ajax({
                url: path + "admin/userlist.json?page=" + (page - 1) + "&perpage=" + perpage + "&orderby=" + orderby + "&order=" + order + searchstr,
                dataType: 'json',
                async: false,
                success: function(data) {
                    result = data;
                }
            });
            return result;
        }
    }

    // -------------------------------------------------------------------------------------------

    var number_of_users = admin.numberofusers();
    var users_per_page = 250;
    var number_of_pages = Math.ceil(number_of_users / users_per_page);
    var orderby = "id";
    var page = 1;
    var order = "ascending";
    var searchq = false;

    var out = "";
    for (var z = 0; z < number_of_pages; z++) {
        out += '<li><a class="pageselect" href="#">' + (z + 1) + '</a></li>';
    }
    $(".pagination").find("ul").html(out);
    $("#numberofusers").html(number_of_users);

    users = admin.userlist(page, 250, orderby, order, searchq);
    table_draw();

    $(".pagination").on("click", ".pageselect", function() {
        page = $(this).html();
        users = admin.userlist(page, 250, orderby, order, searchq);
        table_draw();
    });

    $("#orderby").change(function() {
        orderby = $(this).val();
        users = admin.userlist(page, 250, orderby, order, searchq);
        table_draw();
    });

    $("#order").change(function() {
        order = $(this).val();
        users = admin.userlist(page, 250, orderby, order, searchq);
        table_draw();
    });

    $("#user-search").click(function() {
        searchq = $("#user-search-key").val();
        users = admin.userlist(page, 250, orderby, order, searchq);
        table_draw();
    });

    function table_draw() {
        var out = "";
        for (var z in users) {

            var email_verified = users[z].email_verified * 1;

            if (email_verified) {
                out += "<tr style='background-color:rgba(0,255,0,0.2)'>";
            } else {
                out += "<tr>";
            }
            out += "<td><a class=\"btn btn-info btn-sm\" href='../admin/setuser?id=" + users[z].id + "'><?php echo tr('view'); ?></a></td>";
            out += "<td>" + users[z].id + "</td>";
            out += "<td>" + users[z].username + "</td>";
            out += "<td>" + users[z].email + "</td>";
            out += "<td>" + users[z].feeds + "</td>";
            out += "</tr>";
        }
        $("#users").html(out);
    }

    function printdate(timestamp) {
        var date = new Date();

        var date = new Date(timestamp);
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var year = date.getFullYear();
        var month = months[date.getMonth()];
        var day = date.getDate();

        var minutes = date.getMinutes();
        if (minutes < 10) minutes = "0" + minutes;

        var datestr = date.getHours() + ":" + minutes + " " + day + " " + month + " " + year;
        if (timestamp == 0) datestr = "";
        return datestr;
    };

    $("#open-add-user-modal").click(function() {
        $("#add-user-error").hide();
        $('#addNewUserModal').modal('show');
    });

    $("#add-user").click(function() {
        var username = $("#new-username").val();
        var password = $("#new-password").val();
        var email = $("#new-email").val();

        // Set user timezone automatically using current browser timezone
        var user_timezone = 'UTC';
        if (Intl != undefined) {
            user_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            console.log(user_timezone);
        }

        $.ajax({
            type: "POST",
            url: path + "user/register.json",
            data: {
                username: encodeURIComponent(username),
                password: encodeURIComponent(password),
                email: encodeURIComponent(email), 
                timezone: encodeURIComponent(user_timezone)
            },
            dataType: 'json',
            async: false,
            success: function(result) {
                if (result.success == undefined) {
                    $("#add-user-error").html(result);
                    $("#add-user-error").show();
                    return;
                } else {
                    if (result.success) {
                        $('#addNewUserModal').modal('hide');
                        users = admin.userlist(page, 250, orderby, order, searchq);
                        table_draw();
                        return;
                    } else {
                        $("#add-user-error").html(result.message);
                        $("#add-user-error").show();
                        return;
                    }
                }
            }
        });
    });
</script>