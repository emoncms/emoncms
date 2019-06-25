<?php
    global $path;
?>
<?php if ($deviceModule) : ?>
<script src="<?php echo $path; ?>Modules/device/Views/device.js"></script>
<?php endif; ?>
<script src="<?php echo $path; ?>Modules/input/Views/input.js"></script>
<script src="<?php echo $path; ?>Modules/feed/feed.js"></script>
<script src="<?php echo $path; ?>Lib/responsive-linked-tables.js"></script>

<style>

.container-fluid { padding: 0px 10px 0px 10px; }

#table {
    margin-top:3rem
}
#footer {
    margin-left: 0px;
    margin-right: 0px;
}

.navbar-fixed-top {
    margin-left: 0px;
    margin-right: 0px;
}

input[type="checkbox"] { margin:0px; }
.controls { margin-bottom:10px; }
#inputs-to-delete { font-style:italic; }

#auth-check {
    padding:10px;
    background-color:#dc9696;
    margin-top:50px;
    margin-bottom:10px;
    font-weight:bold;
    border: 1px solid #de6464;
    color:#fff;
}

.auth-check-btn {
    float:right;
    margin-top:-2px;
}

#noprocesses .alert{margin:0;border-bottom-color:#fcf8e3;border-radius: 4px 4px 0 0;padding-right:14px}

@media (min-width: 768px) {
    .container-fluid { padding: 0px 20px 0px 20px; }
    .modal-wide{
        width:650px;
        margin-left:-325px
    }
}

@media (max-width: 768px) {
    body {padding:0};
}

.node .node-info{
    border-bottom: 1px solid white;
}
.node .node-info,
.node .node-input {
    position: relative;
}
.node .node-info::after,
.node .node-input::after {
    content: '';
    width: .4em;
    height: 100%;
    display: block;
    position: absolute;
    top: 0;
    right: 0;
    background: rgba(0,0,0,.1);
}
.buttons{
    padding-right: .4em;
}
.status-success.node-info::after,
.status-success.node-input::after{
    background: #28A745!important;
}
.status-danger.node-info::after,
.status-danger.node-input::after{
    background: #DC3545!important;
}
.status-warning.node-info::after,
.status-warning.node-input::after{
    background: #FFC107!important;
}

.status-success.node-info .last-update,
.status-success.node-input .last-update{
    color: #28A745!important;
}
.status-danger.node-info .last-update,
.status-danger.node-input .last-update{
    color: #DC3545!important;
}
.status-warning.node-info .last-update,
.status-warning.node-input .last-update{
    color: #C70!important;
}

</style>

