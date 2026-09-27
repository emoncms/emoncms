<?php
defined('EMONCMS_EXEC') or die('Restricted access');
global $path;
load_js("Lib/js/vue.global.prod-3.5.22.min.js");
load_css("Modules/admin/users/userlist_view.css");

?>

<div class="page-header">
    <h3><?php echo tr("Users"); ?></h3>
</div>

<div id="userlist-app" class="panel-page" v-cloak>

    <!-- Users card -->
    <div class="panel">

        <!-- Card header -->
        <div class="panel-header panel-header-static user-header">
            <span class="panel-accent"></span>
            <span class="panel-name"><?php echo tr("Users"); ?></span>
            <span class="panel-badge" :title="searchq ? '<?php echo tr('Matching users'); ?>' : ''">{{ searchq ? users.length + ' / ' + numberOfUsers : numberOfUsers }}</span>
            <input class="form-control user-search" type="search" v-model="searchKey" @input="searchSoon" @keyup.enter="search" placeholder="<?php echo tr('Search users'); ?>" aria-label="<?php echo tr('Search users'); ?>" />
            <button class="btn btn-default btn-sm" @click="openAddUserModal">
                <span class="svg-icon-plus"></span> <?php echo tr("Add new user"); ?>
            </button>
        </div>

        <!-- Pagination (top) -->
        <div class="panel-controls" v-if="numberOfPages > 1">
            <div class="pagination-bar">
                <a href="#" v-for="p in numberOfPages" :key="p" :class="{ active: p === currentPage }" @click.prevent="goToPage(p)">{{ p }}</a>
            </div>
        </div>

        <!-- User table -->
        <div class="panel-table">
        <table>
            <colgroup>
                <col style="width:60px">
                <col>
                <col>
                <col style="width:110px">
                <col style="width:90px">
                <col style="width:80px">
            </colgroup>
            <thead>
                <tr>
                    <th v-for="col in sortColumns" :key="col.key" class="user-sort" :class="{ 'is-sorted': orderby === col.key }" :aria-sort="orderby === col.key ? order : 'none'" @click="sortBy(col.key)">
                        {{ col.label }}<span class="user-sort-arrow">{{ orderby === col.key ? (order === 'ascending' ? '▲' : '▼') : '' }}</span>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id">
                    <td class="col-secondary">{{ user.id }}</td>
                    <td>
                        <div class="user-cell">
                            <span class="user-avatar" :class="tagClass(avatarColour(user.username))">{{ initials(user.username) }}</span>
                            <span class="col-primary text-truncate">{{ user.username }}</span>
                            <span v-if="user.admin" class="badge px-2 bg-danger-subtle text-danger-emphasis"><?php echo tr("Admin"); ?></span>
                        </div>
                    </td>
                    <td class="col-secondary text-truncate">{{ user.email }}</td>
                    <td>
                        <span v-if="user.email_verified" class="badge px-2 bg-success-subtle text-success-emphasis" title="<?php echo tr('Email verified'); ?>"><?php echo tr("Verified"); ?></span>
                        <span v-else class="badge px-2 bg-secondary-subtle text-secondary-emphasis"><?php echo tr("Unverified"); ?></span>
                    </td>
                    <td><span class="badge px-2" :class="tagClass(user.feeds > 0 ? 'info' : 'secondary')">{{ user.feeds }}</span></td>
                    <td class="text-end"><a class="btn btn-default btn-sm" :href="'../admin/setuser?id=' + user.id"><?php echo tr('View'); ?></a></td>
                </tr>
            </tbody>
        </table>
        </div>

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
                sortColumns: [
                    { key: 'id', label: <?php echo json_encode(tr("Id")); ?> },
                    { key: 'username', label: <?php echo json_encode(tr("Username")); ?> },
                    { key: 'email', label: <?php echo json_encode(tr("Email")); ?> },
                    { key: 'email_verified', label: <?php echo json_encode(tr("Verified")); ?> },
                    { key: 'feeds', label: <?php echo json_encode(tr("Feeds")); ?> }
                ],
                searchTimer: null,
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
            // Up to two letters: first letters of the first two words, or the first two characters
            initials: function (name) {
                var words = String(name || '?').split(/[\s._-]+/).filter(Boolean);
                var letters = words.length > 1 ? words[0][0] + words[1][0] : String(name || '?').slice(0, 2);
                return letters.toUpperCase();
            },
            // Same colour for the same username, one of six
            avatarColour: function (name) {
                var colours = ['info', 'success', 'warning', 'purple', 'danger', 'orange'];
                var hash = 0;
                name = String(name || '');
                for (var i = 0; i < name.length; i++) hash = (hash * 31 + name.charCodeAt(i)) | 0;
                return colours[Math.abs(hash) % colours.length];
            },
            // Pastel tag classes for a Bootstrap colour name
            tagClass: function (colour) {
                return 'bg-' + colour + '-subtle text-' + colour + '-emphasis';
            },
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

            // Same column reverses the order, a new column starts ascending
            sortBy: function (key) {
                if (this.orderby === key) {
                    this.order = this.order === 'ascending' ? 'descending' : 'ascending';
                } else {
                    this.orderby = key;
                    this.order = 'ascending';
                }
                this.fetchUsers();
            },

            // Search after a pause in typing
            searchSoon: function () {
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(this.search, 300);
            },

            search: function () {
                clearTimeout(this.searchTimer);
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