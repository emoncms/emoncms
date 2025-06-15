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
                self.processes = result;   // Store processes
                self.populate_id_num_map(); // Populate id_num map

                if (typeof callback === "function") {
                    callback(self.processes);
                } else {
                    // Perhaps synchronous result should not be available..
                    return self.processes;
                }
            }
        });
        return false;
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

            if (process.deleted === true) {
                // Skip deleted processes
                continue;
            }

            if (context === 0 && !process.input_context) {
                // If context type is input and process is not valid for input context, skip it
                continue;

            } else if (context === 1 && !process.virtual_feed_context) {
                // If context type is virtual feed and process is not valid for virtual feed context, skip it
                continue;
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
    }
}