<div>
    <div id="input-header">
        <span id="api-help" style="float:right"><a href="api"><?php echo _('Input API Help'); ?></a></span>
        <h3> <?php echo _('Inputs'); ?></h3>
    </div>

    <div id="feedlist-controls" class="controls" data-spy="affix" data-offset-top="100">
        <button id="expand-collapse-all" class="btn" title="<?php echo _('Collapse') ?>" data-alt-title="<?php echo _('Expand') ?>"><i class="icon icon-resize-small"></i></button>
        <button id="select-all" class="btn" title="<?php echo _('Select all') ?>" data-alt-title="<?php echo _('Unselect all') ?>"><i class="icon icon-check"></i></button>
        <button class="btn input-delete hide" title="Delete"><i class="icon-trash" ></i></button>
        <a href="#inputEditModal" class="btn input-edit hide" title="Edit" data-toggle="modal"><i class="icon-pencil" ></i></a>
    </div>

    <div id="auth-check" class="hide">
        <i class="icon-exclamation-sign icon-white"></i> Device on ip address: <span id="auth-check-ip"></span> would like to connect
        <button class="btn btn-small auth-check-btn auth-check-allow">Allow</button>
    </div>

    <div id="input-none" class="alert alert-block hide">
        <h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
        <p><?php echo _('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
    </div>

    <div id="noprocesses"></div>
    <div id="table" class="input-list"></div>

    <div id="output"></div>


    <div id="input-footer" class="hide">
        <button id="device-new" class="btn btn-small" >&nbsp;<i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New device'); ?></button>
    </div>
    <div id="input-loader" class="ajax-loader"></div>
</div>

<?php if ($deviceModule) require "Modules/device/Views/device_dialog.php"; ?>
<?php require "Modules/input/Views/input_dialog.php"; ?>
<?php require "Modules/process/Views/process_ui.php"; ?>

<script src="<?php echo $path; ?>Lib/moment.min.js"></script>
<script>
    var _user = {};
    _user.lang = "<?php echo $_SESSION['lang']; ?>";
</script>
<script src="<?php echo $path; ?>Lib/user_locale.js"></script>
<script>

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
</script>

<script>
// @todo: standardise these translations functions, also used in admin_main_view.php and feedlist_view.php
/**
 * return object of gettext translated strings
 *
 * @return object
 */
function getTranslations(){
    return {
        'ID': "<?php echo _('ID') ?>",
        'Value': "<?php echo _('Value') ?>",
        'Time': "<?php echo _('Time') ?>",
        'Updated': "<?php echo _('Updated') ?>"
    }
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
</script>

<script>

var path = "<?php echo $path; ?>";
var devices = {};
var inputs = {};
var nodes = {};
var requestTime = 0;
var firstLoad = true;
var local_cache_key = 'input_nodes_display';
var nodes_display = docCookies.hasItem(local_cache_key) ? JSON.parse(docCookies.getItem(local_cache_key)) : {};
var selected_inputs = {};
var selected_device = false;
var isCollapsed = true;

var device_templates = {};
var device_module_installed = <?php echo $deviceModule ? 'true': 'false';?>;


// Process list UI js
// Set input context
processlist_ui.init(0)
.done(function(){
    // processlist successfully required to display inputs
    if (device_module_installed) {
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

var updater;
function updaterStart(func, interval){
      clearInterval(updater);
      updater = null;
      if (interval > 0) updater = setInterval(func, interval);
}
updaterStart(update, 5000);
// ---------------------------------------------------------------------------------------------
// Fetch device and input lists
// ---------------------------------------------------------------------------------------------
var firstLoad = true;
function update() {
    requestTime = (new Date()).getTime();
    if (!device_module_installed) {
        updateInputs();
    } else {
        // device module installed...
        updateDevices();
    }
}
// fetch input list
function updateInputs() {
    $.getJSON(path+"input/list.json")
    .done(function(data,status,xhr){
        inputs = data; // cash responses
        nodes = processInputs(data,status,xhr);
        draw_inputs(nodes);
    })
    .fail(function(xhr,error){
        console.error('issue with loading input/list.json')
    })
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
 * return the combined devices object on success
 * @return Promise - done(Object devices), fail(Array messages)
 */
function assignInputsToDevices() {
    // use a deferred object resolve on success. 
    // calling function can use the done()/error()/then() function to act on response
    var def = $.Deferred();
    var errors = [];
    var totalInputs = Object.values(inputs).length;
    var counter = 0;
    // Assign inputs to devices
    for (var z in inputs) {
        counter++;
        // Device does not exist which means this is likely a new system or that the device was deleted
        // There needs to be a corresponding device for every node and so the system needs to recreate the device here
        if (devices[inputs[z].nodeid] == undefined) {
            // DEVICE CREATION
            devices[inputs[z].nodeid] = {description:""};
            // get new device id from api response
            $.getJSON(path+"device/create.json?nodeid="+inputs[z].nodeid)
            .done(function(deviceid){
                if (!deviceid) {
                    errors.push("There was an error creating device: nodeid="+inputs[z].nodeid+" deviceid="+deviceid);
                    // on last iteration return errors to calling function 
                    if(totalInputs === counter) {
                        def.reject(errors);
                    }
                } else {
                    // get complete device object from api response
                    $.getJSON(path+"device/get.json?id="+deviceid)
                    .done(function(device) {
                        devices[inputs[z].nodeid] = device;
                        if (nodes_display[inputs[z].nodeid]==undefined) nodes_display[inputs[z].nodeid] = true;
                        // add inputs to device
                        if (devices[inputs[z].nodeid].inputs==undefined) {
                            devices[inputs[z].nodeid].inputs = [];
                        }
                        devices[inputs[z].nodeid].inputs.push(inputs[z]);
                        
                        // expand if only one feed available or state locally cached in cookie
                        if (firstLoad && Object.keys(devices).length > 1 && Object.keys(nodes_display).length == 0) {
                            nodes_display[inputs[z].nodeid] = false;
                        }
                    })
                    .fail(function(xhr,error,message){
                        errors.push([error, message].join(' '));
                    })
                    .always(function(){
                        // on last iteration return devices or errors to calling function 
                        if(totalInputs === counter) {
                            // if all calls to device/create and device/get succesfull
                            if(errors.length===0){
                                def.resolve(devices);
                            }else{
                                def.reject(errors);
                            }
                        }
                    })
                }
            })
            .fail(function(xhr,error,message){
                errors.push(['Error calling device/create.json',error,message].join(':'));
                // on last iteration return errors to calling function 
                if(totalInputs === counter) {
                    def.reject(errors);
                }
            })
        } else {
            // NO NEW DEVICE TO CREATE
            // add inputs to device
            if (devices[inputs[z].nodeid].inputs==undefined) {
                devices[inputs[z].nodeid].inputs = [];
            }
            devices[inputs[z].nodeid].inputs.push(inputs[z]);

            // on last iteration return devices or errors to calling function 
            if(totalInputs === counter) {
                // if all calls to device/create and device/get succesfull
                if(errors.length===0){
                    def.resolve(devices);
                }else{
                    def.reject(errors);
                }
            }
        }
    }
    // return promise to calling function so that it can wait for the ajax responses
    return def.promise();
}
/**
 * Join and include device data
 * then call draw_devices()
 * @return void
 */
function updateDevices() {
    // Join and include device data
    var devices = {};
    $.getJSON(path+'device/list.json')
    .done(function(response) {
        // map inputs onto devices
        processDevices(response)
          .done(draw_devices)
          .fail(showAjaxErrors)
    })
    .fail(showAjaxErrors)
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
 * map api response to correct properties
 * return devices object for updateDevices() to display
 * 
 * @param data - ajax response body from device/list.json
 * @return Promise - resolve({devices}), reject(message)
 */
function processDevices(data) {
    // device objects with nodeid as key
    var def = $.Deferred();
    // empty cache
    devices = {};
    for (var z in data) {
        devices[data[z].nodeid] = data[z];
    }
    $.getJSON(path+'input/list.json')
    .done( function(data, status, xhr) {
        // store to global
        inputs = processInputs(data,status,xhr);
        // Assign inputs to devices
        assignInputsToDevices()
        .done(def.resolve)
        .fail(def.reject)
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
        message = '<div class="alert pull-right">%s <i class="icon-arrow-down" style="opacity: .7;"></i></div>'.replace('%s',"<?php echo _("Configure your device here") ?>")
    }
    $('#noprocesses').html(message);
}
function draw_inputs(nodes) {
    $('#input-loader').hide();
    var out = "";
    for (var key in nodes) {
        var node = nodes[key];
        isCollapsed = false; //@todo: fix this
        out += buildRow(key, node)
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
function draw_devices(devices) 
{
    // Draw node/input list
    var out = "";
    var latest_update = [];

    isCollapsed = !(Object.keys(devices).length > 1);
    for (let node in devices) {
        // isCollapsed = !nodes_display[node]; // @todo: fix this
        out += buildRow(node, devices[node])
    }
    $("#table").html(out);

    for (let node in devices) {
        latest_update[node] = latest_update > input.time ? latest_update : input.time;
    }
    // show the latest time in the node title bar
    for(let node in latest_update) {
        $('#table [data-node="'+node+'"] .device-last-updated').html(list_format_updated(latest_update[node]));
    }

    // show tooltip with device key on click
    // todo: improve efficiency of the tooltip addon 
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

    if(typeof $.fn.collapse == 'function'){
        $("#table .collapse").collapse({toggle: false});
        setExpandButtonState($('#table .collapsed').length == 0);
    }
    autowidth($('#table')); // set each column group to the same width
}

function buildRow(key, node) {
    isCollapsed = false;
    index = ''; // convert key to hex to use as attribute value
    for (var i = 0; i < key.length; i++) index += key.charCodeAt(i).toString(16);
    out = "";
    out += "<div class='node accordion line-height-expanded'>";
    out += '   <div class="node-info accordion-toggle thead'+(isCollapsed ? ' collapsed' : '') + ' ' + nodeIntervalClass(node) + '" data-node="'+node.name+'" data-toggle="collapse" data-target="#collapse_'+index+'">'
    out += "     <div class='select text-center has-indicator' data-col='B'><span class='icon-chevron-"+(isCollapsed ? 'right' : 'down')+" icon-indicator'><span></div>";
    out += "     <h5 class='name' data-col='A'>"+node.name+":</h5>";
    out += "     <span class='description' data-col='G'>"+(node.description || '')+"</span>";
    out += "     <div class='processlist' data-col='H' data-col-width='auto'></div>";
    out += "     <div class='buttons pull-right'>"

    var control_node = "hidden";
    if (device_templates[node.type]!=undefined && device_templates[node.type].control!=undefined && device_templates[node.type].control) {
        control_node = "";
    }

    out += "        <div class='device-schedule text-center "+control_node+"' data-col='F' data-col-width='50'><i class='icon-time'></i></div>";
    out += "        <div class='device-last-updated text-center' data-col='E'></div>";

    if(device_module_installed) {
        devicekey = node && node.hasOwnProperty('devicekey') ? node.devicekey: 'No device key created';
        out += "        <a href='#' class='device-key text-center' data-col='D' data-toggle='tooltip' data-tooltip-title='<?php echo _("Show node key") ?>' data-device-key='"+devicekey+"' data-col-width='50'><i class='icon-lock'></i></a>";
        out += "        <div class='device-configure text-center' data-col='C' data-col-width='50'><i class='icon-cog' title='<?php echo _('Configure device using device template')?>'></i></div>";
    }
    out += "     </div>";
    out += "  </div>";

    out += "  <div id='collapse_"+index+"' class='node-inputs collapse tbody "+( !isCollapsed ? 'in':'' )+"' data-node='"+node.name+"'>";

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
            title_lines.push(_('Updated')+": "+ time_since(input.time));
            title_lines.push(_('Time')+': '+ input.time);
            title_lines.push(format_time(input.time,'LL LTS')+" UTC");
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
        out += "    <div class='time text-center' data-col='E'>"+list_format_updated(input.time)+"</div>";
        if(device_module_installed) {
            out += "    <div class='value text-center' data-col='D'>"+list_format_value(input.value)+"</div>";
            out += "    <div class='configure text-center cursor-pointer' data-col='C' id='"+input.id+"'><i class='icon-wrench' title='<?php echo _('Configure Input processing')?>'></i></div>";
        }
        out += "  </div>";
        out += "</div>";
    }

    out += "</div>";
    out += "</div>";
    return out;
}
// ---------------------------------------------------------------------------------------------

$('#wrap').on("device-delete",function() { update(); });
$('#wrap').on("device-init",function() { update(); });
$('#device-new').on("click",function() { device_dialog.loadConfig(device_templates); });

$("#table").on("click select",".input-select",function(e) {
    input_selection();
});

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

// column title buttons ---

$("#table").on("shown",".device-key",function(e) { $(this).data('shown',true) })
$("#table").on("hidden",".device-key",function(e) { $(this).data('shown',false) })
$("#table").on("click",".device-key",function(e) {
    e.stopPropagation()
    var $btn = $(this),
    action = 'show';
    if($btn.data('shown') && $btn.data('shown')==true){
        action = 'hide';
    }
    // @todo: fix this
    $(this).tooltip({title:e.currentTarget.dataset.deviceKey}).tooltip(action);
})
// $("#table").on("click",".device-key",function(e) {
//     e.stopPropagation();
//     var node = $(this).parents('.node-info').first().data("node");
//     $this = $(this)
//     if(!$this.data('original')) $this.data('original',$this.html())
//     if(!$this.data('originalWidth')) $this.data('originalWidth',$this.width())
//     $this.data('state', !$this.data('state')||false)
//     let width = 315
//     if($this.data('state')){
//         $this.html(devices[node].devicekey)
//         $this.css({position:'absolute'}).animate({marginLeft:-Math.abs(width-$(this).width()), width:width}) // value will be of fixed size
//     }else{
//         $this.html($this.data('original'))
//         $this.animate({marginLeft:0, width:$this.data('originalWidth')},'fast') // reset to original width
//     }
// });

$("#table").on("click",".device-schedule",function(e) {
    e.stopPropagation();
    var node = $(this).parents('.node-info').first().data("node");
    window.location = path+"demandshaper?node="+node;

});

$("#table").on("click",".device-configure",function(e) {
    if (!device_module_installed) return;
    e.stopPropagation();
    // Get device of clicked node
    node = $(this).parents('.node-info').first().data("node");
    var device = devices[node];
    device_dialog.loadConfig(device_templates, device);
});


// selection buttons ---

$(".input-delete").click(function(){
      $('#inputDeleteModal').modal('show');
      var out = "";
      var ids = [];
      for (var inputid in selected_inputs) {
            if (selected_inputs[inputid]==true) {
                  var i = inputs[inputid];
                  if (i.processList == "" && i.description == "" && (parseInt(i.time) + (60*15)) < ((new Date).getTime() / 1000)){
                        // delete now if has no values and updated +15m
                        // ids.push(parseInt(inputid));
                        out += i.nodeid+":"+i.name+"<br>";
                  } else {
                        out += i.nodeid+":"+i.name+"<br>";
                  }
            }
      }

      input.delete_multiple(ids);
      update();
      $("#inputs-to-delete").html(out);
});
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

    showStatus.info('<?php echo _('Saving') ?>...',fd.inputid);

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
        showStatus.info('<?php echo _('Saving') ?>...',fd.inputid);
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
    var i = inputs[$(this).attr('id')];
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
    if (device_module_installed) {
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
    if (device_module_installed) {
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


</script>
