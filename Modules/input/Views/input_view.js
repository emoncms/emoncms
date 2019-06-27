

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


$(function(){
    // Process list UI js
    // Set input context
    processlist_ui.init(0)
    .done(function(){
        // processlist successfully required to display inputs
        if (DEVICE_MODULE_INSTALLED) {
            // device module installed, load from /device/list
            $.getJSON(path+'device/template/listshort.json')
            .done(function(data){
                device_templates = data;
                update();
            })
        } else {
            // device module not installed, load from /input/list
            update();
        }
    })
})

var updater;
function updaterStart(func, interval){
      clearInterval(updater);
      updater = null;
      if (interval > 0) updater = setInterval(func, interval);
}
// updaterStart(update, 5000);
// ---------------------------------------------------------------------------------------------
// Fetch device and input lists
// ---------------------------------------------------------------------------------------------

function update() {
    requestTime = (new Date()).getTime();
    if (!DEVICE_MODULE_INSTALLED) {
        // DEVICE MODULE NOT INSTALLED...
        
        // fetch input list
        $.getJSON(path+"input/list.json")
        .done(function(data,status,xhr){
            inputs = data; // cash responses
            nodes = processInputs(data,status,xhr);
            draw_inputs(nodes);
        })
        .fail(function(xhr,error){
            console.error('issue with loading input/list.json')
        })
    } else {
        // DEVICE MODULE INSTALLED...
        // Join and include device data
        $.getJSON(path + 'device/list.json')
        .done( function(response) {
            // map inputs onto devices
            // creates missing devices via ajax
            processDevices( response )
            .done( function(_devices) {
                devices = _devices
                draw_devices(_devices)
            })
            .fail( function(errors) {
                showAjaxErrors(errors)
            })
        })
        // call a function to display the error information
        .fail(showAjaxErrors)
    }
}

/**
 * create an object of inputs grouped by nodeid
 * @return object list of nodes with inputs as values
 */ 
function processInputs(data, textStatus, xhr) {
    table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
    let nodes = {};
    // Object of inputs by id
    for (var z in data) {
        let index = data[z].nodeid;
        if (typeof nodes[index] === 'undefined') {
            nodes[index] = {
                name: index,
                description: ''
            };
            nodes[index].inputs = [];
        }
        nodes[index].inputs.push(data[z]);
    }
    return nodes;
}
/**
 * Get combined devices object on success
 *
 * create new devices if not found
 * will fulfill the promise with a list of devices {Object}
 * will reject the promise with a list of error messages {array}
 * 
 * @param  {Object} nodes all the inputs grouped
 * @param  {Array} devices api response from device/list.json
 * @return {Promise} once all the api calls return successfull the calling function will run the success() function
 */
