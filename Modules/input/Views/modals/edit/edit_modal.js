
var edit_input = new Vue({
    el: '#inputEditModal',
    data: {
        hidden: true,
        loading: false,
        message: '',
        errors: {},
        timeouts: {}
    },
    computed: {
        total_inputs: function(){
            return app.total_inputs
        },
        total_devices: function(){
            return app.total_devices
        },
        selected: function() {
            return app.selected
        },
        inputs: function() {
            return app.inputs
        }
    },
    methods: {
        clearErrors: function(inputid) {
            if (typeof inputid !== 'undefined') {
                this.errors[inputid] = ''
            } else {
                this.errors = {}
            }
        },
        save: function(event) {
            var formData = [new FormData(event.target)]
            this.loading = true;
            this.message = "";
            this.clearErrors();
            var self = this
            // send formData to api
            this.send(formData)
            .done(function(response) {
                // show success message and close overlay
                self.message = _('Saved')
                for (inputid in response.messages) {
                    let indexes = getInput(app.devices, inputid, true)
                    let nodeid = indexes[0];
                    let inputIndex = indexes[1];
                    // update input's "original" value for subsequent updates
                    app.devicesOriginal[nodeid].inputs[inputIndex] = clone(app.devices[nodeid].inputs[inputIndex]);
                    edit_input.$set(self.errors, inputid, response.messages[inputid].message)
                    // self.timeouts[inputid] = window.setTimeout(function() {
                    //     self.clearErrors(inputid)
                    // }, 2000);
                    let cloned = clone(app.devices[nodeid].inputs[inputIndex]);
                    app.devicesOriginal[nodeid].inputs[inputIndex] = cloned;
                }
            })
            .fail(function(response) {
                // show errors
                if (typeof response !== 'string') {
                    for (inputid in response) {
                        // window.clearTimeout(self.timeouts[inputid])
                        edit_input.$set(self.errors, inputid, response[inputid].message)
                        self.timeouts[inputid] = window.setTimeout(function(){
                            self.clearErrors(inputid)
                        }, 2000)
                    }
                } else {
                    self.message = _(errors)
                }
            })
            .always(function() {
                // finished loading
                self.loading = false;
            })
        },
        saveAll: function(event) {
            // collect input data from all forms
            var formData = []
            var timeout;
            var forms = document.querySelectorAll('#inputEditModal .modal-body form')
            this.message = "";
            if (typeof forms !== 'undefined') {
                forms.forEach(function(form) {
                    formData.push(new FormData(form))
                })
            }
            // show loader
            this.loading = true;
            var self = this
            // send all formData to api
            this.send(formData)
            .done(function(response) {
                // show success message and close overlay
                self.message = _('Saved')
                self.errors[response.lastUpdated.inputid] = response.lastUpdated.message
                for(inputid in response.messages) {
                    let indexes = getInput(app.devices, inputid, true)
                    let nodeid = indexes[0];
                    let inputIndex = indexes[1];
                    // update input's "original" value for subsequent updates
                    app.devicesOriginal[nodeid].inputs[inputIndex] = clone(app.devices[nodeid].inputs[inputIndex]);
                }
            })
            .fail(function(errors){
                // show errors
                for (inputid in errors) {
                    self.errors[inputid] = errors[inputid].message
                    window.clearTimeout(self.timeouts[inputid])
                    self.timeouts[inputid] = window.setTimeout(function() {
                        self.clearErrors(inputid)
                    }, 2000)
                }
            })
            .progress(function(response) {
                // add message next to input
                // @todo: check why last update doesn't always get a chance to show messages
                window.clearTimeout(self.timeouts[response.inputid])
                self.timeouts[response.inputid] = window.setTimeout(function(){
                    self.clearErrors(response.inputid)
                }, 2000)
                self.errors[response.inputid] = response.message
            })
            .always(function(inputid) {
                // finished loading
                self.loading = false;
                window.setTimeout(function(){
                    self.clearErrors(inputid)
                }, 2000)
            })
        },
        /**
         * submit as many ajax requests as required to update input meta data
         * on last response from all the calls respond to the 
         * @param {Array.<FormData>} formData array of any submitted forms' FormData()
         */
        send: function(formData) {
            var def = $.Deferred();
            var self = this
            var errors = {}
            var total = formData.length;
            var messages = {};
            this.message = '';
            formData.forEach(function(form, index, array) {
                var inputid = form.get('id')
                self.inputs.forEach( function(input) {
                    if(input.id === inputid) {
                        // store any changed fields
                        let fields = {
                            description: form.get('description')
                        }
                        // if something changed submit data to api
                        let fieldsOriginal = getInput(app.devicesOriginal, inputid)
                        if(hasChanged(fields, fieldsOriginal) !== false) {
                            $.getJSON(path + 'input/set.json', {
                                inputid: inputid,
                                fields: JSON.stringify(fields)
                            })
                            .done(function(response) {
                                // notify calling function that entry has saved
                                if(response.message) {
                                    def.notify({
                                        inputid: inputid,
                                        message: response.message,
                                        success: true
                                    })
                                    messages[inputid] = {success: true, message: response.message}
                                }
                            })
                            .fail(function(xhr, type, error) {
                                errors[inputid] = {message: error}
                            })
                            .always(function() {
                                // once the last ajax call returns respond to calling function
                                if(index === array.length - 1) {
                                    if(Object.values(errors).length === array.length) {
                                        def.reject(errors)
                                    } else {
                                        lastUpdated = extend(arguments[0], {inputid: inputid})
                                        def.resolve({messages: messages, lastUpdated: lastUpdated})
                                    }
                                }
                            })
                        } else {
                            // nothing changed for input[inputid]
                            errors[inputid] = {message: _('Nothing changed')}
                            // notify calling function that nothing has changed
                            def.notify({
                                inputid: inputid,
                                message: _('Nothing changed'),
                                success: true
                            })
                            // update the status
                            messages[inputid] = {success: false, message: _('Nothing changed')}

                            if(index === array.length - 1) {
                                def.reject(errors)
                            }
                        }
                    }
                })
            })
            return def.promise();
        },
        closeModal: function(event) {
            this.hidden = true
            this.errors = {}
            this.message = ''
            app.paused = false;
            // remove ESC keypress event
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
            // listen for ESC keypress and close modal
            if (event.keyCode == 27) {
                if(typeof this.closeModal !== 'undefined') {
                    this.closeModal();
                }
            }
        }
    }
});