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
                let processes = result;
                if (typeof callback === "function") {
                    self.processes = processes; // Store processes
                    self.populate_id_num_map(); // Populate id_num map
                    callback(processes);
                } else {
                    // Perhaps synchronous result should not be available..
                    return processes;
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