function assignInputsToDevices ( nodes, _devices ) {
    // use a deferred object resolve on success. 
    // calling function can use the done()/error()/then() function to act on response
    const DEF = $.Deferred();
    const TOTAL_NODES = Object.values(nodes).length;
    const ERRORS = [];
    var devices = {};
    var counter = 0;
    // Assign inputs to devices
    for (var nodeid in nodes) {
        counter ++
        let node = nodes[nodeid];
        let device = getDevice(nodeid, _devices);
        // nodes are just groups of inputs
        // Device does not yet exist which means this is likely a new system or that the device was deleted
        // There needs to be a corresponding device for every node and so the system needs to recreate the device here
        if (typeof device === 'undefined') {
            // DEVICE CREATION
            device = {description:""};
            // get new device id from api response
            $.getJSON(path + "device/create.json?nodeid=" + nodeid)
            .fail( function(xhr,error,message) {
                errors.push(['Error calling device/create.json',error,message].join(':'));
                // on last iteration return errors to calling function 
                if(TOTAL_NODES === counter) {
                    DEF.reject(ERRORS);
                }
            })
            .done( function(deviceid) {
                if (!deviceid) {
                    ERRORS.push("There was an error creating a new device: nodeid=" + nodeid + " deviceid=" + deviceid);
                    // on last iteration return errors to calling function 
                    if(TOTAL_NODES === counter) {
                        DEF.reject(ERRORS);
                    }
                } else {
                    // get complete device object from api response
                    $.getJSON( path + "device/get.json?id=" + deviceid ) 
                    .done( function(device) {
                        let deviceid = device.id
                        // add the newly created device to the list
                        devices[device.nodeid] = device;
                        if (typeof nodes_display[deviceid] === 'undefined') {
                            nodes_display[deviceid] = true;
                        }
                        devices[deviceid].inputs = node.inputs;
                        // expand if only one feed available or state locally cached in cookie
                        if (firstLoad && Object.keys(devices).length > 1 && Object.keys(nodes_display).length == 0) {
                            nodes_display[deviceid] = false;
                        }
                    })
                    .fail(function(xhr,error,message){
                        ERRORS.push([error, message].join(' '));
                    })
                    .always(function(){
                        // on last iteration return devices or errors to calling function 
                        if(TOTAL_NODES === counter) {
                            // if all calls to device/create and device/get succesfull
                            if(ERRORS.length===0){
                                DEF.resolve(devices);
                            }else{
                                DEF.reject(ERRORS);
                            }
                        }
                    })
                }
            })

        } else {
            devices[device.nodeid] = device;
            // device exists. add the inputs
            device.inputs = node.inputs;
        }
        // on last iteration return devices or errors to calling function 
        if(TOTAL_NODES === counter) {
            // if all calls to device/create and device/get succesfull
            if ( ERRORS.length === 0 ) {
                DEF.resolve( devices );
            } else {
                DEF.reject( ERRORS );
            }
        }
    }
    // return promise to calling function so that it can wait for the ajax responses
    return DEF.promise();
}

function showAjaxErrors(xhr, error, message) {
    if(xhr.hasOwnProperty['url']) {
        path =  xhr.url;
    } else {
        message = xhr;
    }
    if (typeof message !== 'string'){
        message = message.join("\n")
    }
    console.log(message, path, error)
}
/**
 * map inputs to devices
 * return devices object for update() to display
 * 
 * @param {Array} devices ajax response body from device/list.json
 * @return {Promise} - resolve({Object} devices), reject({Array} message)
 */
function processDevices(devices) {
    var def = $.Deferred();
    $.getJSON(path + 'input/list.json')
    .done( function(inputs, status, xhr) {
        // group inputs by nodeid
        let nodes = processInputs(inputs, status, xhr);
        // Assign inputs to devices
        assignInputsToDevices( nodes, devices )
        .done(function(devices) {
            // return the devices list
            def.resolve(devices)
        })
        .fail(function( xhr, error, message ) {
            // return errors
            def.reject( xhr, error, message )
        })
    })
    .fail(function(message) {
        def.reject('Ajax error loading input/list.json')
    })
    return def.promise();
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
        message = '<div class="alert pull-right">%s <i class="icon-arrow-down" style="opacity: .7;"></i></div>'.replace('%s',_('Configure your device here'))
    }
    $('#noprocesses').html(message);
}
function draw_inputs(nodes) {
    $('#input-loader').hide();
    var out = "";
    for (var key in nodes) {
        var node = nodes[key];
        isCollapsed = false; //@todo: fix this
        out += buildRow(node)
    }
    $("#table").html(out);
    toggleErrorNotification(out==="")
    autowidth($('#table')); // set each column group to the same width
}
// shows or hides the alert at top of page "no inputs created"
function toggleErrorNotification(isHidden) {
    isHidden = isHidden === true;
    if (isHidden) {
        $("#input-footer").show();
        $("#input-none").show();
        $("#feedlist-controls").hide();
    } else {
        $("#input-footer").show();
        $("#input-none").hide();
        $("#feedlist-controls").show();
    }
}
// ---------------------------------------------------------------------------------------------
// Draw devices
// ---------------------------------------------------------------------------------------------
function draw_devices(devices) {
    // Draw node/input list
    var out = "";
    var latest_update = [];
    
    var max_name_length = 0;
    var max_description_length = 0;
    var max_time_length = 0;
    var max_value_length = 0;

    isCollapsed = !(Object.keys(devices).length > 1);
    // nodes === devices
    for (let device_name in devices) {
        let device = devices[device_name];
        // isCollapsed = !nodes_display[node]; // @todo: fix this
        out += buildRow(device)
        if(device.inputs) {
            device.inputs.forEach(function(input){
                // Node name and description length
                var fv = list_format_updated_obj(input.time);
                var value_str = list_format_value(input.value);
                if (input.name.length>max_name_length) max_name_length = input.name.length;
                if (input.description.length>max_description_length) max_description_length = input.description.length;
                if (String(fv.value).length>max_time_length) max_time_length = String(fv.value).length;
                if (String(value_str).length>max_value_length) max_value_length = String(value_str).length;  
            })
        }
    }
    $("#table").html(out);

    for (let device in devices) {
        latest_update[device] = latest_update > input.time ? latest_update : input.time;
    }
    // show the latest time in the node title bar
    for(let node in latest_update) {
        $('#table [data-node="'+node+'"] .device-last-updated').html(list_format_updated(latest_update[node]));
    }

    // show tooltip with device key on click
    // apply the tooltips to all the node/device groups
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
    $('#input-loader').hide();
    toggleErrorNotification(out==="")

    if(typeof $.fn.collapse == 'function') {
        $("#table .collapse").collapse({toggle: false});
        setExpandButtonState($('#table .collapsed').length == 0);
    }
    
    // autowidth($('#table')); // set each column group to the same width
    
    var charsize = 8;
    var padding = 20;

    $('[data-col="A"]').width(max_name_length*charsize+padding);          // name
    $('[data-col="G"]').width(max_description_length*10+padding);         // description
    $('[data-col="E"]').width(max_time_length*charsize+padding);          // time
    $('[data-col="D"]').width(max_value_length*charsize+padding);         // value
    
    $('[data-col="B"]').width(40);                                        // select
    $('[data-col="F"]').width(50);                                        // schedule
    $('[data-col="C"]').width(50);                                        // config

    onResize();
}
/**
 * returns the id from a node
 * @param {Object} node 
 */
