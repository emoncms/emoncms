var process_api = {

    processes: {}, // Store processes

    id_num_map: {}, // Map of id_num to process key

    // Fetch the list of processes
    list: function(callback = null)
    {
        let self = this;
        $.ajax({ 
            url: path+"process/list.json", 
            dataType: 'json', 
            async: (typeof callback !== "function"),
            success: function(result)
            {
                let processes = self.convert_arg_structure(result);
                processes = self.populate_feed_write(processes);
                self.processes = processes; // Store processes
                self.populate_id_num_map(); // Populate id_num map

                if (typeof callback === "function") {
                    callback(processes);
                } else {
                    // Perhaps synchronous result should not be available..
                    return processes;
                }
            }
        });
        return false;
    },

    // Convert singular arguments to args array
    convert_arg_structure: function(processes) {
        // Convert singular arguments to args array
        for (let key in processes) {
            let process = processes[key];

            // If process.args does not exist or is not an array, create it from process.argtype
            if (!process.args || !Array.isArray(process.args)) {
                // Does singular definition exist?
                if (process.argtype !== undefined) {

                    // Base type
                    let singular_arg = { "type": process.argtype };

                    // Copy over engines if available
                    if (process.engines !== undefined && Array.isArray(process.engines)) {
                        singular_arg.engines = process.engines;
                    }

                    // Copy over default if available
                    if (process.default !== undefined) {
                        singular_arg.default = process.default;
                    }

                    // Copy over unit if available
                    if (process.unit !== undefined) {
                        singular_arg.unit = process.unit;
                    }

                    process.args = [singular_arg];
                } else {
                    // If no argtype, initialize args as an empty array
                    process.args = [];
                }
            }
        }
        return processes;
    },

    // Populate feed write information for processes
    populate_feed_write: function(processes) {

        // For each process, check if it has engines
        for (let key in processes) {
            let process = processes[key];
            process.writes_to_feed = false; // Default to false

            // If process has engines, assume these write to feeds
            if (process.engines && process.engines.length > 0) {
                process.writes_to_feed = true;
            } else {
                // Check if any argument has engines
                let has_engines = false;
                if (process.args) {
                    for (let i = 0; i < process.args.length; i++) {
                        if (process.args[i].engines && process.args[i].engines.length > 0) {
                            has_engines = true;
                            break;
                        }
                    }
                }
                if (has_engines) process.writes_to_feed = true;
            }
        }
        return processes;
    },

    // Processes by Group
    // Returns an associative array of processes by group
    by_group: function(processes) {
        const processes_by_group = {};
        for (const key in processes) {
            if (Object.prototype.hasOwnProperty.call(processes, key)) {
                const process = processes[key];
                if (!processes_by_group[process.group]) {
                    processes_by_group[process.group] = {};
                }
                processes_by_group[process.group][key] = process;
            }
        }
        return processes_by_group;
    },

    // Filter processes for a specific context
    // - processes: Object containing all processes
    // - context: Input context (0) or Virtual feed context (1)
    filter_for_context: function(processes, context) {
        const filtered_processes = {};

        for (const key in processes) {
            const process = processes[key];

            // Skip deleted processes
            if (process.group === 'Deleted') continue;

            // In input context, skip virtual processes
            if (context === 0 && process.group === 'Virtual') continue;

            // In virtual feed context, skip certain process types/groups
            if (context === 1) {
                // If process has engines, assume these write to feeds and should be skipped
                if (process.writes_to_feed) continue;
                if (process.function === 'sendEmail') continue;
                if (process.function === 'publish_to_mqtt') continue;
                if (process.group === 'Feed') continue;
                if (process.group === 'Input') continue;
                if (process.group === 'Hidden') continue;
            }

            filtered_processes[key] = process;
        }

        return filtered_processes;
    },

    // Populate the id_num_map from processes
    // This is used to map id_num to process key
    populate_id_num_map: function() {
        // Create a map of id_num to process key
        let id_num_map = {};
        for (let z in this.processes) {
            if (Object.prototype.hasOwnProperty.call(this.processes, z)) {
                let process = this.processes[z];
                // if id_num
                if (process.id_num) {
                    id_num_map[process.id_num] = z;
                }
            }
        }
        this.id_num_map = id_num_map;
    },

    // Decode input/feed process list
    // Example input: "process__log_to_feed_join:2095,4:2096,29:1564,47:10,24:,schedule__if_not_schedule_zero:3"
    // Returns an array of process items with fn, label, and args
    // **This may be moved to the server side in the future**
    decode: function(process_list) {
        let decoded_process_list = [];

        if (process_list === undefined || process_list === null || process_list === '') {
            // If process_list is empty, return an empty array
            return decoded_process_list;
        }

        // 1. Split the process list by commas
        let segments = process_list.split(',');
        for (let i = 0; i < segments.length; i++) {
            // 2. Split each segment by colon
            let parts = segments[i].split(':');
            let id_and_arg_count = parts.length;
            // skip if no parts
            if (id_and_arg_count < 1) {
                continue;
            }

            // 3. Get the process id
            let process_id = parts[0];
            // The process_id could be the module__function name or an id_num
            // Check if it is an id_num

            let process_key = null;
            // Check if process_id is in id_num_map first
            if (this.id_num_map[process_id]) {
                process_key = this.id_num_map[process_id];
            } else {
                // If not, check if it is a process key
                if (this.processes[process_id]) {
                    process_key = process_id;
                }
            }

            if (!process_key) {
                console.warn('Process not found for id:', process_id);
                continue; // Skip if process not found
            }

            // 4. Get the arguments
            let args = parts.slice(1);

            let process_item = {
                fn: process_key,
                args: args
            }

            decoded_process_list.push(process_item);
        }

        return decoded_process_list;
    },

    // Encode a process list to a string
    // Example input: [{fn: 'process__log_to_feed_join', args: ['2095', '4']}, {fn: 'schedule__if_not_schedule_zero', args: ['3']}]
    // Returns a string like "process__log_to_feed_join:2095,4:schedule__if_not_schedule_zero:3"
    // **This may be moved to the server side in the future**
    encode: function(process_list) {
        // Encode a process list to a string
        let encoded_process_list = [];
        for (let i = 0; i < process_list.length; i++) {
            let process_item = process_list[i];
            let process_key = process_item.fn;

            // Get id_num if it exists
            if (this.processes[process_key] && this.processes[process_key].id_num !== undefined) {
                process_key = this.processes[process_key].id_num;
            }
            
            let args = process_item.args.join(':');
            encoded_process_list.push(process_key + ':' + args);
        }
        return encoded_process_list.join(',');
    }
}