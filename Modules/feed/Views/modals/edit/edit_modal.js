
var edit_feed = new Vue({
    el: '#feedEditModal',
    data: {
        hidden: true,
        loading: false,
        message: '',
        errors: {},
        localFeeds: {},
        feedsOriginal: {},
        unitOther: {},
        units: typeof feed_units !== 'undefined' ? feed_units : []
    },
    computed: {
        selectedFeedIds: function() {
            var selected = feedApp.selectedFeeds;
            return Object.keys(selected).filter(function(id) {
                return selected[id];
            });
        },
        selectedFeeds: function() {
            var localFeeds = this.localFeeds;
            return this.selectedFeedIds
                .map(function(id) { return localFeeds[id]; })
                .filter(Boolean);
        }
    },
    methods: {
        clearErrors: function(feedid) {
            if (typeof feedid !== 'undefined') {
                this.$set(this.errors, feedid, '');
            } else {
                this.errors = {};
            }
        },
        onUnitChange: function(feed, event) {
            var val = event.target.value;
            if (val === '_other') {
                Vue.set(this.unitOther, feed.id, true);
            } else {
                Vue.set(this.unitOther, feed.id, false);
                feed.unit = val;
            }
        },
        saveAll: function() {
            this.clearErrors();
            this.sendFields();
        },
        /**
         * POST all changed fields from selectedFeeds in a single request.
         */
        sendFields: function() {
            var self = this;
            var feeds = [];

            this.selectedFeeds.forEach(function(feed) {
                var original = self.feedsOriginal[feed.id];
                if (!original) return;

                var changed = {};
                if (self.selectedFeedIds.length === 1 && feed.name !== original.name) changed.name = feed.name;
                if (feed.tag !== original.tag) changed.tag = feed.tag;
                if (feed.unit !== original.unit) changed.unit = feed.unit;
                var publicVal = feed.public ? 1 : 0;
                if (publicVal !== (original.public ? 1 : 0)) changed.public = publicVal;

                if (Object.keys(changed).length === 0) {
                    self.$set(self.errors, feed.id, _('Nothing changed'));
                    return;
                }
                changed.id = feed.id;
                feeds.push(changed);
            });

            if (feeds.length === 0) {
                this.message = _('Nothing changed');
                return;
            }

            this.loading = true;
            this.message = '';

            $.post(path + 'feed/set-multiple.json', {feeds: JSON.stringify(feeds)})
            .done(function(response) {
                if (response.success) {
                    self.message = _('Saved');
                }
                Object.keys(response.results).forEach(function(feedid) {
                    var result = response.results[feedid];
                    self.$set(self.errors, feedid, result.message);
                    if (result.success) {
                        // Update snapshot so subsequent saves in the same session work correctly
                        var local = self.localFeeds[feedid];
                        if (local) {
                            self.feedsOriginal[feedid] = {
                                name: local.name,
                                tag: local.tag,
                                unit: local.unit,
                                public: local.public
                            };
                        }
                    }
                });
                update_feed_list();
            })
            .fail(function() {
                self.message = _('Save failed');
            })
            .always(function() {
                self.loading = false;
            });
        },
        closeModal: function() {
            // localFeeds is modal-private, so just discard it — feedApp.feeds is untouched.
            this.hidden = true;
            this.localFeeds = {};
            this.errors = {};
            this.message = '';
            document.removeEventListener('keydown', this.escape);
        },
        openModal: function() {
            var self = this;
            var newLocal = {};
            var newOriginal = {};
            var newUnitOther = {};
            // Snapshot current values from feedApp into the modal's own copies.
            // The template binds to localFeeds, so polls to feedApp.feeds cannot
            // overwrite what the user is typing.
            this.selectedFeedIds.forEach(function(id) {
                var feed = feedApp.feeds[id];
                if (!feed) return;
                var copy = { id: feed.id, name: feed.name, tag: feed.tag, unit: feed.unit, public: feed.public*1 };
                newLocal[id] = copy;
                newOriginal[id] = { name: feed.name, tag: feed.tag, unit: feed.unit, public: feed.public*1 };
                var inList = self.units.some(function(u) { return u.short === feed.unit; });
                newUnitOther[id] = !inList && feed.unit !== '';
            });
            this.localFeeds = newLocal;
            this.feedsOriginal = newOriginal;
            this.unitOther = newUnitOther;
            this.errors = {};
            this.message = '';
            this.hidden = false;
            document.addEventListener('keydown', this.escape);
        },
        escape: function(event) {
            if (event.key === 'Escape') {
                this.closeModal();
            }
        }
    }
});

// Keep backward-compatible entry point used by feedApp.editFeeds()
function openEditFeedModal() {
    edit_feed.openModal();
}