function getNodeId(node){
    return node.nodeid||node.id
}
/**
 * returns the device for a given nodeid
 * @param {Object} nodeid 
 */
function getDevice(nodeid, devices){
    let match;
    devices.forEach(function(device){
        if (device.nodeid === nodeid) {
            match = device;
        }
    })
    return match;
}
/**
 * @param {Object} node - a collection of inputs by node / device
 * @return {string} <html>
 */
function buildRow(node) {
    var nodeid = getNodeId(node);
    if(!nodeid) return '';
    var isCollapsed = false;
    out = "";
    out += "<div class='node accordion line-height-expanded'>";
    out += '  <div class="node-info accordion-toggle thead' + (isCollapsed ? ' collapsed' : '') + ' ' +
              nodeIntervalClass(node) + '" data-node="'+nodeid+'" data-toggle="collapse" data-target="#collapse_' + nodeid + '">'
    out += "   <div class='select text-center has-indicator' data-col='B'>"
    out += "      <span class='icon-chevron-"+(isCollapsed ? 'right' : 'down')+" icon-indicator'><span>"
    out += "   </div>";
    out += "   <h5 class='name' data-col='A'>"+node.name+":</h5>";
    out += "   <span class='description' data-col='G'>"+(node.description || '')+"</span>";
    out += "   <div class='processlist' data-col='H' data-col-width='auto'></div>";
    out += "   <div class='buttons pull-right'>"

    var control_node = "hidden";
    if (device_templates[node.type]!=undefined && device_templates[node.type].control!=undefined && device_templates[node.type].control) {
        control_node = "";
    }

    out += "        <div class='device-schedule text-center "+control_node+"' data-col='F' data-col-width='50'><i class='icon-time'></i></div>";
    out += "        <div class='device-last-updated text-center' data-col='E'></div>";

    if(DEVICE_MODULE_INSTALLED) {
        devicekey = node && node.hasOwnProperty('devicekey') && node.devicekey ? node.devicekey: 'No device key created';
        out += "        <a href='#' class='device-key text-center' data-col='D' data-toggle='tooltip' data-tooltip-title='" +
                         _('Show node key') + "' data-device-key='" + devicekey +
                         "' data-col-width='50'><i class='icon-lock'></i></a>";
        out += "        <div class='device-configure text-center' data-col='C' data-col-width='50'><i class='icon-cog' title='" +
                         _('Configure device using device template') + 
                         "'></i></div>";
    }
    out += "     </div>";
    out += "  </div>";

    out += "  <div id='collapse_"+nodeid+"' class='node-inputs collapse tbody "+( !isCollapsed ? 'in':'' )+"' data-node='"+nodeid+"'>";

    for (var i in node.inputs) {
        var input = node.inputs[i];
        var selected = selected_inputs[input.id] ? 'checked': '';
        var processlistHtml = processlist_ui ? processlist_ui.drawpreview(input.processList, input) : '';

        var title_lines = [
            node.name.toUpperCase() + ': ' + input.name,
            '-----------------------',
            _('ID')+': '+ input.id
        ];
        if(input.value) {
            title_lines.push(_('Value')+': ' + input.value);
        }
        if(input.time) {
            title_lines.push(_('Updated') + ': ' + time_since(input.time));
            title_lines.push(_('Time') + ': ' + input.time);
            title_lines.push(format_time(input.time,'LL LTS') + ' UTC');
        }


        row_title = title_lines.join("\n");

        out += "<div class='node-input " + nodeItemIntervalClass(input) + "' id="+input.id+" title='"+row_title+"'>";
        out += "  <div class='select text-center' data-col='B'>";
        out += "   <input class='input-select' type='checkbox' id='"+input.id+"' "+selected+" />";
        out += "  </div>";
        out += "  <div class='name' data-col='A'>"+input.name+"</div>";
        out += "  <div class='description' data-col='G'>"+input.description+"</div>";
        out += "  <div class='processlist' data-col='H'><div class='label-container line-height-normal'>"+processlistHtml+"</div></div>";
        out += "  <div class='buttons pull-right'>";
        out += "    <div class='schedule text-center hidden' data-col='F'></div>";

        var fv = list_format_updated_obj(input.time);
        out += "    <div class='time text-center' data-col='E'><span class='last-update' style='color:" + fv.color + ";'>" + fv.value + "</span></div>";
        if(DEVICE_MODULE_INSTALLED) {
            var value_str = list_format_value(input.value);
            out += "    <div class='value text-center' data-col='D'>"+value_str+"</div>";
            out += "    <div class='configure text-center cursor-pointer' data-col='C' id='"+input.id+"'>";
            out += "        <i class='icon-wrench' title='" + _('Configure Input processing') + "'></i>";
            out += "    </div>";
        }
        out += "  </div>";
        out += "</div>";
    }

    out += "</div>";
    out += "</div>";
    return out;
}
// ---------------------------------------------------------------------------------------------


