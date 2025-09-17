/**
 * uses moment.js to format to local time 
 * @param int time unix epoc time
 * @param string format moment.js date formatting options
 * @see date format options - https://momentjs.com/docs/#/displaying/
 */
function format_time(time,format){
    if(!Number.isInteger(time)) return time;
    format = format || 'YYYY-MM-DD';
    formatted_date = moment.unix(time).utc().format(format);
    return formatted_date;
}
/**
 * uses moment.js to display relative time from input time 
 * @param int time unix epoc time
 * @see docs - https://momentjs.com/docs/#/displaying/fromnow
 */
function time_since(time){
    if(!Number.isInteger(time)) return time;
    formatted_date = moment.unix(time).utc().fromNow();
    return formatted_date;
}

/**
 * wrapper for gettext like string replace function
 */
function _(str) {
    return translate(str);
}
/**
 * emulate the php gettext function for replacing php strings in js
 */
function translate(property) {
    _strings = typeof translations === 'undefined' ? getTranslations() : translations;
    if (_strings.hasOwnProperty(property)) {
        return _strings[property];
    } else {
        return property;
    }
}

var inactive_input_timeout = 3600; // seconds of inactivity before input is considered inactive
var devices = {};
var inputs = {};
var nodes = {};
var local_cache_key = 'input_nodes_display';
var nodes_display = {};
// clear cookie value if not in correct format
if (Array.isArray(nodes_display)) nodes_display = {};
var selected_inputs = {};
var selected_device = false;

if (DEVICE_MODULE) {
    var device_templates = {};
    $.getJSON(path + "device/template/listshort.json").done( function(response){
        device_templates = response;
        update();
    })
} else {
    setTimeout(update, 100);
}

var updater;
function updaterStop(){
    return clearInterval(updater);
}
function updaterStart(func, interval){
    updater = updaterStop();
    if (interval > 0) updater = setInterval(func, interval);
}
updaterStart(update, 5000);

