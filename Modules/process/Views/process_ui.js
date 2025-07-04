var ContextType = {
    INPUT: 0, // Input context
    VIRTUALFEED: 1, // Feed context
};

var ProcessArg = {
    VALUE: 0,
    INPUTID: 1,
    FEEDID: 2,
    NONE: 3,
    TEXT: 4,
    SCHEDULEID: 5
}

var argtypes = {
    0: { name: "Value", cssClass: 'label-important', title: '{longText}: {value}' },
    1: { name: "Input", cssClass: 'label-warning', title: '{longText}: ({input.nodeid}:{input.name}) {input.description}' },
    2: { name: "Feed", cssClass: 'label-info', title: '{longText}: {feed.tag}:{feed.name} ({feed.id})' },
    3: { name: "None", cssClass: 'label-important', title: '{longText}: {value}' },
    4: { name: "Text", cssClass: 'label-info', title: '{longText}: {value}' },
    5: { name: "Schedule", cssClass: 'label-warning', title: '{longText}: {schedule.name}' }
};

var process_vue = new Vue({
    el: '#process_vue',
    data: {

        context_type: 0,
        has_redis: 0, // Flag to indicate if Redis is available (1) or not (0)

        input_or_virtual_feed_id: '', // ID of the input or virtual feed
        input_or_virtual_feed_name: '', // Name of the input or virtual feed (used for modal title)
        new_feed_name: '', // Name for the new feed (if creating a new feed)
        new_feed_tag: '', // Tag for the new feed (if creating a new feed)

        args: [],
        inputs: [], // List of inputs
        inputs_by_id: {},
        inputs_by_node: {},
        schedules: {},

        selected_process: 'process__log_to_feed',

        processes_by_key: {},
        // Non input or virtual feed processes are filtered out
        // This is used for populating the process select dropdown
        context_only_processes_by_group: {}, 

        feeds_by_id: {},
        feeds_by_tag: {},

        // Holds process list for current input or feed
        process_list: [],

        // This array is used to keep track of selected processes in the UI
        // It is used for bulk actions like cut, copy, paste, and delete
        selected_processes: [],

        // Holds copied processes for cut/copy/paste functionality
        copied_processes: [],

        state: 'not_modified', // State of the process list (not_modified, modified, saved)

        init_done: 4, // Counter for initialization progress

        add_edit_mode: 'add', // Mode of the process list (add or edit)
        edit_index: -1, // Index of the process being edited (if any)
    },

    methods: {

        feedSelectChange: function () {
            if (typeof nodes_display !== 'undefined') {
                autocomplete(document.getElementById("new-feed-tag"), Object.keys(nodes_display));
            }
        },

        load: function (
            context_type,
            input_or_virtual_feed_id,
            input_or_virtual_feed_process_list,
            input_or_virtual_feed_name,
            new_feed_name = "",
            new_feed_tag = ""
        ) {
            this.context_type = context_type; // Set the context type (input:0 or feed:1)
            this.input_or_virtual_feed_id = input_or_virtual_feed_id; // Set the ID of the input or virtual feed
            this.input_or_virtual_feed_name = input_or_virtual_feed_name; // Set the name for the modal title
            this.new_feed_name = new_feed_name; // Set the new feed name
            this.new_feed_tag = new_feed_tag; // Set the new feed tag

            this.add_edit_mode = 'add'; // Set the mode to add
            this.edit_index = -1; // Reset the edit index
            this.state = 'not_modified'; // Reset the state to not_modified
            let process_list = process_api.decode(input_or_virtual_feed_process_list);

            for (let i = 0; i < process_list.length; i++) {
                let process = process_list[i];
                let argtype = ProcessArg.NONE; // Default argument type

                // Get arg type
                if (process.fn && process.fn in this.processes_by_key) {
                    let process_info = this.processes_by_key[process.fn];
                    // use the first argument's type
                    if (process_info.args.length > 0) {
                        argtype = process_info.args[0].type;
                    }
                }

                // Set the label for the process according to its argtype
                process.label = argtypes[argtype].cssClass || 'label-default'; // Default to 'label-default' if not found
            }

            this.process_list = process_list;

            if (this.context_type == ContextType.INPUT) {
                this.selected_process = "process__log_to_feed"; // default process for input context
            } else if (this.context_type == ContextType.VIRTUALFEED) {
                this.selected_process = "process__source_feed_data_time"; // default process for feed context
            }

            // Fetch the processes that are relevant to the context type
            Vue.set(process_vue, 'context_only_processes_by_group', process_api.by_group(process_api.filter_for_context(this.processes_by_key, this.context_type)));

            this.processSelectChange(); // Trigger the process select change to update the UI
            // this.scrollto($('#processlist-ui'));

            // Show the process list modal
            $("#processlistModal").modal('show');
            this.adjustModal(); // Adjust the modal height
        },

        adjustModal: function () {
            // Adjust the height of the process list UI
            if ($("#processlistModal").length) {
                var h = $(window).height() - $("#processlistModal").position().top - 180;
                $("#processlist-ui").height(h);
            }
        },

        scrollto: function (scrollTo) {
            let container = $('#processlist-ui');
            container.animate({
                scrollTop: scrollTo.offset().top - container.offset().top + container.scrollTop()
            });
        },

        initprogress: function () {
            this.init_done--;
            if (this.init_done == 0) {
                // Which table draw is this? input and feed list perhaps/
                if (window.table != undefined && window.table.draw != undefined) table.draw();
            }
        },

        save: function () {

            // if global function exists save_processlist
            if (typeof save_processlist === 'function') {

                // Remove extra fields (perhaps refactor this out)
                let output_process_list = [];
                for (let i in this.process_list) {
                    output_process_list.push({
                        fn: this.process_list[i].fn,
                        args: this.process_list[i].args
                    });
                }

                if (save_processlist(this.input_or_virtual_feed_id, output_process_list)) {
                    this.saved(); // Update the state to saved
                }
            } else {
                alert('ERROR: save_processlist function not defined. Please implement it to save the process list.');
            }
        },
        // Strips HTML tags from a string
        // Used to display process descriptions without HTML formatting
        strip_html: function (html) {
            return html.replace(/<(?:.|\n)*?>/gm, '');
        },

        // Closes the process list modal
        // This function is called when the close button is clicked
        close: function () {
            $("#processlistModal").modal('hide');
        },

        // Moves a process in the list up or down
        // index: the index of the process in the process_list array
        // direction: -1 for up, 1 for down
        moveby: function (index, direction) {
            if (index >= 0 && index < this.process_list.length) {
                var process = this.process_list[index];
                var newIndex = index + direction;
                if (newIndex >= 0 && newIndex < this.process_list.length) {
                    this.process_list.splice(index, 1);
                    this.process_list.splice(newIndex, 0, process);
                    this.modified();
                }
            }
        },

        // Removes a process from the list
        // index: the index of the process in the process_list array
        remove: function (index) {
            if (index >= 0 && index < this.process_list.length) {
                this.process_list.splice(index, 1);
                this.modified();
            }
        },

        // Handles switch to edit mode:
        edit: function (index) {
            this.add_edit_mode = 'edit'; // Set the mode to edit
            this.edit_index = index; // Set the index of the process being edited
            this.selected_process = this.process_list[index].fn; // Set the selected process to the one being edited
            this.processSelectChange();
        },

        cancel_edit: function () {
            this.add_edit_mode = 'add'; // Switch back to add mode
        },

        // Handles process selection change
        // This function is called when the process select dropdown changes
        // It updates the args data based on the selected process
        // It also sets default values for the args based on their type
        processSelectChange: function (set_selected_process = false) {

            // Option to set the selected process (used by quick link badges)
            if (set_selected_process) {
                this.selected_process = set_selected_process; // Update the selected process
            }

            // Get the selected process
            let process = this.processes_by_key[this.selected_process];
            let args = JSON.parse(JSON.stringify(process.args));

            // Set default values for Vue args
            if (this.add_edit_mode === 'edit') {
                for (let i = 0; i < args.length; i++) {
                    args[i].value = this.process_list[this.edit_index].args[i];
                    if (args[i].type === ProcessArg.FEEDID) {
                        args[i].new_feed_tag = '';
                        args[i].new_feed_name = '';
                        args[i].new_feed_engine = 5; // Default feed engine
                        args[i].new_feed_interval = 10; // Default feed interval
                        args[i].new_feed_table_name = ''; // Default feed table name
                    }
                }
                Vue.set(process_vue, 'args', args);
                return;
            }

            // ELSE: Set default values for new process
            for (let i = 0; i < args.length; i++) {
                let arg = args[i];
                switch (arg.type) {
                    case ProcessArg.VALUE:
                        arg.value = 0; // Default value for VALUE type
                        if (arg.default !== undefined) {
                            arg.value = arg.default; // Use default value if available
                        }
                        break;
                    case ProcessArg.INPUTID:
                        arg.value = 0; // Default value for INPUTID type

                        if (this.inputs_by_node && Object.keys(this.inputs_by_node).length > 0) {
                            // Default to first input in the first node
                            let first_node = Object.keys(this.inputs_by_node)[0];
                            if (this.inputs_by_node[first_node] && this.inputs_by_node[first_node].length > 0) {
                                arg.value = this.inputs_by_node[first_node][0].id; // Default to first input
                            }
                        }
                        break;
                    case ProcessArg.FEEDID:
                        if (this.context_type === ContextType.INPUT && Array.isArray(arg.engines) && arg.engines.length > 0) {
                            arg.value = -1; // Default value for FEEDID type (create new feed)
                            arg.new_feed_tag = this.new_feed_tag; // Default feed tag
                            arg.new_feed_name = this.new_feed_name; // Default feed name
                            arg.new_feed_engine = 5; // Default feed engine
                            arg.new_feed_interval = 10; // Default feed interval
                            arg.new_feed_table_name = ''; // Default feed table name
                            arg.new_feed_engine = parseInt(arg.engines[0]); // Default to first engine in the list
                        } else {
                            // Select first feed by default if in feed context
                            arg.value = 0; // Default value for FEEDID type
                            if (Object.keys(this.feeds_by_id).length > 0) {
                                // Default to first feed in the list
                                arg.value = Object.keys(this.feeds_by_id)[0];
                            }
                        }
                        break;
                    case ProcessArg.TEXT:
                        arg.value = ''; // Default value for TEXT type
                        break;
                    case ProcessArg.SCHEDULEID:
                        arg.value = 0;
                        // Default to first schedule in the list
                        if (Object.keys(this.schedules).length > 0) {
                            arg.value = Object.keys(this.schedules)[0];
                        }
                        break;
                    case ProcessArg.NONE:
                        arg.value = 0; // Default value for NONE type
                        break;
                }
            }

            // Set the Vue args data
            Vue.set(process_vue, 'args', args);
        },

        // Handles the process add action
        // This function is called when the user clicks the "Add" button
        // It validates the input values and creates a new process entry
        processAdd: function () {
            var process = this.processes_by_key[this.selected_process];

            let output_args = [];
            let label = 'label-muted'; // Default label for the process

            if (this.args != undefined && Array.isArray(this.args)) {
                // Loop through the Vue args and get the values
                for (let i = 0; i < this.args.length; i++) {
                    let arg_type = this.args[i].type;
                    let arg_value = this.args[i].value;
                    label = argtypes[arg_type].cssClass;
                    switch (arg_type) {

                        // Value
                        // Check if the value is a valid number
                        case ProcessArg.VALUE:
                            if (arg_value === undefined || arg_value === null || arg_value === "" || isNaN(arg_value)) {
                                alert('ERROR: Value must be a valid number');
                                return false;
                            }
                            output_args.push(parseFloat(arg_value));
                            break;

                        // Input ID
                        // Input id is from input select so no need to check if it's a valid input
                        case ProcessArg.INPUTID:
                            output_args.push(parseInt(arg_value));
                            break;

                        // Feed ID
                        // Feed id is from feed select so no need to check if it's a valid feed
                        // Create new feed if feed id is -1
                        case ProcessArg.FEEDID:
                            let feedid = parseInt(arg_value);
                            if (feedid == -1) {
                                let feedtag = this.args[i].new_feed_tag;
                                let feedname = this.args[i].new_feed_name;
                                let engine = this.args[i].new_feed_engine;

                                var options = {};
                                if (engine == 6 || engine == 5 || engine == 4 || engine == 1) {
                                    let interval = this.args[i].new_feed_interval;
                                    if (interval == "") {
                                        alert('ERROR: Please select a feed interval');
                                        return false;
                                    }
                                    options = { "interval": interval };
                                }
                                else if (engine == 8 || engine == 0) {
                                    options = { "name": this.args[i].new_feed_table_name };
                                }

                                if (feedname == '') {
                                    alert('ERROR: Please enter a feed name');
                                    return false;
                                }

                                var unit = '';
                                if (process.unit != undefined) unit = process.unit;

                                var result = feed.create(feedtag, feedname, engine, options, unit);
                                feedid = result.feedid;

                                if (!result.success || feedid < 1) {
                                    alert('ERROR: Feed could not be created, ' + result.message);
                                    return false;
                                } else {
                                    // Add feed to the feeds_by_id
                                    if (!process_vue.feeds_by_id.hasOwnProperty(feedid)) {
                                        process_vue.feeds_by_id[feedid] = {
                                            id: feedid,
                                            tag: feedtag,
                                            name: feedname,
                                            engine: engine,
                                            options: options
                                        };
                                    }
                                }

                                output_args.push(feedid);
                            } else {
                                output_args.push(feedid);
                            }
                            break;

                        // Text
                        // Text must not be empty and must not contain commas or colons
                        case ProcessArg.TEXT:
                            // Text must not contain commas and semi-colons
                            if (arg_value === undefined || arg_value === null || arg_value === "") {
                                alert('ERROR: Text must not be empty');
                                return false;
                            }
                            if (arg_value.includes(',') || arg_value.includes(':')) {
                                alert('ERROR: Text must not contain commas or colons');
                                return false;
                            }
                            output_args.push(arg_value);
                            break;

                        // Schedule ID
                        // Schedule id is from schedule select so no need to check if it's a valid schedule
                        case ProcessArg.SCHEDULEID:
                            output_args.push(parseInt(arg_value));
                            break;

                        // None
                        case ProcessArg.NONE:
                            output_args.push(0);
                            break;
                    }
                }
            }

            let new_process = {
                fn: this.selected_process,
                label: label, // Set the label for the process
                args: output_args
            };

            if (this.add_edit_mode === 'edit') {
                // If in edit mode, replace the process at the edit index
                this.process_list[this.edit_index].fn = new_process.fn; // Update the function name
                this.process_list[this.edit_index].args = new_process.args; // Update the arguments
            } else {
                // If in add mode, push the new process to the process list
                this.process_list.push(new_process);
            }

            // this.scrollto($("a.edit-process[processid='"+$("#type-btn-edit").attr('curpos')+"']"));
            this.modified();
        },

        modified: function () {
            this.state = 'modified'; // Update the state to modified
            // $(".feedaccesslabel").attr("href", "#"); // Disable access to feeds
        },

        saved: function () {
            this.state = 'saved'; // Update the state to saved
        },

        // ---------------------------------------------------------------------------------------------
        // Bulk actions for process list
        // These methods allow users to select, cut, copy, paste, and remove processes in bulk
        // ---------------------------------------------------------------------------------------------

        // Select or unselect all processes in the process list
        select_all: function () {
            // If all processes are selected, unselect them
            // Otherwise, select all processes
            if (this.selected_processes.length === this.process_list.length) {
                this.selected_processes = [];
            } else {
                this.selected_processes = this.process_list.map((_, index) => index);
            }
        },

        // Cuts the selected processes from the process list
        cut: function () {
            /*
            if (this.selected_processes.length > 0) {
                this.copied_processes = this.selected_processes.map(index => this.process_list[index]);
                this.selected_processes.forEach(index => this.remove(index));
                this.selected_processes = [];
            }*/
            this.copy(); // Call copy to put the cut processes on the clipboard
            this.remove_selected(); // Remove selected processes from the list
        },

        // Copies the selected processes from the process list and puts them on the clipboard
        copy: function () {
            if (this.selected_processes.length > 0) {
                // Get the selected process objects
                const copiedProcesses = this.selected_processes.map(index => this.process_list[index]);
                this.copied_processes = copiedProcesses;

                // Serialize and copy to clipboard
                const clipboardText = JSON.stringify(copiedProcesses);
                navigator.clipboard.writeText(clipboardText).then(() => {
                    // Optionally notify the user
                    // alert("Copied processes to clipboard.");
                }).catch((error) => {
                    console.error("Failed to copy to clipboard:", error);
                    alert("Failed to copy processes to clipboard. " + error);
                });
            } else {
                alert("No processes selected to copy.");
            }
        },

        // Pastes the copied processes into the process list
        // This is currently a bit sticky for some reason? 
        // You have to click away from the paste button to see the changes
        paste: function () {
            // Try to read from the clipboard first
            navigator.clipboard.readText().then((clipboardText) => {
                try {
                    const pastedProcesses = JSON.parse(clipboardText);
                    if (!Array.isArray(pastedProcesses)) {
                        throw new Error("Clipboard data is not a valid array");
                    }
                    // Validate each pasted process
                    pastedProcesses.forEach(process => {
                        if (!process.fn || !Array.isArray(process.args)) {
                            throw new Error("Invalid process format in clipboard data");
                        }
                        // Ensure the process function exists in the known processes
                        if (!this.processes_by_key[process.fn]) {
                            throw new Error(`Process function ${process.fn} not found`);
                        }
                    });
                    // Insert pasted processes at the end of the process list
                    this.process_list.push(...pastedProcesses);
                    this.selected_processes = []; // Clear selected processes after pasting
                    this.modified();
                } catch (error) {
                    alert("Failed to paste processes. The clipboard data is not in the correct format.");
                    console.error("Error parsing clipboard data:", error);
                }
            }).catch((error) => {
                // If clipboard read fails, fallback to internal copied_processes
                if (this.copied_processes && this.copied_processes.length > 0) {
                    this.process_list.push(...this.copied_processes);
                    this.selected_processes = [];
                    this.modified();
                } else {
                    alert("Failed to read data from the clipboard." + error);
                    console.error("Failed to read data from the clipboard:", error);
                }
            });
        },

        // Removes the selected processes from the process list
        remove_selected: function () {
            if (this.selected_processes.length > 0) {
                // Remove selected processes from the process list
                this.selected_processes.sort((a, b) => b - a); // Sort in descending order to avoid index issues
                this.selected_processes.forEach(index => this.remove(index));
                this.selected_processes = []; // Clear selected processes after removal
            }
        },

        // ---------------------------------------------------------------------------------------------
        // Draws a preview of the process list
        // This function generates HTML for the process list based on the provided raw process list
        // It creates colored badges for each process with links and titles
        // ---------------------------------------------------------------------------------------------
        drawPreview: function (raw_process_list, input) {
            if (!raw_process_list) return "";

            var decoded_process_list = process_api.decode(raw_process_list);
            if (decoded_process_list.length == 0) return "";

            var out = [];
            // create coloured link or span for each process 
            for (let b of this.getBadges(decoded_process_list, input)) {
                let markup = []
                markup.push(b.href ? '<a target="_blank" href="' + b.href + '"' : '<span')
                markup.push(' class="label ' + b.cssClass + '" title="' + b.title + '">')
                markup.push((b.text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'))
                markup.push(b.href ? '</a> ' : '</span> ')
                out.push(markup.join(''));
            }
            return out.join('');
        },

        getBadges: function (process_list, input) {

            if (this.init_done !== 0) {
                // Show loading badge for all processes if not initialized
                return process_list.map(() => ({
                    text: ' ⌛ ',
                    title: '',
                    cssClass: 'muted',
                    href: false
                }));
            }

            let badges = [];
            for (const process of process_list) {
                const process_info = process_api.processes[process.fn];
                if (!process_info) continue;

                // Use the first argument's type, default to NONE
                const argtype = (process_info.args.length > 0) ? process_info.args[0].type : ProcessArg.NONE;
                const argtypeInfo = argtypes[argtype] 

                // Common badge properties
                let badge = {
                    typeName: argtypeInfo.name,
                    cssClass: argtypeInfo.cssClass,
                    text: process_info.short || '',
                    longText: process_info.name,
                    href: false,
                    value: process.args[0] || ''
                }

                let missing_input_feed_schedule = false;

                // Handle feed, input, and schedule arguments
                // Load contextual information such as feed tag and name etc.
                if (argtype === ProcessArg.INPUTID) {
                    badge.input = input;
                } else if (argtype === ProcessArg.FEEDID) {
                    if (this.feeds_by_id[badge.value] !== undefined) {
                        badge.href = [path, "graph/", badge.value].join("");
                        badge.feed = this.feeds_by_id[badge.value]
                    } else {
                        missing_input_feed_schedule = true;
                    }
                } else if (argtype === ProcessArg.SCHEDULEID) {
                    if (this.schedules[badge.value] !== undefined) {
                        badge.schedule = this.schedules[badge.value]
                    } else {
                        missing_input_feed_schedule = true;
                    }
                }
                
                if (!missing_input_feed_schedule) {
                    badge.title = argtypeInfo.title.format(badge);
                } else {
                    // If feed, input, or schedule is missing, set error badge
                    badge.title = '{typeName} {value} does not exist or was deleted'.format(badge);
                    badge.text = 'ERROR';
                    badge.cssClass = 'badge-muted';
                }

                // Highlight processes that require Redis if Redis is not available
                if (!this.has_redis && process_info.requireredis) {
                    badge.cssClass = 'badge-muted';
                }

                badges.push(badge);
            }
            return badges;
        }
    },
    filters: {
        lastValueFormat: function (value) {
            if (value === undefined || value === null || value === '') return '-';

            // if not a number, return as is
            if (isNaN(value)) return value;

            // if less than 30 return 2 dp
            // if less than 1000 return 1 dp
            // if greater than 1000 return 0 dp
            if (value < 30) {
                return parseFloat(value).toFixed(2);
            } else if (value < 1000) {
                return parseFloat(value).toFixed(1);
            } else {
                return parseFloat(value).toFixed(0);
            }
        }
    }
});

// Fetch the process list from the server
process_api.list(function (processes) {
    // Store the processes in the Vue instance
    Vue.set(process_vue, 'processes_by_key', processes);
    process_vue.initprogress();
});

// Fetch the feeds from the server and organize them by tag and ID
feed.list(function (feeds) {
    Vue.set(process_vue, 'feeds_by_tag', feed.by_tag(feeds));
    Vue.set(process_vue, 'feeds_by_id', feed.by_id(feeds));

    process_vue.initprogress();
});

// Schedule Select List
$.ajax({
    url: path + "schedule/list.json", dataType: 'json', async: true, success: function (result) {
        // Schedule list by ID
        var schedules = {};
        for (z in result) {
            schedules[result[z].id] = result[z];
        }

        Vue.set(process_vue, 'schedules', schedules);
        process_vue.initprogress();
    }
});

// Input Select List  
$.ajax({
    url: path + "input/list.json", dataType: 'json', async: true, success: function (result) {
        let inputs = result;

        // Input list by ID
        let inputs_by_id = {};
        inputs.forEach(input => {
            inputs_by_id[input.id] = input;
        });
        Vue.set(process_vue, 'inputs_by_id', inputs_by_id);

        // Input list by node
        let inputs_by_node = {};
        inputs.forEach(input => {
            let node = input.nodeid;
            if (!inputs_by_node[node]) inputs_by_node[node] = [];
            inputs_by_node[node].push(input);
        });
        Vue.set(process_vue, 'inputs', inputs);
        Vue.set(process_vue, 'inputs_by_node', inputs_by_node);
        process_vue.initprogress();
    }
});

// takes plain object with key / value pairs. 
// if found swaps placeholder for variable
// can handle 2 deep nested objects
if (!String.prototype.format) {
    String.prototype.format = function (data) {
        return this.replace(/{([\w\.-]+)}/g, function (match, placeholder) {
            if (placeholder.indexOf('.') > -1) {
                p = placeholder.split('.')
                return typeof data[p[0]] != 'undefined' ? data[p[0]][p[1]] : match
            } else {
                return typeof data[placeholder] != 'undefined' ? data[placeholder] : match
            }
        });
    };
}

// Support keyboard shortcuts
$(document).on("keydown", function (e) {
    if ($("#processlistModal").is(":visible")) { // Ensure modal is visible
        if (e.ctrlKey) {
            switch (e.key) {
                case "c":
                    e.preventDefault();
                    process_vue.copy(); // Call copy method
                    break;
                case "v":
                    e.preventDefault();
                    process_vue.paste(); // Call paste method
                    break;
                case "x":
                    e.preventDefault();
                    process_vue.cut(); // Call cut method
                    break;
            }
        } else if (e.key === "Delete") {
            e.preventDefault();
            process_vue.remove_selected(); // Call remove_selected method
        }
    }
});