$(function(){

    $('#wrap').on("device-delete",function() { update(); });
    $('#wrap').on("device-init",function() { update(); });
    $('#device-new').on("click",function() { device_dialog.loadConfig(device_templates); });

    $("#table").on("click select",".input-select",function(e) {
        input_selection();
    });

    // column title buttons --- they are re-built when new data is loaded
    $("#table").on("shown",".device-key", function(event) {
        $(this).data('shown', true);
    })
    $("#table").on("hidden",".device-key", function(event) {
        $(this).data('shown', false);
    })
    var activeTooltip;
    $("#table").on("click",".device-key", function(e) {
        e.stopPropagation()
        var $btn = $(this),
        action = 'show';
        if($btn.data('shown') && $btn.data('shown')==true){
            action = 'hide';
        }
        // @todo: fix this
        activeTooltip = $(this).tooltip({title:e.currentTarget.dataset.deviceKey}).tooltip(action);
    })
    // hide tooltips when you click off
    $(document).on("click", function(e) {
        if (activeTooltip && !e.target.classList.contains('tooltip-inner')) {
            activeTooltip.tooltip('hide')
            activeTooltip = null;
        }
    })

    $("#table").on("click",".device-schedule",function(e) {
        e.stopPropagation();
        var node = $(this).parents('.node-info').first().data("node");
        window.location = path+"demandshaper?node="+node;

    });

    $("#table").on("click",".device-configure",function(e) {
        if (!DEVICE_MODULE_INSTALLED) return;
        e.stopPropagation();
        // Get device of clicked node
        let node = $(this).parents('.node-info').first().data("node");
        let device = devices[node];
        device_dialog.loadConfig(device_templates, device);
    });

    // selection buttons ---
    $(".input-delete").click(function(){
        $('#inputDeleteModal').modal('show');
        var out = "";
        var ids = [];
        for (var inputid in selected_inputs) {
                if ( selected_inputs[inputid] === true ) {
                    let input = inputs[inputid];
                    if (input.processList == "" && input.description == "" && (parseInt(input.time) + (60*15)) < ((new Date).getTime() / 1000)){
                        // delete now if has no values and updated +15m
                        out += input.nodeid + ":" + input.name + "<br>";
                    } else {
                        out += input.nodeid + ":" + input.name + "<br>";
                    }
                }
        }

        input.delete_multiple(ids);
        update();
        $("#inputs-to-delete").html(out);
    });

    $("#inputEditModal").on('show',function(e){
        // show input fields for the selected inputs
        let template = document.getElementById('edit-input-form').innerHTML;
        let container = document.getElementById('edit-input-form-container');
        container.innerHTML = '';
        total_selected = 0;
        for(inputid in selected_inputs){
            let form = document.createElement('div');
            // if input has been selected duplicate <template> and modify values
            if (selected_inputs[inputid]){
                let input = getInput(inputid);
                if (!input) return // no form if input not found
                total_selected++;
                form.innerHTML += template;
                form.querySelector('[name="inputid"]').value = inputid;
                form.querySelector('[name="name"]').value = input.name;
                form.querySelector('[name="description"]').value = input.description;
                form.querySelector('.input_id').innerText = '#'+inputid;
                let appended = container.appendChild(form.firstElementChild);
                appended.dataset.originalData = serializeInputData(appended);
                $(appended).on('submit',submitSingleInputForm);
            }
        }
        if(total_selected>1){
            $('#inputEditModal .btn.single').addClass('hide');
            $('#inputEditModal .btn.multiple').removeClass('hide');
        }else{
            $('#inputEditModal .btn.single').removeClass('hide');
            $('#inputEditModal .btn.multiple').addClass('hide');
        }
    })
    $("#inputEditModal").on('show',function(e){
        showStatus.clear();
        update();
    })
})

