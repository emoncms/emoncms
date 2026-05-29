<?php
defined('EMONCMS_EXEC') or die('Restricted access');
global $path;
load_js("Lib/js/vue.global.prod-3.5.22.min.js");

?>
<style>
    [v-cloak] { display: none; }
    .userlist-controls {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .userlist-controls label {
        font-size: var(--font-sm);
        color: var(--text-secondary);
        margin-right: 0.25rem;
    }
    .pagination-bar {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
    }
    .pagination-bar a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 26px;
        padding: 0 6px;
        border: 1px solid var(--border-strong);
        border-radius: 0.25rem;
        font-size: var(--font-xs);
        color: var(--text-secondary);
        text-decoration: none;
        background: var(--bg-input);
        transition: background-color 0.15s, color 0.15s;
    }
    .pagination-bar a:hover { background: var(--bg-card-row-hover); color: var(--text-primary); }
    .pagination-bar a.active { background: var(--accent); color: #fff; border-color: var(--accent); }



    body {
        background-color: whitesmoke;
    }

</style>

<div id="userlist-app" v-cloak>

    <!-- Users card -->
    <div class="card mt-3">

        <!-- Card header -->
        <div class="card-header" style="cursor:default">
            <span class="card-accent"></span>
            <span class="card-name"><?php echo tr("Users"); ?></span>
            <span class="card-badge">{{ numberOfUsers }}</span>
            <button class="btn btn-sm" style="margin-left:auto" @click="openAddUserModal">
                <i class="icon icon-plus"></i> <?php echo tr("Add new user"); ?>
            </button>
        </div>

        <!-- Controls -->
        <div class="card-controls">
            <div class="userlist-controls">
                <div class="input-prepend">
                    <span class="add-on"><?php echo tr("Order by"); ?></span>
                    <select v-model="orderby" @change="fetchUsers">
                        <option value="id"><?php echo tr("Id"); ?></option>
                        <option value="username"><?php echo tr("Username"); ?></option>
                        <option value="email"><?php echo tr("Email"); ?></option>
                        <option value="email_verified"><?php echo tr("Email Verified"); ?></option>
                    </select>
                    <select v-model="order" @change="fetchUsers">
                        <option value="ascending"><?php echo tr("Ascending"); ?></option>
                        <option value="descending"><?php echo tr("Descending"); ?></option>
                    </select>
                </div>
                <div class="input-prepend input-append">
                    <span class="add-on"><?php echo tr("Search"); ?></span>
                    <input v-model="searchKey" type="text" @keyup.enter="search" style="width:180px" />
                    <button class="btn" @click="search"><?php echo tr("Search"); ?></button>
                </div>
            </div>
        </div>

        <!-- Pagination (top) -->
        <div class="card-controls" v-if="numberOfPages > 1">
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
                    <td class="col-secondary"><span v-if="user.email_verified" title="<?php echo tr('Email verified'); ?>" style="color:var(--success)"><i class="icon icon-check"></i></span><span v-else></span></td>
                    <td class="col-secondary">{{ user.feeds }}</td>
                    <td><a class="btn" :href="'../admin/setuser?id=' + user.id"><?php echo tr('View'); ?></a></td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination (bottom) -->
        <div class="card-controls" v-if="numberOfPages > 1">
            <div class="pagination-bar">
                <a href="#" v-for="p in numberOfPages" :key="p" :class="{ active: p === currentPage }" @click.prevent="goToPage(p)">{{ p }}</a>
            </div>
        </div>

    </div><!-- end .card -->

    <!-- Add new user modal -->
    <div id="addUserModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true" style="width:380px;margin-left:-190px;">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h3 id="addUserModalLabel"><?php echo tr("Add new user"); ?></h3>
        </div>
        <div class="modal-body">
            <p>
                <label><?php echo tr("Username"); ?></label>
                <input v-model="newUser.username" type="text" class="input-block-level" />
            </p>
            <p>
                <label><?php echo tr("Password"); ?></label>
                <input v-model="newUser.password" type="password" class="input-block-level" />
            </p>
            <p>
                <label><?php echo tr("Email"); ?></label>
                <input v-model="newUser.email" type="text" class="input-block-level" />
            </p>
            <div class="alert alert-danger" v-if="addUserError">{{ addUserError }}</div>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal"><?php echo tr('Close'); ?></button>
            <button class="btn btn-primary" @click="addUser"><?php echo tr('Add user'); ?></button>
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