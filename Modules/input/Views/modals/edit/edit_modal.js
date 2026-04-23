var edit_input = new Vue({
    el: '#inputEditModal',
    data: {
        hidden: true,
        loading: false,
        message: '',
        errors: {}
    },
    computed: {
        selected: function() {
            return app.selected
        },
        inputs: function() {
            return app.inputs
        },
        selectedInputs: function() {
            var selected = this.selected;
            return this.inputs.filter(function(input) {
                return selected.indexOf(input.id) > -1;
            });
        }
    },
    methods: {
        clearErrors: function(inputid) {
            if (typeof inputid !== 'undefined') {
                this.$set(this.errors, inputid, '')
            } else {
                this.errors = {}
            }
        },
        saveAll: function() {
            this.clearErrors();
            this.sendDescriptions();
        },
        /**
         * POST all changed descriptions from selectedInputs in a single request.
         */
        sendDescriptions: function() {
            var self = this;
            var inputs = [];

            this.selectedInputs.forEach(function(input) {
                var original = getInput(app.devicesOriginal, input.id);
                if (!original || input.description === original.description) {
                    self.$set(self.errors, input.id, _('Nothing changed'));
                    return;
                }
                inputs.push({id: input.id, description: input.description});
            });

            if (inputs.length === 0) {
                this.message = _('Nothing changed');
                return;
            }

            this.loading = true;
            this.message = '';

            $.post(path + 'input/set-descriptions.json', {inputs: JSON.stringify(inputs)})
            .done(function(response) {
                if (response.success) {
                    self.message = _('Saved');
                }
                Object.keys(response.results).forEach(function(inputid) {
                    var result = response.results[inputid];
                    self.$set(self.errors, inputid, result.message);
                    if (result.success) {
                        var indexes = getInput(app.devices, inputid, true);
                        var nodeid = indexes[0];
                        var inputIndex = indexes[1];
                        app.devicesOriginal[nodeid].inputs[inputIndex] = clone(app.devices[nodeid].inputs[inputIndex]);
                    }
                });
            })
            .fail(function() {
                self.message = _('Save failed');
            })
            .always(function() {
                self.loading = false;
            });
        },
        closeModal: function(event) {
            this.hidden = true
            this.errors = {}
            this.message = ''
            // reset any unsaved description edits back to their original values
            this.selectedInputs.forEach(function(input) {
                var original = getInput(app.devicesOriginal, input.id);
                if (original) {
                    input.description = original.description;
                }
            });
            app.paused = false;
            document.removeEventListener('keydown', this.escape)
        },
        openModal: function(event) {
            this.hidden = false
            this.errors = {}
            this.message = ''
            app.paused = true
            document.addEventListener("keydown", this.escape);
        },
        escape: function(event) {
            if (event.key === 'Escape') {
                this.closeModal();
            }
        }
    }
});