var app = new Vue({
    el: "#app",
    data: {
        devices: {},
        col: {
            B: 40,  // select
            A: 200, // name
            G: 200, // description
            H: 200, // processList
            F: 50,  // schedule
            E: 100, // time
            D: 100, // value     
            C: 50,  // config       
        },
        col_h: {
            E: 'auto',
            H: 'auto'
        },
        selected: [],
        collapsed: [],
        paused: false,
        device_module: DEVICE_MODULE === true,
        scrolled: false,
        loaded: false,
        local_cache_key: 'input_nodes_display',
        input_creation_disabled: false // new property
    },
    computed: {
        total_inputs: function() {
            return this.inputs.length;
        },
        total_devices: function() {
            return Object.keys(this.devices).length
        },
        inputs: function() {
            let inputs = [];
            Object.keys(this.devices).forEach(function(nodeid){
                let device = this.devices[nodeid]
                if (device) {
                    device.inputs.forEach(function(input){
                        inputs.push(input);
                    })
                }
            })
            return inputs;
        },
        selectMode: function() {
            return this.selected.length > 0
        }
    },
    watch: {
        // stop updaing the list when form overlay showing
        paused: function(newVal) {
            if (newVal === true) {
                updaterStop()
            } else {
                update()
                updaterStart(update, 5000)
            }
        },
        collapsed: function(newVal) {
            // cache state in cookie
            if(!this.firstLoad) {
                // docCookies.setItem(this.local_cache_key, JSON.stringify(newVal));
            } else {
                this.firstLoad = false;
            }
        }
    },
    methods: {
        toggleCollapse: function(event, nodeid) {
            let index = this.collapsed.indexOf(nodeid);

            if(Array.isArray(this.collapsed)) {
                if (index === -1) {
                    this.collapsed.push(nodeid)
                } else {
                    this.collapsed.splice(index, 1)
                }
            } else {
                this.collapsed = [nodeid]
            }

        },
        toggleSelected: function(event, inputid) {
            if (event.target.tagName === 'A') {
                // allow links to be clicked
            } else {
                if (event.target.tagName !== 'INPUT' && !this.selectMode) {
                    event.stopPropagation();
                    event.preventDefault();
                    return false;
                }
            }
            let index = this.selected.indexOf(inputid);
            if (index === -1) {
                this.selected.push(inputid)
            } else {
                this.selected.splice(index,1)
            }
        },
        isSelected: function(inputid) {
            return this.selected.indexOf(inputid) > -1
        },
        isCollapsed: function(nodeid) {
            return this.collapsed.indexOf(nodeid) > -1
        },
        showInputConfigure: function(inputid) {
            var input = getInput(this.devices, inputid);
            showInputConfigure(input)
        },
        device_configure: function(device) {
            if(DEVICE_MODULE) {
                device_configure(device);
            } else {
                alert(_("Please install the device module to enable this feature"));
            }
        },
        show_device_key: function(device) {
            var devicekey = _("Please install the device module to enable this feature");
            if(DEVICE_MODULE) {
                devicekey = device.devicekey;
                if (devicekey === "") devicekey = _("No device key created");
            }
            alert(devicekey)
        },
        create_device: function(device) {
            if(typeof device_templates !== 'undefined') {
                device_dialog.loadConfig(device_templates)
            }
        },
        oldestDeviceInput: function(device) {
            var oldest = false;
            device.inputs.forEach(function(input) {
                if (!oldest || !oldest.time || input.time < oldest.time) {
                    oldest = input;
                }
            })
            return oldest;
        },
        handleScroll: function () {
            window.clearTimeout(this.timeout);
            let self = this;
            this.timeout = window.setTimeout(function(){
                self.scrolled = window.scrollY > 45;
            }, 20)
        },
        getDeviceInputIds: function (device) {
            // return array of ids from array of inputs
            return find(device.inputs, 'id')
        },
        isFullySelected: function(device) {
            // return true if all device inputs are selected, else false
            var inputids = this.getDeviceInputIds(device)
            var totalSelectedInputs = array_intersect(inputids, this.selected).length
            return device.inputs.length > 0 && totalSelectedInputs === device.inputs.length;
        },
        getDeviceSelectedInputids: function(device) {
            // return array of the selected device's inputids
            var inputids = this.getDeviceInputIds(device)
            return array_intersect(inputids, this.selected)
        },
        // select all if not all already selected, else clear selection
        selectAllDeviceInputs: function (device) {
            let inputids = this.getDeviceInputIds(device)
            let selectedInputids = this.getDeviceSelectedInputids(device)
            
            if (selectedInputids.length === device.inputs.length) {
                // all already selected, unselect all
                for(i in inputids) {
                    let inputid = inputids[i]
                    let index = this.selected.indexOf(inputid)
                    if (index > -1) {
                        // remove from selection array
                        this.selected.splice(index, 1)
                    }
                }
            } else {
                // select all if not all selected
                for(i in inputids) {
                    let inputid = inputids[i]
                    if (this.selected.indexOf(inputid) === -1) {
                        this.selected.push(inputid)
                    }
                }
            }
        },
        enableInputCreation: function() {
            var self = this;
            $.get(path + "input/enable.json").done(function(response) {
                if (response === true || response.success === true) {
                    self.input_creation_disabled = false;
                }
            });
        },
        disableInputCreation: function() {
            var self = this;
            if (confirm("Are you sure you want to disable further input creation? New inputs and devices will not appear automatically until re-enabled. This can be useful if spurious inputs are being created.")) {
                $.get(path + "input/disable.json").done(function(response) {
                    if (response === true || response.success === true) {
                        self.input_creation_disabled = true;
                    }
                });
            }
        }
    },
    created () {
        window.addEventListener('scroll', this.handleScroll);
        // load list collapsed state from previous visit
        this.firstLoad = true;
        /*if(docCookies.hasItem(this.local_cache_key)) {
            var cached_state = JSON.parse(docCookies.getItem(this.local_cache_key))
            if(Array.isArray(cached_state)) {
                this.collapsed = cached_state
            } else {
                this.collapsed = []
            }
        }*/
    },
    destroyed () {
        window.removeEventListener('scroll', this.handleScroll);
    }
});



