<?php
defined('EMONCMS_EXEC') or die('Restricted access');
global $path;
?>
<style>
    .content-container {
        max-width: 980px;
    }
</style>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>

<div id="app">
    <h2><?php echo _("My Accounts"); ?></h2>

    <div class="input-prepend input-append" style="float:right">
        <button class="btn" id="open-add-user-modal"><i class="icon icon-plus"></i> <?php echo _("Add new user"); ?></button>
    </div>

    <p><?php echo _("Number of users:"); ?> {{accounts.length}}</p>
    <br>

    <div v-if="accounts.length==0" class="alert alert-warning"><?php echo _("Multi user management. Click on add new user to create a new user or to add an existing user."); ?></div>
    <table class="table table-striped" v-else>
        <tr>
            <th><?php echo _("Edit"); ?></th>
            <th><?php echo _("Id"); ?></th>
            <th><?php echo _("Username"); ?></th>
            <th><?php echo _("Email"); ?></th>
            <th><?php echo _("User Login"); ?></th>
            <th><?php echo _("Feeds"); ?></th>
            <th></th>
        </tr>
        <tr v-for="(user,index) in accounts">
            <td><a class="btn btn-info btn-sm" :href="path+'account/switch?userid='+user.id">view</a></td>
            <td>{{ user.id }}</td>
            <td>{{ user.username }}</td>
            <td>{{ user.email }}</td>
            <td @click="change_access(index)" style="cursor:pointer" title="Click to change access level">
                <span class="label label-inverse" v-if="user.access==0">Disabled</span>
                <span class="label label-warning" v-if="user.access==1">Read only</span>
                <span class="label label-success" v-if="user.access==2">Write access</span>
            </td>
            <td>{{ user.feeds }}</td>
            <td @click="unlink(index)" style="cursor:pointer"><i class="icon-trash"></i></td>
        </tr>
    </table>

    <div id="addNewUserModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="addNewUserModalLabel" aria-hidden="true" data-backdrop="static" style="width:300px">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="addNewUserModalLabel">Add new user</h3>
        </div>
        <div class="modal-body">

            <p>
                <lable>Username:</label><br>
                <input v-model="add_username" type="text" style="width:250px" />
            </p>
            <p>
                <lable>Password:</label><br>
                <input v-model="add_password" type="text" style="width:250px" />
            </p>
            <p>
                <lable>Email:</label><br>
                <input v-model="add_email" type="text" style="width:250px" />
            </p>
            <p>
                <lable>Timezone:</label><br>
                <input v-model="add_timezone" type="text" style="width:250px" />
            </p>

            <div class="alert alert-error" v-if="add_error" style="margin-bottom:0px">{{ add_error }}</div>

        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Close'); ?></button>
            <button class="btn btn-info"  @click="add_account"><?php echo _('Add user'); ?></button>
        </div>
    </div>
</div>

<script>
    // Vue app
    var app = new Vue({
        el: '#app',
        data: {
            accounts: [],
            user: {},
            add_username: "",
            add_password: "",
            add_email: "",
            add_timezone: "",
            add_error: false
        },
        mounted: function() {
            this.getAccounts();
            this.getUser();
        },
        methods: {
            getAccounts: function() {
                // using jquery ajax
                $.ajax({
                    url: path + "account/list.json",
                    dataType: 'json',
                    success: function(result) {
                        app.accounts = result;
                    }
                });
            },
            getUser: function() {
                // using jquery ajax
                $.ajax({
                    url: path + "user/get.json",
                    dataType: 'json',
                    success: function(result) {
                        app.user = result;
                        app.add_email = app.user.email;
                        app.add_timezone = app.user.timezone;
                    }
                });
            },
            add_account: function() {
                $.ajax({
                    type: "POST",
                    url: path + "account/add.json",
                    dataType: 'json',
                    data: {
                        username: encodeURIComponent(app.add_username),
                        password: encodeURIComponent(app.add_password),
                        email: encodeURIComponent(app.add_email),
                        timezone: encodeURIComponent(app.add_timezone)
                    },
                    success: function(result) {
                        if (result.success) {
                            app.getAccounts();
                            $('#addNewUserModal').modal('hide');
                        } else {
                            app.add_error = result.message;
                        }
                    }
                });
            },
            change_access: function(index) {
                var userid = app.accounts[index].id;
                var access = app.accounts[index].access;
                access ++;
                if (access > 2) access = 0;
                $.ajax({
                    type: "POST",
                    url: path + "account/setaccess.json",
                    dataType: 'json',
                    data: {
                        userid: userid,
                        access: access
                    },
                    success: function(result) {
                        app.accounts[index].access = access;
                    }
                });
            },
            unlink: function(index) {
                var userid = app.accounts[index].id;

                // ask for confirmation
                if (!confirm("Are you sure you want to unlink this user? Original user will not be deleted")) return;

                $.ajax({
                    type: "POST",
                    url: path + "account/unlink.json",
                    dataType: 'json',
                    data: {
                        userid: userid
                    },
                    success: function(result) {
                        app.getAccounts();
                    }
                });
            }
        },
        filters: {
            access: function(value) {
                if (value == 0) return "Disabled";
                if (value == 1) return "Read only";
                if (value == 2) return "Write access";
                return "Unknown";
            }
        
        }
    });

    $("#open-add-user-modal").click(function() {
        app.add_error = false;
        $('#addNewUserModal').modal('show');
    });
</script>