function input_selection()
{
    selected_inputs = {};
    var num_selected = 0;
    $(".input-select").each(function(){
        var id = $(this).attr("id");
        selected_inputs[id] = $(this)[0].checked;
        if (selected_inputs[id]==true) num_selected += 1;
    });

    if (num_selected>0) {
        $(".input-delete,.input-edit").removeClass('hide');
    } else {
        $(".input-delete,.input-edit").addClass('hide');
    }

}

// return input if 'id' property matches given id. else false
function getInput(id){
    for(x in inputs) {
        let input = inputs[x];
        if (parseInt(input.id) === parseInt(id)) {
            return input
        }
    }
    return false;
}

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

    showStatus.info(_('Saving') + '...', fd.inputid);

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
        showStatus.info(_('Saving') + '...',fd.inputid);
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
/**
 * Return the input corresponding to an inputId
 * 
 * @return {Mixed} full input object if found, or undefined
 * @param {Number} inputId an id to search for
 */
function getInput(inputId) {
    let matched_input;
    if(devices) {
        Object.values(devices).forEach(function(device){
            device.inputs.forEach(function(input){
                if (input.id === inputId) {
                    matched_input = input;
                }
            })
        })
    }
    return matched_input;
}

$(function(){
    $("#inputDelete-confirm").off('click').on('click', function(){
        var ids = [];
        for (var inputid in selected_inputs) {
            if (selected_inputs[inputid]==true) ids.push(parseInt(inputid));
        }
        input.delete_multiple(ids);
        update();
        $('#inputDeleteModal').modal('hide');
    });

    $("#table").on('click', '.configure', function() {
        // i == input
        var i = getInput($(this).attr('id'));
        var contextid = i.id; // Current Input ID
        // Input name
        var newfeedname = "";
        var contextname = "";
        if (i.description != "") {
            newfeedname = i.description;
            contextname = "Node " + i.nodeid + " : " + newfeedname;
        }
        else {
            newfeedname = i.name;
            contextname = i.nodeid;
        }
        var newfeedtag = i.nodeid;
        var processlist = processlist_ui.decode(i.processList); // Input process list
        processlist_ui.load(contextid,processlist,contextname,newfeedname,newfeedtag); // load configs
    });

    $("#save-processlist").click(function (){
        var result = input.set_process(processlist_ui.contextid,processlist_ui.encode(processlist_ui.contextprocesslist));
        if (result.success) { processlist_ui.saved(table); } else { alert('ERROR: Could not save processlist. '+result.message); }
    });

    // -------------------------------------------------------------------------------------------------------
    // Device authentication transfer
    // -------------------------------------------------------------------------------------------------------
    auth_check();
    //setInterval(auth_check,5000);
    function auth_check() {
        if (DEVICE_MODULE_INSTALLED) {
            $.ajax({ url: path+"device/auth/check.json", dataType: 'json', async: true, success: function(data) {
                if (typeof data.ip !== "undefined") {
                    $("#auth-check-ip").html(data.ip);
                    $("#auth-check").show();
                    $("#table").css("margin-top","0");
                } else {
                    $("#table").css("margin-top","3rem");
                    $("#auth-check").hide();
                }
            }});
        }
    }
    $(".auth-check-allow").click(function() {
        if (DEVICE_MODULE_INSTALLED) {
            var ip = $("#auth-check-ip").html();
            $.ajax({ url: path+"device/auth/allow.json?ip="+ip, dataType: 'json', async: true, success: function(data) {
                $("#auth-check").hide();
            }});
        }
    });

    // -------------------------------------------------------------------------------------------------------
    // Interface responsive
    //
    // The following implements the showing and hiding of the device fields depending on the available width
    // of the container and the width of the individual fields themselves. It implements a level of responsivness
    // that is one step more advanced than is possible using css alone.
    // -------------------------------------------------------------------------------------------------------

    // watchResize(onResize,50) // only call onResize() after delay (similar to debounce)

    // debouncing causes odd rendering during resize - run this at all resize points...
    $(window).on("resize",onResize);

});