var controls = new Vue({
    el: '#input-controls',
    data: {
        timeout: null,
        overlayControlsOveride: false,

        // used for clean feature
        show_clean: false,
        inactive_unconfigured_inputs: 0,
        inactive_unconfigured_devices: 0

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
        collapsed: function () {
            return app.collapsed
        },
        collapse_title: function () {
            var title = ''
            if (this.collapsed.length < this.total_devices) {
                title += _('Collapse');
            } else {
                title += _('Expand');
            }
            return title;
        },
        checkbox_icon: function () {
            var icon = '#icon-checkbox-'
            if (this.selected.length < this.total_devices) {
                icon += 'unchecked'
            } else {
                icon += 'checked'
            }
            return icon;
        },
        selectMode: function() {
            return app.selectMode;
        },
        scrolled: function() {
            return app.scrolled;
        },
        overlayControls: function () {
            return this.overlayControlsOveride || this.selectMode && this.scrolled
        }
    },
    methods: {
        selectAll: function() {
            if(app.selected.length < this.total_inputs) {
                let ids = [];
                app.inputs.forEach(function(input){
                    ids.push(input.id)
                })
                app.selected = ids
            } else {
                app.selected = [];
            }
        },
        collapseAll: function() {
            if(app.collapsed.length < app.total_devices) {
                app.collapsed = Object.keys(app.devices)
            } else {
                app.collapsed = [];
            }
        },
        open_delete: function(event) {
            delete_input.openModal(event)
        },
        open_edit: function(event) {
            edit_input.openModal(event)
        },
        showInputConfigure: function(inputid) {
            if (inputs[inputid] !== undefined) {
                showInputConfigure(inputs[inputid]);
            } else {
                alert(_("Input not found"));
            }
        },
        clean_unused: function() {

            const inputText = this.inactive_unconfigured_inputs === 1 ? "input" : "inputs";
            const deviceText = this.inactive_unconfigured_devices === 1 ? "device" : "devices";
            
            let msg = `Are you sure you want to remove ${this.inactive_unconfigured_inputs} inactive and unconfigured ${inputText}`;
            
            if (this.inactive_unconfigured_devices > 0) {
                msg += ` and ${this.inactive_unconfigured_devices} ${deviceText}?`;
            } else {
                msg += "?";
            }

            if (confirm(msg)) {
                // call device/clean.json result is plain text
                $.get(path+"device/clean.json?active="+inactive_input_timeout).done( function(response) {
                    alert(response);
                });

            }

        }
    },
    watch: {
        selectMode: function(newVal, oldVal) {
            if (oldVal && !newVal && this.scrolled) {
                this.overlayControlsOveride = true;
            } else {
                this.overlayControlsOveride = false;
            }
            return newVal;
        },
        scrolled: function(newVal, oldVal) {
            if (!newVal) this.overlayControlsOveride = false;
        }
    }
});




