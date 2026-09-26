<?php
defined('EMONCMS_EXEC') or die('Restricted access');
global $path;
load_js("Lib/js/vue.global.prod-3.5.22.min.js");
load_css("Modules/admin/users/userlist_view.css");

?>

<div class="page-header">
    <h3><?php echo tr("Users"); ?></h3>
</div>

<div id="userlist-app" v-cloak>

    <!-- Users card -->
    <div class="panel">

        <!-- Card header -->
        <div class="panel-header panel-header-static">
            <span class="panel-accent"></span>
            <span class="panel-name"><?php echo tr("Users"); ?></span>
            <span class="panel-badge">{{ numberOfUsers }}</span>
            <button class="btn btn-default btn-sm" @click="openAddUserModal">
                <span class="svg-icon-plus"></span> <?php echo tr("Add new user"); ?>
            </button>
        </div>

        <!-- Controls -->
        <div class="panel-controls">
            <div class="userlist-controls">
                <div class="input-group">
                    <span class="input-group-text"><?php echo tr("Order by"); ?></span>
                    <select class="form-select input-220" v-model="orderby" @change="fetchUsers">
                        <option value="id"><?php echo tr("Id"); ?></option>
                        <option value="username"><?php echo tr("Username"); ?></option>
                        <option value="email"><?php echo tr("Email"); ?></option>
                        <option value="email_verified"><?php echo tr("Email Verified"); ?></option>
                    </select>
                    <select class="form-select input-220" v-model="order" @change="fetchUsers">
                        <option value="ascending"><?php echo tr("Ascending"); ?></option>
                        <option value="descending"><?php echo tr("Descending"); ?></option>
                    </select>
                </div>
                <div class="input-group">
                    <span class="input-group-text"><?php echo tr("Search"); ?></span>
                    <input class="form-control" v-model="searchKey" type="text" @keyup.enter="search" style="width:194px" />
                    <button class="btn btn-default" @click="search"><?php echo tr("Search"); ?></button>
                </div>
            </div>
        </div>

        <!-- Pagination (top) -->
        <div class="panel-controls" v-if="numberOfPages > 1">
            <div class="pagination-bar">
                <a href="#" v-for="p in numberOfPages" :key="p" :class="{ active: p === currentPage }" @click.prevent="goToPage(p)">{{ p }}</a>
            </div>
        </div>

        <!-- User table -->
        <table>
            <colgroup>
                <col style="width:60px">
                <col>
                <col>
                <col>
                <col style="width:70px">
                <col style="width:80px">
            </colgroup>
            <thead>
                <tr>
                    <th><?php echo tr("Id"); ?></th>
                    <th><?php echo tr("Username"); ?></th>
                    <th><?php echo tr("Email"); ?></th>
                    <th><?php echo tr("Verified"); ?></th>
                    <th><?php echo tr("Feeds"); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id">
                    <td class="col-secondary">{{ user.id }}</td>
                    <td class="col-primary">{{ user.username }}</td>
                    <td class="col-secondary">{{ user.email }}</td>
                    <td class="col-secondary"><span v-if="user.email_verified" title="<?php echo tr('Email verified'); ?>" class="text-success"><span class="svg-icon-check"></span></span><span v-else></span></td>
                    <td class="col-secondary">{{ user.feeds }}</td>
                    <td><a class="btn btn-default" :href="'../admin/setuser?id=' + user.id"><?php echo tr('View'); ?></a></td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination (bottom) -->
        <div class="panel-controls" v-if="numberOfPages > 1">
            <div class="pagination-bar">
                <a href="#" v-for="p in numberOfPages" :key="p" :class="{ active: p === currentPage }" @click.prevent="goToPage(p)">{{ p }}</a>
            </div>
        </div>

    </div><!-- end .panel -->

    <!-- Add new user modal -->
    <div id="addUserModal" class="modal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true" style="--bs-modal-width:380px">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="addUserModalLabel" class="modal-title"><?php echo tr("Add new user"); ?></h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>
                        <label class="form-label"><?php echo tr("Username"); ?></label>
                        <input v-model="newUser.username" type="text" class="form-control" />
                    </p>
                    <p>
                        <label class="form-label"><?php echo tr("Password"); ?></label>
                        <input v-model="newUser.password" type="password" class="form-control" />
                    </p>
                    <p>
                        <label class="form-label"><?php echo tr("Email"); ?></label>
                        <input v-model="newUser.email" type="text" class="form-control" />
                    </p>
                    <div class="alert alert-danger" v-if="addUserError">{{ addUserError }}</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" data-bs-dismiss="modal"><?php echo tr('Close'); ?></button>
                    <button class="btn btn-primary" @click="addUser"><?php echo tr('Add user'); ?></button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    var USERS_PER_PAGE = 250;

    var app = Vue.createApp({
        data: function () {
            return {
                users: [],
                numberOfUsers: 0,
                currentPage: 1,
                orderby: 'id',
                order: 'ascending',
                searchKey: '',
                searchq: false,
                newUser: { username: '', password: '', email: '' },
                addUserError: ''
            };
        },

        computed: {
            numberOfPages: function () {
                return Math.ceil(this.numberOfUsers / USERS_PER_PAGE);
            }
        },

        mounted: function () {
            this.fetchNumberOfUsers();
            this.fetchUsers();
        },

        methods: {
            fetchNumberOfUsers: function () {
                var self = this;
                fetch(path + 'admin/numberofusers.json')
                    .then(function (r) { return r.text(); })
                    .then(function (data) { self.numberOfUsers = parseInt(data, 10) || 0; });
            },

            fetchUsers: function () {
                var self = this;
                var searchstr = self.searchq ? '&search=' + encodeURIComponent(self.searchq) : '';
                var url = path + 'admin/userlist.json'
                    + '?page=' + (self.currentPage - 1)
                    + '&perpage=' + USERS_PER_PAGE
                    + '&orderby=' + self.orderby
                    + '&order=' + self.order
                    + searchstr;
                fetch(url)
                    .then(function (r) { return r.json(); })
                    .then(function (data) { self.users = data || []; });
            },

            goToPage: function (p) {
                this.currentPage = p;
                this.fetchUsers();
            },

            search: function () {
                this.searchq = this.searchKey || false;
                this.currentPage = 1;
                this.fetchUsers();
            },

            openAddUserModal: function () {
                this.newUser = { username: '', password: '', email: '' };
                this.addUserError = '';
                $('#addUserModal').modal('show');
            },

            closeAddUserModal: function () {
                $('#addUserModal').modal('hide');
            },


            addUser: function () {
                var self = this;
                var user_timezone = 'UTC';
                if (typeof Intl !== 'undefined') {
                    user_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                }

                var body = new URLSearchParams({
                    username: encodeURIComponent(self.newUser.username),
                    password: encodeURIComponent(self.newUser.password),
                    email: encodeURIComponent(self.newUser.email),
                    timezone: encodeURIComponent(user_timezone)
                });

                fetch(path + 'user/register.json', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                })
                .then(function (r) { return r.json(); })
                .then(function (result) {
                    if (result.success === undefined) {
                        self.addUserError = result;
                    } else if (result.success) {
                        $('#addUserModal').modal('hide');
                        self.fetchNumberOfUsers();
                        self.fetchUsers();
                    } else {
                        self.addUserError = result.message;
                    }
                });
            }
        }
    });

    app.mount('#userlist-app');
}());
</script>