<?php
    global $path;
?>

<script src="<?php echo $path; ?>Modules/device/Views/device.js"></script>
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
    
    <div id="noprocesses"></div>
    <div id="table" class="input-list"></div>
    
    <div id="output"></div>

    <div id="input-none" class="alert alert-block hide">
        <h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
        <p><?php echo _('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
    </div>
    
    <div id="input-footer" class="hide">
        <button id="device-new" class="btn btn-small" >&nbsp;<i class="icon-plus-sign" ></i>&nbsp;<?php echo _('New device'); ?></button>
    </div>
    <div id="input-loader" class="ajax-loader"></div>
</div>

<?php require "Modules/device/Views/device_dialog.php"; ?>
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
var local_cache_key = 'input_nodes_display';
var nodes_display = docCookies.hasItem(local_cache_key) ? JSON.parse(docCookies.getItem(local_cache_key)) : {};
var selected_inputs = {};
var selected_device = false;

var device_templates = {};
$.ajax({ url: path+"device/template/listshort.json", dataType: 'json', async: true, success: function(data) { 
    device_templates = data; 
    update();
}});

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
function update(){

    // Join and include device data
    $.ajax({ url: path+"device/list.json", dataType: 'json', async: true, success: function(data) {
        
        // Associative array of devices by nodeid
        devices = {};
        for (var z in data) devices[data[z].nodeid] = data[z];
        
        var requestTime = (new Date()).getTime();
        $.ajax({ url: path+"input/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
            table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
              
            // Associative array of inputs by id
            inputs = {};
            for (var z in data) inputs[data[z].id] = data[z];
            
            // Assign inputs to devices
            for (var z in inputs) {
                // Device does not exist which means this is likely a new system or that the device was deleted
                // There needs to be a corresponding device for every node and so the system needs to recreate the device here
                if (devices[inputs[z].nodeid]==undefined) {
                    devices[inputs[z].nodeid] = {description:""};
                    // Device creation
                    $.ajax({ url: path+"device/create.json?nodeid="+inputs[z].nodeid, dataType: 'json', async: false, success: function(deviceid) {
                        if (!deviceid) {
                            alert("There was an error creating device: nodeid="+inputs[z].nodeid+" deviceid="+deviceid); 
                        } else {
                            $.ajax({ url: path+"device/get.json?id="+deviceid, dataType: 'json', async: false, success: function(result) {
                                devices[inputs[z].nodeid] = result;
                            }});
                        }
                    }});
                }
                if (nodes_display[inputs[z].nodeid]==undefined) nodes_display[inputs[z].nodeid] = true;
                // expand if only one feed available
                if (devices[inputs[z].nodeid].inputs==undefined) devices[inputs[z].nodeid].inputs = [];
                // expand if only one feed available or state locally cached in cookie
                if (firstLoad && Object.keys(devices).length > 1 && Object.keys(nodes_display).length == 0) {
                    nodes_display[inputs[z].nodeid] = false;
                }
                devices[inputs[z].nodeid].inputs.push(inputs[z]);
            }
            // cache state in cookie
            if(firstLoad) docCookies.setItem(local_cache_key, JSON.stringify(nodes_display));
            firstLoad = false;
            draw_devices();
            noProcessNotification(devices);
        }});
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
        message = '<div class="alert pull-right">%s <i class="icon-arrow-down" style="opacity: .7;"></i></div>'.replace('%s',"<?php echo _("Configure your device here") ?>")
    }
    $('#noprocesses').html(message);
}

// ---------------------------------------------------------------------------------------------
// Draw devices
// ---------------------------------------------------------------------------------------------
function draw_devices()
{
    // Draw node/input list
    var out = "";
    var counter = 0;
    isCollapsed = !(Object.keys(devices).length > 1);

    var latest_update = [];
    
    var max_name_length = 0;
    var max_description_length = 0;
    var max_time_length = 0;
    var max_value_length = 0;

    for (var node in devices) {
        var device = devices[node]
        counter++
        isCollapsed = !nodes_display[node];
        out += "<div class='node accordion line-height-expanded'>";
        out += '   <div class="node-info accordion-toggle thead'+(isCollapsed ? ' collapsed' : '') + ' ' + nodeIntervalClass(device) + '" data-node="'+node+'" data-toggle="collapse" data-target="#collapse'+counter+'">'
        out += "     <div class='select text-center has-indicator' data-col='B'><span class='icon-chevron-"+(isCollapsed ? 'right' : 'down')+" icon-indicator'><span></div>";
        out += "     <h5 class='name' data-col='A'>"+node+":</h5>";
        out += "     <span class='description' data-col='G'>"+device.description+"</span>";
        out += "     <div class='processlist' data-col='H' data-col-width='auto'></div>";
        out += "     <div class='buttons pull-right'>"
        
        var control_node = "hidden";
        if (device_templates[device.type]!=undefined && device_templates[device.type].control!=undefined && device_templates[device.type].control) control_node = "";
        
        out += "        <div class='device-schedule text-center "+control_node+"' data-col='F' data-col-width='50'><i class='icon-time'></i></div>";
        out += "        <div class='device-last-updated text-center' data-col='E'></div>"; 
        
        var devicekey = device.devicekey;
        if (device.devicekey=="") devicekey = "No device key created"; 
        
        out += "        <a href='#' class='device-key text-center' data-col='D' data-toggle='tooltip' data-tooltip-title='<?php echo _("Show node key") ?>' data-device-key='"+devicekey+"' data-col-width='50'><i class='icon-lock'></i></a>"; 
        out += "        <div class='device-configure text-center' data-col='C' data-col-width='50'><i class='icon-cog' title='<?php echo _('Configure device using device template')?>'></i></div>";
        out += "     </div>";
        out += "  </div>";

        out += "  <div id='collapse"+counter+"' class='node-inputs collapse tbody "+( !isCollapsed ? 'in':'' )+"' data-node='"+node+"'>";
        for (var i in device.inputs) {
            var input = device.inputs[i];
            var selected = selected_inputs[input.id] ? 'checked': '';
            var processlistHtml = processlist_ui ? processlist_ui.drawpreview(input.processList, input) : '';
            latest_update[node] = latest_update > input.time ? latest_update : input.time;

            var title_lines = [ 
                node.toUpperCase() + ': ' + input.name,
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
            var fv = list_format_updated_obj(input.time);
            out += "    <div class='time text-center' data-col='E'><span class='last-update' style='color:" + fv.color + ";'>" + fv.value + "</span></div>";
            var value_str = list_format_value(input.value);
            out += "    <div class='value text-center' data-col='D'>"+value_str+"</div>";
            out += "    <div class='configure text-center cursor-pointer' data-col='C' id='"+input.id+"'><i class='icon-wrench' title='<?php echo _('Configure Input processing')?>'></i></div>";
            out += "  </div>";
            out += "</div>";
            
            if (input.name.length>max_name_length) max_name_length = input.name.length;
            if (input.description.length>max_description_length) max_description_length = input.description.length;
            if (String(fv.value).length>max_time_length) max_time_length = String(fv.value).length;
            if (String(value_str).length>max_value_length) max_value_length = String(value_str).length;            
        }
        
        out += "</div>";
        out += "</div>";
        
        // Node name and description length
        if ((""+node).length>max_name_length) max_name_length = (""+node).length;
        if (device.description.length>max_description_length) max_description_length = device.description.length;        
    }
    $("#table").html(out);

    // show the latest time in the node title bar
    for(let node in latest_update) {
        $('#table [data-node="'+node+'"] .device-last-updated').html(list_format_updated(latest_update[node]));
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
    $('#input-loader').hide();
    if (out=="") {
        $("#input-header").hide();
        $("#input-footer").show();
        $("#input-none").show();
        $("#feedlist-controls").hide();
    } else {
        $("#input-header").show();
        $("#input-footer").show();
        $("#input-none").hide();
        $("#feedlist-controls").show();
    }

    if(typeof $.fn.collapse == 'function'){
        $("#table .collapse").collapse({toggle: false});
        setExpandButtonState($('#table .collapsed').length == 0);
    }
    
    // autowidth($('#table')); // set each column group to the same width
    
    var charsize = 8;
    var padding = 20;
    
    $('[data-col="B"]').width(40);                                        // select
    $('[data-col="A"]').width(max_name_length*charsize+padding);          // name
    $('[data-col="G"]').width(max_description_length*10+padding);         // description
    $('[data-col="F"]').width(50);                                        // schedule
    $('[data-col="E"]').width(max_time_length*charsize+padding);          // time
    $('[data-col="D"]').width(max_value_length*charsize+padding);         // value
    $('[data-col="C"]').width(50);                                        // config

    onResize();
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
    $(this).tooltip({title:'def'}).tooltip(action);
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
            total_selected++;
            form.innerHTML += template;
            form.querySelector('[name="inputid"]').value = inputid;
            form.querySelector('[name="name"]').value = inputs[inputid].name;
            form.querySelector('[name="description"]').value = inputs[inputid].description;
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
 
// Process list UI js
processlist_ui.init(0); // Set input context

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
function auth_check(){
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

$(".auth-check-allow").click(function(){
    var ip = $("#auth-check-ip").html();
    $.ajax({ url: path+"device/auth/allow.json?ip="+ip, dataType: 'json', async: true, success: function(data) {
        $("#auth-check").hide();
    }});
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