/**
 * find out how many intervals an feed/input has missed
 *
 * @param {object} nodeItem
 * @return mixed
 */
function missedIntervals(nodeItem) {
    // @todo: interval currently fixed to 5s
    var interval = 5;
    if (!nodeItem.time) return null;
    var lastUpdated = new Date(nodeItem.time * 1000);
    var now = new Date().getTime();
    var elapsed = (now - lastUpdated) / 1000;
    let missedIntervals = parseInt(elapsed / interval);
    return missedIntervals;
}
/**
 * get css class name based on number of missed intervals
 *
 * @param {mixed} missed - number of missed intervals, false if error
 * @return string
 */
function missedIntervalClassName (missed) {
    let result = 'status-success';
    // @todo: interval currently fixed to 5s
    if (missed > 4) result = 'status-warning';
    if (missed > 11) result = 'status-danger';
    if (missed === null) result = 'status-danger';
    return result;
}
/**
 * get css class name for node item status
 *
 * first gets number of missed intervals since last update
 * @param {object} nodeItem
 * @return {string}
 */
function nodeItemIntervalClass (nodeItem) {
    let missed = missedIntervals(nodeItem);
    return missedIntervalClassName(missed);
}
/**
 * get css class name for latest node status
 *
 * only returns the status for the most recent update
 * @param {array} - array of nodeItems
 * @return {string}
 */
function nodeIntervalClass (node) {
    let nodeMissed = 0;
    let missed = null;
    // find most recent interval status
    for (f in node.inputs) {
        let nodeItem = node.inputs[f];
        missed = missedIntervals(nodeItem);
        if (missed > nodeMissed) {
            nodeMissed = missed;
        }
    }
    return missedIntervalClassName(missed);
}