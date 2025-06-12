//---------------------------------------------------------------------
// Version 11.8 - Nuno Chaveiro nchaveiro(at)gmail.com 04/2025
//---------------------------------------------------------------------

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

var processlist_ui =
{
  processlist: [], // Cache this lists
  feedlist: [],
  inputlist: [],
  schedulelist: [],
  has_redis: 0,
  table: typeof table !== "undefined" ? table : null,

  'drawpreview': function (processlist, input) {
    if (!processlist) return "";
    var localprocesslist = processlist_ui.decode(processlist);
    if (localprocesslist.length == 0) {
      return ""
    } else {
      var out = [];
      // create coloured link or span for each process 
      for (b of this.getBadges(processlist, input)) {
        let markup = []
        markup.push(b.href ? '<a target="_blank" href="' + b.href + '"' : '<span')
        markup.push(' class="label ' + b.cssClass + '" title="' + b.title + '">')
        markup.push((b.text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'))
        markup.push(b.href ? '</a> ' : '</span> ')
        out.push(markup.join(''));
      }
      return out.join('');
    }
  },

  /**
   * return array of objects with id,id_num properties
   */
  'backward_compatible_list': function () {
    if (!process_vue.processes_by_key) return
    let pl = process_vue.processes_by_key
    let ids = [];
    Object.keys(pl).forEach(function (key) {
      ids.push({ id: key, id_num: pl[key].id_num })
    });
    return ids
  },

  /**
   * return process "name" when given a valid id (if id not number original input returned)
   */
  'getProcessKeyById': function (id) {
    id_int = parseInt(id)
    if (isNaN(id_int)) return id
    old_ids = this.backward_compatible_list()
    // add numeric and textual ids (backward compatible)
    for (id2 in old_ids) {
      if (old_ids[id2].id_num === id_int) {
        return old_ids[id2].id
      }
    }
  },

  'argtypes': {
    0: { cssClass: 'label-important', title: 'Value: {longText} - {value}' },
    1: { cssClass: 'label-warning', title: 'Input: {longText} - ({input.nodeid}:{input.name}) {input.description}' },
    2: { cssClass: 'label-info', title: 'Feed: {longText} - ({feed.tag}:{feed.name})  [{feed.id}]' },
    3: { cssClass: 'label-important', title: 'Text: {longText} - {value}' },
    4: { cssClass: 'label-info', title: 'Topic: {longText} - {value}' },
    5: { cssClass: 'label-warning', title: 'Schedule: {longText} - {schedule.name}' }
  },

  'getBadges': function (processlist, input) {
    if (!processlist) return ""
    var processPairs = processlist.split(",")
    // create empty list of badges
    let badges = []
    for (z in processPairs) {
      // add badge to list or add a blank one if there are any issues.
      let badge = {}
      let id_and_args = processPairs[z].split(":")
      var process_id = parseInt(id_and_args[0])
      process_id = isNaN(process_id) ? id_and_args[0] : this.getProcessKeyById(process_id);

      badge.value = id_and_args[1]

      badge.process = process_vue.processes_by_key.hasOwnProperty(process_id) ? process_vue.processes_by_key[process_id] : false

      if (process_vue.init_done === 0 && badge.process !== false) {

        // set badge properties
        let argtype = ProcessArg.NONE;
        if (badge.process.argtype !== undefined) {
          argtype = badge.process.argtype;
        } else if (badge.process.args !== undefined && Array.isArray(badge.process.args) && badge.process.args.length > 0) {
          // If args is an array, use the first argument's type
          // Review this!!
          argtype = badge.process.args[0].type;
        }

        badge.type = this.argtypes[argtype]
        badge.typeName = badge.type.name
        badge.cssClass = badge.type.cssClass
        var feedviewpath = "graph/";
        if (_SETTINGS && _SETTINGS.hasOwnProperty('feedviewpath') && _SETTINGS.feedviewpath !== "") {
          var feedviewpath = _SETTINGS.feedviewpath;
        }
        badge.href = badge.process.argtype == ProcessArg.FEEDID ? [path, feedviewpath, badge.value].join("") : false;
        badge.text = badge.process.short || ''
        badge.longText = badge.process.name
        badge.input = input
        badge.feed = this.feedlist[badge.value] || {}
        badge.schedule = this.schedulelist[badge.value] || {}
        badge.title = badge.type.title.format(badge);
        // pass the collected badge object as values for the title string template
        badges.push(badge);
      } else if (process_vue.init_done === 0 && this.has_redis == 0 && badge.process['requireredis'] !== undefined && badge.process['requireredis'] == true ? 1 : 0) {
        // no redis
        badges.push({
          text: badge.process['internalerror_reason'],
          title: badge.process['internalerror_desc'],
          cssClass: 'badge-important',
          href: false
        })
      } else if (process_vue.init_done === 0 && !badge.value) {
        // input,feed or schedule doesnt exist
        badges.push({
          title: '{typeName} {value} does not exist or was deleted'.format(badge),
          text: 'ERROR',
          cssClass: 'badge-important',
          href: false
        })
      } else if (process_vue.init_done === 0 && !badge.process) {
        // process not available
        badges.push({
          title: '{typeName} {value} does not exist or was deleted'.format(badge),
          text: 'UNSUPPORTED',
          cssClass: 'badge-important',
          href: false
        })
      } else {
        // default else
        badges.push({
          text: ' ⌛ ',
          title: '',
          cssClass: 'muted',
          href: false
        })
      }
    }
    return badges;
  },

  'decode': function (str) {
    var processlist = [];
    if (str != null && str != "") {
      var tmp = str.split(",");
      for (n in tmp) {
        var process = tmp[n].split(":");
        processlist.push(process);
      }
    }
    return processlist;
  },

  'init': function (contexttype) {
    init_vue(contexttype);
  },

  'load': function (
    input_or_virtual_feed_id, 
    input_or_virtual_feed_process_list, 
    input_or_virtual_feed_name, 
    new_feed_name, 
    new_feed_tag
  ){
    process_vue.load(
      input_or_virtual_feed_id,
      input_or_virtual_feed_process_list,
      input_or_virtual_feed_name,
      new_feed_name,
      new_feed_tag
    );
  }
}

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

function init_vue(contexttype) {

  process_vue = new Vue({
    el: '#process_vue',
    data: {

      contexttype: contexttype, // 0: input, 1: feed/virtual

      input_or_virtual_feed_id: '', // ID of the input or virtual feed
      input_or_virtual_feed_name: '', // Name of the input or virtual feed (used for modal title)
      new_feed_name: '', // Name for the new feed (if creating a new feed)
      new_feed_tag: '', // Tag for the new feed (if creating a new feed)

      args: [],
      inputs_by_node: {},

      selected_process: 'process__log_to_feed',

      processes_by_key: {},
      processes_by_group: {},
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

    },

    methods: {

      feedSelectChange: function () {
        if (typeof nodes_display !== 'undefined') {
          autocomplete(document.getElementById("new-feed-tag"), Object.keys(nodes_display));
        }
      },

      load: function (
        input_or_virtual_feed_id,
        input_or_virtual_feed_process_list,
        input_or_virtual_feed_name,
        new_feed_name = "",
        new_feed_tag = ""
      ) {
        this.input_or_virtual_feed_id = input_or_virtual_feed_id; // Set the ID of the input or virtual feed
        this.input_or_virtual_feed_name = input_or_virtual_feed_name; // Set the name for the modal title
        this.new_feed_name = new_feed_name; // Set the new feed name
        this.new_feed_tag = new_feed_tag; // Set the new feed tag

        this.state = 'not_modified'; // Reset the state to not_modified
        this.process_list = process_api.decode(input_or_virtual_feed_process_list);
        console.log("Process Vue initialized with process list:", this.process_list);
        this.processSelectChange(); // Trigger the process select change to update the UI
        // processlist_ui.scrollto($('#processlist-ui'));


        // Show the process list modal
        $("#processlistModal").modal('show');
        this.adjustModal(); // Adjust the modal height
        $("#process-header-add").show();
        $("#process-header-edit").hide();
        $("#type-btn-add").show();
        $("#type-btn-edit").hide();

      },

      adjustModal: function () {
        // Adjust the height of the process list UI
        if ($("#processlistModal").length) {
            var h = $(window).height() - $("#processlistModal").position().top - 180;
            $("#processlist-ui").height(h);
        }
      },

      initprogress: function () {
        this.init_done--;
        console.log("Process Vue init progress: " + this.init_done);
        if (this.init_done == 0) {
          // Which table draw is this? input and feed list perhaps/
          if (window.table != undefined && window.table.draw != undefined) table.draw();
          console.log("Process Vue initialized successfully.");

          if (this.contexttype == ContextType.INPUT) {
            this.selected_process = "process__log_to_feed"; // default process for input context
          } else if (this.contexttype == ContextType.VIRTUALFEED) {
            this.selected_process = "process__source_feed_data_time"; // default process for feed context
          }
        }
      },

      save: function () {
        let encoded_process_list = process_api.encode(this.process_list);

        // if global function exists save_processlist
        if (typeof save_processlist === 'function') {
          if (save_processlist(this.input_or_virtual_feed_id, encoded_process_list)) {
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

      // Handles process selection change
      // This function is called when the process select dropdown changes
      // It updates the args data based on the selected process
      // It also sets default values for the args based on their type
      processSelectChange: function () {

        // Get the selected process
        let process = this.processes_by_key[this.selected_process];

        let args = {};
        // Set the Vue args data
        if (process.args != undefined && Array.isArray(process.args)) {
          args = JSON.parse(JSON.stringify(process.args));

        } else if (process.argtype != undefined) {

          // Base type
          let singular_arg = { "type": process.argtype };

          // Copy over egines if available
          if (process.engines !== undefined && Array.isArray(process.engines)) {
            singular_arg.engines = process.engines;
          }

          // Copy over unit if available
          if (process.unit !== undefined) {
            singular_arg.unit = process.unit;
          }

          args = [singular_arg];
        }

        // Set default values for Vue args

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
              if (processlist_ui.inputlist.length > 0) {
                arg.value = processlist_ui.inputlist[0].id; // Default to first input
              }
              break;
            case ProcessArg.FEEDID:
              arg.value = -1; // Default value for FEEDID type (create new feed)
              arg.new_feed_tag = this.new_feed_tag; // Default feed tag
              arg.new_feed_name = this.new_feed_name; // Default feed name
              arg.new_feed_engine = 5; // Default feed engine
              arg.new_feed_interval = 10; // Default feed interval
              arg.new_feed_table_name = ''; // Default feed table name

              if (arg.engines !== undefined && Array.isArray(arg.engines)) {
                arg.new_feed_engine = parseInt(arg.engines[0]); // Default to first engine in the list
              }
              break;
            case ProcessArg.TEXT:
              arg.value = ''; // Default value for TEXT type
              break;
            case ProcessArg.SCHEDULEID:
              arg.value = 0; // Default value for SCHEDULEID type
              if (processlist_ui.schedulelist.length > 0) {
                arg.value = processlist_ui.schedulelist[0].id; // Default to first schedule
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

        if (this.args != undefined && Array.isArray(this.args)) {
          // Loop through the Vue args and get the values
          for (let i = 0; i < this.args.length; i++) {
            let arg_type = this.args[i].type;
            let arg_value = this.args[i].value;
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
          label: "info",
          args: output_args
        };

        console.log("Adding new process:", new_process);
        this.process_list.push(new_process);
        // processlist_ui.scrollto($("a.edit-process[processid='"+$("#type-btn-edit").attr('curpos')+"']"));
        this.modified();
      },

      modified: function () {
        this.state = 'modified'; // Update the state to modified
        // $(".feedaccesslabel").attr("href", "#"); // Disable access to feeds
      },

      saved: function () {
        this.state = 'saved'; // Update the state to saved

        // compatibility input vs device view transpose
        /*
        if (feeds.data != undefined) feeds = feeds.data;

        for (z in feeds) {
          if (feeds.hasOwnProperty(z) && (feeds[z].id == this.input_or_virtual_feed_id)) {
            feeds[z].processList = processlist_ui.encode(processlist_ui.contextprocesslist);
          }
        }
        if (window.table != undefined && window.table.draw != undefined) {
          table.draw();
        }
        if (typeof update == 'function') update()
        */
      },

      // ---------------------------------------------------------------------------------------------
      // Bulk actions for process list
      // These methods allow users to select, cut, copy, paste, and remove processes in bulk
      // ---------------------------------------------------------------------------------------------

      // Select or unselect all processes in the process list
      select_all: function() {
          // If all processes are selected, unselect them
          // Otherwise, select all processes
          if (this.selected_processes.length === this.process_list.length) {
              this.selected_processes = [];
          } else {
              this.selected_processes = this.process_list.map((_, index) => index);
          }
      },

      // Cuts the selected processes from the process list
      cut: function() {
          if (this.selected_processes.length > 0) {
              this.copied_processes = this.selected_processes.map(index => this.process_list[index]);
              this.selected_processes.forEach(index => this.remove(index));
              this.selected_processes = [];
          }

          // $(".process-copy").trigger("click");
          // $(".process-delete").trigger("click");
      },

      // Copies the selected processes from the process list
      copy: function() {
          if (this.selected_processes.length > 0) {
              this.copied_processes = this.selected_processes.map(index => this.process_list[index]);
          }
          /*
          const copiedProcessIds = $(".process-select:checked").map(function () {
            return $(this).attr("processid");
          }).get();

          if (copiedProcessIds.length > 0) {
            let contextprocesslist = JSON.parse(JSON.stringify(processlist_ui.contextprocesslist)); // clone

            const clipboardText = JSON.stringify(
              copiedProcessIds.map(processId => {
                return contextprocesslist[processId];
              })
            );

            // Copy to external clipboard
            navigator.clipboard.writeText(clipboardText).then(() => {
              //alert("Copied processes to clipboard:\n" + clipboardText);
            }).catch((error) => {
              console.error("Failed to copy to clipboard:", error);
              alert("Failed to copy processes to clipboard." + error);
            });
          } else {
            alert("No processes selected to copy.");
          }
          */
      },

      // Pastes the copied processes into the process list
      paste: function() {
          if (this.copied_processes && this.copied_processes.length > 0) {
              // Insert copied processes at the end of the process list
              this.process_list.push(...this.copied_processes);
              this.selected_processes = []; // Clear selected processes after pasting
          }

          /*
          navigator.clipboard.readText().then((clipboardText) => {
            try {
              const pastedProcesses = JSON.parse(clipboardText);
              if (!Array.isArray(pastedProcesses)) {
                throw new Error("Clipboard data is not a valid array");
              }

              pastedProcesses.forEach(process => {
                // Add the processed entry back to contextprocesslist
                processlist_ui.contextprocesslist.push(process);
              });

              processlist_ui.modified();
            } catch (error) {
              alert("Failed to paste processes. The clipboard data is not in the correct format.");
              console.error("Error parsing clipboard data:", error);
            }
          }).catch((error) => {
            console.error("Failed to read data from the clipboard:", error);
            alert("Failed to read data from the clipboard." + error);
          });
          */
      },

      // Removes the selected processes from the process list
      remove_selected: function() {
          if (this.selected_processes.length > 0) {
              // Remove selected processes from the process list
              this.selected_processes.sort((a, b) => b - a); // Sort in descending order to avoid index issues
              this.selected_processes.forEach(index => this.remove(index));
              this.selected_processes = []; // Clear selected processes after removal
          }
      }


    }
  });


  // TODO: Remove hidden engines!
  // TODO: Filter processes for context type (input/virtual feed)
  // TODO: Note redis required processes
  // TODO: Filter out deleted processes
  // search for internalerror, requireredis ??

  // Fetch the process list from the server
  process_api.list(function (processes) {
    // Store the processes in the Vue instance
    Vue.set(process_vue, 'processes_by_key', processes);
    Vue.set(process_vue, 'processes_by_group', process_api.by_group(processes));

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
      var schedules = {};
      for (z in result) schedules[result[z].id] = result[z];
      // processlist_ui.schedulelist = schedules;
      // $("#schedule-select").html(processlist_ui.fillschedule());
      process_vue.initprogress();
    }
  });

  // Input Select List  
  $.ajax({
    url: path + "input/list.json", dataType: 'json', async: true, success: function (result) {
      let inputs = result;
      // set vue inputs
      let inputs_by_node = {};
      for (let z in inputs) {
        let node = inputs[z].nodeid;
        if (!inputs_by_node[node]) inputs_by_node[node] = [];
        inputs_by_node[node].push(inputs[z]);
      }
      Vue.set(process_vue, 'inputs_by_node', inputs_by_node);
      process_vue.initprogress();

    }
  });


  /*
      // Support keyboard shortcuts
    $(document).on("keydown", function (e) {
      if ($("#processlistModal").is(":visible")) { // Ensure modal is visible
        if (e.ctrlKey) {
          switch (e.key) {
            case "c":
              $(".process-copy").trigger("click");
              e.preventDefault();
              break;
            case "v":
              $(".process-paste").trigger("click");
              e.preventDefault();
              break;
            case "x":
              $(".process-cut").trigger("click");
              e.preventDefault();
              break;
          }
        } else if (e.key === "Delete") {
          $(".process-delete").trigger("click");
          e.preventDefault();
        }
      }
    });

    'fillschedule': function () {
      var groupname = { 0: 'Public', 1: 'Mine' };
      var groups = [];
      //for (z in result) schedules[result[z].id] = result[z];

      for (z in processlist_ui.schedulelist) {
        var group = processlist_ui.schedulelist[z].own;
        group = groupname[group];
        if (!groups[group]) groups[group] = [];
        processlist_ui.schedulelist[z]['_index'] = z;
        groups[group].push(processlist_ui.schedulelist[z]);
      }

      var out = "";
      for (z in groups) {
        out += "<optgroup label='" + z + "'>";
        for (p in groups[z]) {
          out += "<option value=" + groups[z][p]['id'] + ">" + groups[z][p]['name'] + (z != groupname[1] ? " [" + groups[z][p]['id'] + "]" : "") + "</option>";
        }
        out += "</optgroup>";
      }
      return out;
    },

  'scrollto': function (scrollTo) {
    var container = $('#processlist-ui');
    container.animate({
      scrollTop: scrollTo.offset().top - container.offset().top + container.scrollTop()
    });
  },

    */
}