var delete_input = new Vue({
    el: '#inputDeleteModal',
    data: {
        hidden: true,
        buttonLabel: _('Delete'),
        buttonClass: 'btn-primary',
        success: false
    },
    computed: {
        total_inputs: function() {
            return app.total_inputs
        },
        total_devices: function() {
            return app.total_devices
        },
        selected: function() {
            return app.selected
        },
        inputs: function() {
            return app.inputs
        },
        devices: function() {
            return app.devices
        }
    },
    methods: {
        confirm: function(event) {
            var vm = this;
            input.delete_multiple_async(this.selected)
            .done(function() {
                // if all device inputs deleted, then delete the device
                vm.buttonLabel = _('Deleted')
                vm.buttonClass = 'btn-success'
                vm.success = true
                // wait for user to read response, then update & close modal
                setTimeout(function() {
                    update().done(function() {
                        // remove empty devices
                        vm.removeEmptyDevices()
                        .then(function() {
                            // all empty devices removed
                            vm.closeModal()
                        })
                    })
                }, 1000)
            })
            .fail(function(xhr, type, error){
                vm.buttonLabel = _('Error')
                vm.buttonClass = 'btn-warning'
                vm.success = false
            })
        },
        removeEmptyDevices: function() {
            // remove any devices without inputs
            var def = $.Deferred()
            var empty_devices = []
            var deleted_counter = 0
            var remove_responses = []

            // make list of empty devices
            for (n in devices) {
                let device = devices[n]
                if(device.inputs.length === 0) {
                    empty_devices.push(device.id)
                }
            }
            if (empty_devices.length === 0) {
                def.reject('no empty devices');
            }
            // delete each empty device
            for (n in empty_devices) {
                let deviceid = empty_devices[n]
                let ajax
                if(typeof device2 !== 'undefined') {
                    // use new async ajax call from device module js
                    ajax = device2.remove(deviceid)
                } else {
                    // remove this once device module change are in master
                    ajax = $.getJSON(path+"device/delete.json", "id="+deviceid)
                }
                ajax.done(function(remove_response) {
                    remove_response.deviceid = deviceid
                    remove_responses.push(remove_response)
                    deleted_counter ++
                    // on last loop resolve the promise
                    if (deleted_counter >= empty_devices.length) {
                        def.resolve(remove_responses)
                    }
                })
            }
            // return promise
            return def.promise()
        },
        getInputName: function(inputid) {
            var input = getInput(app.devices, inputid);
            return input.name;
        },
        getInputNode: function(inputid) {
            var input = getInput(app.devices, inputid);
            return input.nodeid;
        },
        closeModal: function(event) {
            app.paused = false;
            // clear selection if succesfully deleted
            if (this.success) {
                app.selected = []
            }
            this.hidden = true
            this.errors = {}
            this.message = ''
            // remove ESC keypress event
            document.removeEventListener('keydown', this.escape)
        },
        openModal: function(event) {
            this.hidden = false
            this.errors = {}
            this.message = ''
            this.buttonLabel = _('Delete'),
            this.buttonClass = 'btn-primary'
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


/**
 * get the key/property. only returns the last found.
 * @param {*} newValue 
 * @param {*} oldValue 
 * @returns {String|Boolean} the changed property name or false
 */
function hasChanged(newValue, oldValue){
    let changed = false;
    let properties = Object.keys(newValue)
    properties.forEach(function(key) {
        if (newValue[key] !== oldValue[key]) {
            // value changed
            changed = key
        }
    })
    return changed
}


/**
 * Clones a variable, creates a new variable as a copy of original
 * @param {*} original variable to clone
 * @returns {*} the new variable
 */
function clone(original) {
    var str = JSON.stringify(original)
    if(str) {
        return JSON.parse(str);
    } else {
        return false;
    }
}

/**
 * overwrite an object's properties by subsequent objects' properties
 * @param {*} arguments object1, object2..
 * @return new object
 */
var extend = function () {
    // Create a new object
    var extended = {};
    // Merge the object into the extended object
    var merge = function (obj) {
        for (var prop in obj) {
            if (obj.hasOwnProperty(prop)) {
                // Push each value from `obj` into `extended`
                extended[prop] = obj[prop];
            }
        }
    };
    // Loop through each object and conduct a merge
    for (var i = 0; i < arguments.length; i++) {
        merge(arguments[i]);
    }
    return extended;
};

/**
 * search all devices for input that matches the inputid
 * @param {Object} devices data returned from api 
 * @param {Number} inputid id of input to find as integer
 * @param {Boolean} returnIndex if true, returns [nodeid, index]
 * @return {(Object|Boolean|Number)} single input or device index if found, else false
 */
function getInput(devices, inputid, returnIndex) {
    let found = false
    // vuejs data objects includes setter and getter functions. remove them:
    for(nodeid in clone(devices)) {
        let device = devices[nodeid]
        device.inputs.forEach(function(input, index) {
            if (input.id === inputid) {
                if (!returnIndex) {
                    found = input
                } else {
                    found = [nodeid, index]
                }
            }
        })
    }
    return found
}



// ---------------------------------------------------------------------------------------------
// Fetch device and input lists
// ---------------------------------------------------------------------------------------------
var firstLoad = true;
function update(){
    // Join and include device data
    if (DEVICE_MODULE) {
        var def = $.Deferred()
        $.ajax({ url: path+"device/list.json", dataType: 'json', async: true, success: function(result) {
        
            if (result.message!=undefined && result.message=="Username or password empty") {
                window.location.href = "/";
                return false;
            }

            // Associative array of devices by nodeid
            devices = {};
            for (var z in result) {
                devices[String(result[z].nodeid)] = result[z]
                devices[String(result[z].nodeid)].inputs = []
            }
            update_inputs().done(function() {
                // inputs list done downloading
                def.resolve()
            })
        }});
        return def.promise()
    } else {
        // update_inputs returns jquery ajax promise
        devices = {};
        return update_inputs()
    }
}

function update_inputs() {
    var requestTime = (new Date()).getTime();
    return $.ajax({ url: path+"input/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
        if( typeof app !== 'undefined') app.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
        
        if (data.message!=undefined && data.message=="Username or password empty") {
            window.location.href = "/";
            return false;
        }
        
        // Associative array of inputs by id
        inputs = {};
        for (var z in data) inputs[data[z].id] = data[z];
        
        // Clear existing device inputs
        for (var nodeid in devices) {
            devices[nodeid].inputs = [];
            devices[nodeid].active_or_configured = false;
        }

        // Assign inputs to devices
        for (var z in inputs) {
            let nodeid = String(inputs[z].nodeid);
            
            // Device does not exist which means this is likely a new system or that the device was deleted
            // There needs to be a corresponding device for every node and so the system needs to recreate the device here
            if (devices[nodeid]==undefined) {
            
                devices[nodeid] = {
                    id: false,
                    userid: inputs[z].userid,
                    nodeid: nodeid,
                    name: nodeid,
                    description: "",
                    type: "",
                    devicekey: false,
                    time:false,
                    inputs: []
                }
                
                if (DEVICE_MODULE) {
                    // Device creation
                    $.ajax({ url: path+"device/create.json?nodeid="+nodeid, dataType: 'json', async: false, success: function(result) {
                        if (result.success!=undefined) {
                            //alert("There was an error creating device: nodeid="+nodeid+" message="+result.message); 
                            console.error("There was an error creating device: nodeid="+nodeid+" message="+result.message);
                        } else {
                            devices[nodeid].id = result;
                            devices[nodeid].devicekey = "";
                        }
                    }});
                }
            }
            if (typeof nodes_display[nodeid] === 'undefined') {
                nodes_display[nodeid] = true;
            }
            // expand if only one feed available or state locally cached in cookie
            if (firstLoad && Object.keys(devices).length > 1 && Object.keys(nodes_display).length === 0) {
                delete nodes_display[nodeid];
            }
            // cache state in cookie
            if(Array.isArray(devices[nodeid].inputs)) {
                devices[nodeid].inputs.push(inputs[z]);
            } else {
                devices[nodeid].inputs = [inputs[z]];
            }
        }
        if(firstLoad) {
            $('#input-loader').hide();
            prepare_device_clean();
            firstLoad = false;
        }
        
        draw_devices();
        noProcessNotification(devices);
    }});
}

/** show a message to the user if no processes have been added */
function noProcessNotification(devices){
    let processList = [],  message = '';
    
    for (d in devices) {
        for (i in devices[d].inputs) {
            if(devices[d].inputs[i].processList.length>0) {
                processList.push(devices[d].inputs[i].processList);
            }
        }
    }
    if(processList.length<1 && Object.keys(devices).length > 0){
        message = '<div class="alert pull-right">%s <i class="icon-arrow-down" style="opacity: .7;"></i></div>'.replace('%s',_("Configure your device here"))
    }
    $('#noprocesses').html(message);
}

// ---------------------------------------------------------------------------------------------
// Draw devices
// ---------------------------------------------------------------------------------------------
function draw_devices() {

    max_name_length = 0
    max_description_length = 0
    max_time_length = 0
    max_value_length = 0
    
    // This part works out the column widths based on string length of largest entry in column
    for (var nodeid in devices) {
    
        if (devices[nodeid].nodeid.length > max_name_length) max_name_length = devices[nodeid].nodeid.length;
        if (devices[nodeid].description.length > max_description_length) max_description_length = devices[nodeid].description.length;
        
        var oldest_time = 0;
        var device_oldest_input = null;

        for (var z in devices[nodeid].inputs) {
            var input = devices[nodeid].inputs[z];

            var last_update = list_format_last_update(input.time);
            if (input.time != null) {
                if (last_update > oldest_time) {
                    oldest_time = last_update;
                    device_oldest_input = input;
                }
            }
            
            input.processlistHtml = process_vue ? process_vue.drawPreview(input.processList, input) : '';
            
            var fv = list_format_updated_obj(input.time);
            input.time_color = fv.color
            input.time_value = fv.value
            
            var value_str = list_format_value(input.value);
            input.value_str = value_str
            
            if (input.name.length>max_name_length) max_name_length = input.name.length;
            if (input.description.length>max_description_length) max_description_length = input.description.length;
            if (String(fv.value).length>max_time_length) max_time_length = String(fv.value).length;
            if (String(value_str).length>max_value_length) max_value_length = String(value_str).length;  
        }
        if (device_oldest_input == null) {
            var fv = list_format_updated_obj(0);
        } else {
            var fv = list_format_updated_obj(device_oldest_input.time);
        }
        devices[nodeid].time_color = fv.color
        devices[nodeid].time_value = fv.value
    }

    // Conversion of string length to px width
    app.col = {
        B: 40,                                   // select
        A: ((max_name_length * 8) + 30),         // name          +30
        G: ((max_description_length * 8) + 70),  // description   +70
        H: 200,                                  // processList
        F: 50,                                   // schedule
        E: ((max_time_length * 8) + 40),         // time          +40 (needs to accomodate weeks/days/hours/minutes/s)
        D: ((max_value_length * 8) + 17),        // value         +17
        C: 50                                    // config        
    };
    
    // Column height used when hiding columns
    app.col_h.H = 'auto'
    app.col_h.E = 'auto'
    
    resize_view();

    Vue.set(app, 'devices', clone(devices));
    app.loaded = true;
    app.devicesOriginal = clone(devices);
}

/**
 * Analyzes devices to identify inactive and unconfigured inputs for cleanup
 * 
 * This function determines which inputs can be safely removed by:
 * 1. For devices with configured inputs: finds inputs that are unconfigured AND inactive
 *    relative to the most recent configured input activity
 * 2. For devices with NO configured inputs: finds inputs that are inactive relative to current time
 * 
 * Updates the controls UI to show cleanup options when inactive inputs are found.
 * 
 * @global {Object} devices - Global devices object containing all device data
 * @global {number} inactive_input_timeout - Timeout threshold in seconds for considering inputs inactive
 * @global {Object} controls - Vue component for UI controls
 */
function prepare_device_clean() {
    let inactive_unconfigured_inputs = 0;
    let inactive_unconfigured_devices = 0;
    
    // Convert current time to Unix timestamp (seconds)
    const now = (new Date()).getTime() * 0.001;

    // Iterate through each device to analyze its inputs
    for (const nodeid in devices) {
        const device = devices[nodeid];
        
        // Separate inputs into configured (has process list) and unconfigured
        const configuredInputs = device.inputs.filter(input => input.processList.length > 0);
        const unconfiguredInputs = device.inputs.filter(input => input.processList.length === 0);
        const hasConfiguredInputs = configuredInputs.length > 0;
        
        let deviceInactiveInputs = 0;

        if (hasConfiguredInputs) {
            // Strategy for devices WITH configured inputs:
            // Use the most recent configured input as the baseline for activity
            const mostRecentTime = Math.max(...configuredInputs.map(input => input.time || 0));
            
            // Only count unconfigured inputs that are inactive relative to configured input activity
            // This prevents removing inputs that might still be actively sending data
            deviceInactiveInputs = unconfiguredInputs.filter(input => 
                (mostRecentTime - input.time) > inactive_input_timeout
            ).length;
        } else {
            // Strategy for devices with NO configured inputs:
            // Use current time as baseline since there's no configured activity to reference
            deviceInactiveInputs = device.inputs.filter(input => 
                (now - input.time) > inactive_input_timeout
            ).length;
            
            // If ALL inputs in the device are inactive, mark the entire device for cleanup
            if (deviceInactiveInputs === device.inputs.length) {
                inactive_unconfigured_devices++;
            }
        }

        // Accumulate total count across all devices
        inactive_unconfigured_inputs += deviceInactiveInputs;
    }

    // Show cleanup UI option if any inactive inputs were found
    if (inactive_unconfigured_inputs > 0) {
        controls.show_clean = true;
        controls.inactive_unconfigured_inputs = inactive_unconfigured_inputs;
        controls.inactive_unconfigured_devices = inactive_unconfigured_devices;
    }
}

function resize_view() {
    // Hide columns
    var col_max = JSON.parse(JSON.stringify(app.col));
    var rowWidth = $("#app").width() - 0;       // Originally 0 offset removed here
    hidden = {}
    keys = Object.keys(app.col).sort();
    
    var columnsWidth = 0
    for (k in keys) {
        let key = keys[k]
        columnsWidth += col_max[key];
        hidden[key] = columnsWidth > rowWidth;
    }
    
    for (var key in hidden) {
        if (hidden[key]) {
            app.col[key] = 0;
            app.col_h[key] = 0;
        } else {
            app.col[key] = col_max[key]
            app.col_h[key] = 'auto';
        }
    }

    // show tooltip with device key on click 
    $('#table [data-toggle="tooltip"]').tooltip({
        trigger: 'manual',
        container: 'body',
        placement: 'left',
        title: function(){
           return $(this).data('device-key');
        }
    }).hover(
        // show "fake" title (tooltip-title) on hover
        function(e){
            let $btn = $(this);
            let title = !$btn.data('shown') ? $btn.data('tooltip-title') : '';
            $btn.attr('title', title);
        }
    )
}


$(function(){
    // create new device
    $('#device-new').on("click", function() {
        if(typeof device_templates !== 'undefined') {
            device_dialog.loadConfig(device_templates);
        }
    });
        
    $("#table").on("click",".device-schedule",function(e) {
        e.stopPropagation();
        var node = $(this).parents('.node-info').first().data("node");
        window.location = path+"demandshaper?node="+node;
    });

    $("#table").on("click",".device-configure",function(e) {
        e.stopPropagation();
        // Get device of clicked node
        node = $(this).parents('.node-info').first().data("node");
        var device = devices[node];
        
        if (DEVICE_MODULE) {
            device_dialog.loadConfig(device_templates, device);
        } else {
            alert(_("Please install the device module to enable this feature"));
        }
    });
}) // end of jquery document ready

/**
 * perform the delete action for the currently selected device
 * deletes any inputs that are associated
 * 
 * @requires {Object} jQuery - uses jquery's ajax and promise functions
 * @requires {Object} device - the group of device related functions
 * @requires {Object} device_dialog - the group of device_dialog related functions
 * @returns void
 */
function device_delete() {
    var inputIds = [];
    for (var i in device_dialog.device.inputs) {
        var inputId = device_dialog.device.inputs[i].id;
        inputIds.push(parseInt(inputId));
    }
    // respond/resolve with successful response when all actions done
    var def = $.Deferred()

    if (inputIds.length > 0) {
        input.delete_multiple_async(inputIds)
        .done(function(){
            def.resolve(device.remove(device_dialog.device.id))
        })
        .fail(function(xhr,type,error){
            def.reject([type,error].implode(', '))
        })
    } else {
        def.resolve(device.remove(device_dialog.device.id))
    }
    // call this function once above ajax requests complete
    def.done(function(response) {
        if (response.hasOwnProperty('success') && response.success === false) {
            // api action failed
            if(response.message) {
                alert(response.message)
            }
        } else {
            // success
            $('#device-config-modal .modal-footer [data-dismiss="modal"]').click()
            update().done(function(update_response){
                console.log(update_response)
            })
        }
    }).fail(function(message){
        console.error(message)
    })
}

function device_configure(device){
    if (DEVICE_MODULE) {
        device_dialog.loadConfig(device_templates, device);
    } else {
        alert(_("Please install the device module to enable this feature"));
    }
};

// return fields object that matches the api requirements
function serializeInputData(form){
    let formData = $(form).serializeArray();
    let fields = {};
    let inputid = void 0;
    for(field in formData) {
        if(formData[field].name=='description') fields.description = formData[field].value;
        if(formData[field].name=='name' && formData[field].value.length>0) fields.name = formData[field].value;
        if(formData[field].name=='inputid') inputid = formData[field].value;
    }
    let data = new URLSearchParams({'inputid':inputid});
    if(Object.keys(fields).length>0) data.set('fields',JSON.stringify(fields));
    return data.toString();
}

;var showStatus = (function(){
    var container = document.getElementById('input-edit-status');
    const INFO='text-info',
          ERROR='text-error',
          SUCCESS='text-success';
    var allowed = [INFO,ERROR,SUCCESS];

    function switchClass(classNames,elem){
        elem = typeof elem != 'undefined' && elem instanceof Element ? elem : container;
        classNames = Array.isArray(classNames) ? classNames : [classNames];
        for(a in allowed) {
            elem.classList.remove(allowed[a]);
        }
        for(c in classNames){
            if(allowed.indexOf(classNames[c])>-1) elem.classList.add(classNames[c]);
            if(classNames[c]=='text-error'){
                setTimeout(function(){
                    parent = elem.parentNode;
                    if(parent) parent.removeChild(elem);
                },3000);
            }
        }
    }
    function emptyContainer(){
        $('#inputEditModal .status').remove();
    }
    function addText(text,className,id){
        let elem = document.querySelector('.status[data-inputid="'+id+'"]') || document.createElement('h5');
        elem.innerText = text;
        elem.style.margin = 0;
        elem.style.marginRight = '1em';
        elem.style.float = 'left';
        elem.classList.add('status');
        elem.setAttribute('data-inputid',id);
        domBox = container.appendChild(elem);
        switchClass(className,domBox);
    }
    function showInfo(text,id){
        addText(text,INFO,id);
    }
    function showError(text,id){
        addText(text,ERROR,id);
    }
    function showSuccess(text,id){
        addText(text,SUCCESS,id);
    }
    return{
        clear: emptyContainer,
        info: showInfo,
        error: showError,
        success: showSuccess
    };
}());

function getInputFormData(form){
    let dataString = serializeInputData(form),
        data = new URLSearchParams(dataString),
        inputid = data.get('inputid'),
        fields = JSON.parse(data.get('fields'));

    return {
        originalData: form.dataset.originalData,
        dataString: dataString,
        data: data,
        inputid: inputid,
        fields: fields
    };
}

function submitSingleInputForm(e){
    e.preventDefault();
    let form = e.target, 
        $loader = $(e.target).parents('.modal').find('#inputEdit-loader');

    showStatus.clear();
    $loader.show();
    fd = getInputFormData(form);

    showStatus.info(_('Saving')+'...',fd.inputid);

    // if current form data differs from original data saved in data-originalData
    if(fd.fields && fd.originalData != fd.dataString){
        input.set(fd.inputid, fd.fields, true)
            .done(function(response){
                if(!response.success){
                    showStatus.error('Problem saving data. Error 221',fd.inputid);
                }else{
                    showStatus.success('Saved input #'+fd.inputid,fd.inputid);
                    // reset the 'original data' marker
                    form.dataset.originalData = serializeInputData(form);
                }
                $loader.hide();
            });
    } else {
        $loader.hide();
        showStatus.error('No changes to save',fd.inputid);
    }
}

function submitAllInputForms(e){
    e.preventDefault();
    var forms = $(e.target).parents('.modal').find('form');
    $loader = $(e.target).parents('.modal').find('#inputEdit-loader');

    var messages = [];
    forms.each(function(){
        if (!this.checkValidity()) return false;
        $loader.show();
        let fd = getInputFormData(this);
        showStatus.info(_('Saving')+'...',fd.inputid);
        if(fd.fields && fd.originalData != fd.dataString){
            $.when(input.set(fd.inputid, fd.fields, true))
                .then(function(response) {
                    if(!response || !response.success) {
                        showStatus.error(response.message || '',fd.inputid)
                    } else {
                        showStatus.success('Saved input #'+fd.inputid,fd.inputid)
                    }
                })
                .then(function(){
                    $loader.hide()
                });
        } else {
            $loader.hide();
            showStatus.error('No changes to save',fd.inputid);
        }
    })
}

// $("#inputDelete-confirm").off('click').on('click', function(){
//     var ids = [];
//     for (var inputid in selected_inputs) {
//         if (selected_inputs[inputid]==true) ids.push(parseInt(inputid));
//     }
//     input.delete_multiple(ids);
//     update();
//     $('#inputDeleteModal').modal('hide');
// });

function showInputConfigure(input) {
    var i = input
    var contextid = i.id; // Current Input ID
    // Input name
    var newfeedname = i.name;
    var contextname = i.nodeid + ": " + i.name;
    if (i.description != "") { 
        newfeedname = i.description;
        contextname += " (" + i.description + ")";
    }
    var newfeedtag = i.nodeid;
    process_vue.load(0, contextid, i.processList, contextname, newfeedname, newfeedtag); // load configs
}

function save_processlist(input_id, encoded_process_list) {
    var result = input.set_process(input_id, encoded_process_list);
    if (!result.success) {
        alert('ERROR: Could not save processlist. '+result.message); 
        return false;
    } else {
        update();
        return true;
    }
}

// -------------------------------------------------------------------------------------------------------
// Interface responsive
//
// The following implements the showing and hiding of the device fields depending on the available width
// of the container and the width of the individual fields themselves. It implements a level of responsivness
// that is one step more advanced than is possible using css alone.
// -------------------------------------------------------------------------------------------------------

// watchResize(onResize,50) // only call onResize() after delay (similar to debounce)

// debouncing causes odd rendering during resize - run this at all resize points...
$(window).on("window.resized",function() {
    draw_devices();
});



/**
 * get new array created from values in both arrays
 * @param {Array} arr1
 * @param {Array} arr2
 * @return {Array}
 */
function array_intersect(arr1, arr2) {
    return arr1.filter(function(value) {
        return arr2.indexOf(value) > -1
    })
}

/**
 * searches [arr] of objects and returns array of [prop] values
 * @param {Array} arr 
 * @param {String} prop 
 * @return {Array}
 */
function find(arr, prop) {
    return arr.map(function(a) { 
        return a[prop];
    });
}
$(function(){
    $(document).on('hide show', '#table', function(event){
        // cache state in cookie
        if(!firstLoad) {
            nodes_display[event.target.dataset.node] = event.type === 'show';
            //docCookies.setItem(local_cache_key, JSON.stringify(nodes_display));
            firstLoad = false;
        }
        console.log(event.target.dataset.node,nodes_display)
    })
})


// Check if input creation is disabled for the user
$.get(path + "input/isdisabled.json").done(function(response) {
    if (typeof app !== 'undefined') {
        app.input_creation_disabled = response === true || response.disabled === true;
    }
});
