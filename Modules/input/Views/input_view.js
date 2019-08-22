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

var devices = {};
var inputs = {};
var nodes = {};
var local_cache_key = 'input_nodes_display';
var nodes_display = docCookies.hasItem(local_cache_key) ? JSON.parse(docCookies.getItem(local_cache_key)) : {};
// clear cookie value if not in correct format
if (Array.isArray(nodes_display)) nodes_display = {};
var selected_inputs = {};
var selected_device = false;

if (device_module) {
    var device_templates = {};
    $.ajax({ url: path+"device/template/listshort.json", dataType: 'json', async: true, success: function(data) { 
        device_templates = data; 
        update();
    }});
} else {
    setTimeout(update,100);
}

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

    devices = {};
    // Join and include device data
    if (device_module) {
        $.ajax({ url: path+"device/list.json", dataType: 'json', async: true, success: function(result) {
            // Associative array of devices by nodeid
            for (var z in result) {
                devices[result[z].nodeid] = result[z]
                devices[result[z].nodeid].inputs = []
            }
            update_inputs();
        }});
    } else {
        update_inputs();
    }
}

function update_inputs() {
    var requestTime = (new Date()).getTime();
    $.ajax({ url: path+"input/list.json", dataType: 'json', async: true, success: function(data, textStatus, xhr) {
        table.timeServerLocalOffset = requestTime-(new Date(xhr.getResponseHeader('Date'))).getTime(); // Offset in ms from local to server time
          
        // Associative array of inputs by id
        inputs = {};
        for (var z in data) inputs[data[z].id] = data[z];
        
        // Assign inputs to devices
        for (var z in inputs) {
            let nodeid = inputs[z].nodeid;
            
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
                
                if (device_module) {
                    // Device creation
                    $.ajax({ url: path+"device/create.json?nodeid="+nodeid, dataType: 'json', async: false, success: function(result) {
                        if (result.success!=undefined) {
                            alert("There was an error creating device: nodeid="+nodeid+" message="+result.message); 
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
            devices[nodeid].inputs.push(inputs[z]);
        }
        // cache states in cookie from here on
        if(firstLoad) {
            $('#input-loader').hide();
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
        
        var control = "hidden";
        // if (device_templates[device.type]!=undefined && device_templates[device.type].control!=undefined && device_templates[device.type].control) control = "";
        
        var devicekey = device.devicekey;
        if (device.devicekey===false) devicekey = "Device module required for this feature";
        if (device.devicekey==="") devicekey = "No device key created"; 
        
        out += "<div class='node accordion line-height-expanded'>";
        out += '   <div class="node-info accordion-toggle thead'+(isCollapsed ? ' collapsed' : '') + ' ' + nodeUpdateStatus(device.inputs) + '" data-node="'+node+'" data-toggle="collapse" data-target="#collapse'+counter+'">'
        out += "     <div class='select text-center has-indicator' data-col='B'><span class='icon-chevron-"+(isCollapsed ? 'right' : 'down')+" icon-indicator'><span></div>";
        out += "     <h5 class='name' data-col='A'>"+node+":</h5>";
        out += "     <span class='description' data-col='G'>"+device.description+"</span>";
        out += "     <div class='processlist' data-col='H' data-col-width='auto'></div>";
        out += "     <div class='buttons pull-right'>"
        out += "        <div class='device-schedule text-center "+control+"' data-col='F' data-col-width='50'><i class='icon-time'></i></div>";
        out += "        <div class='device-last-updated text-center' data-col='E'></div>"; 
        out += "        <a href='#' class='device-key text-center' data-col='D' data-toggle='tooltip' data-tooltip-title='"+_("Show node key")+"' data-device-key='"+devicekey+"' data-col-width='50'><i class='icon-lock'></i></a>"; 
        out += "        <div class='device-configure text-center' data-col='C' data-col-width='50'><i class='icon-cog' title='"+_('Configure device using device template')+"'></i></div>";
        out += "     </div>";
        out += "  </div>";

        out += "  <div id='collapse"+counter+"' class='node-inputs collapse tbody "+( !isCollapsed ? 'in':'' )+"' data-node='"+node+"'>";
        for (var i in device.inputs) {
            var input = device.inputs[i];
            var selected = selected_inputs[input.id] ? 'checked': '';
            var processlistHtml = processlist_ui ? processlist_ui.drawpreview(input.processList, input) : '';
            latest_update[node] = latest_update > input.time ? latest_update : input.time;
            
            var updated = itemUpdateString(input.time);
            var value = itemValueFormat(input.value);
            
            var title_lines = [ 
                node.toUpperCase() + ': ' + input.name,
                '-----------------------',
                _('ID')+': '+ input.id
            ];
            if (input.value) {
                title_lines.push(_('Value')+': ' + input.value);
            }
            if (input.time) {
                title_lines.push(_('Updated')+": "+ updated);
                title_lines.push(_('Time')+': '+ input.time);
                // title_lines.push(format_time(input.time,'LL LTS')+" UTC");
            }
            
            row_title = title_lines.join("\n");
            
            out += "<div class='node-input " + itemUpdateStatus(input) + "' id="+input.id+" title='"+row_title+"'>";
            out += "  <div class='select text-center' data-col='B'>";
            out += "   <input class='input-select' type='checkbox' id='"+input.id+"' "+selected+" />";
            out += "  </div>";
            out += "  <div class='name' data-col='A'>"+input.name+"</div>";
            out += "  <div class='description' data-col='G'>"+input.description+"</div>";
            out += "  <div class='processlist' data-col='H'><div class='label-container line-height-normal'>"+processlistHtml+"</div></div>";
            out += "  <div class='buttons pull-right'>";
            out += "    <div class='schedule text-center hidden' data-col='F'></div>";
            out += "    <div class='time text-center' data-col='E'><span class='last-update'>" + updated + "</span></div>";
            out += "    <div class='value text-center' data-col='D'>"+value+"</div>";
            out += "    <div class='configure text-center cursor-pointer' data-col='C' id='"+input.id+"'><i class='icon-wrench' title='"+_('Configure Input processing')+"'></i></div>";
            out += "  </div>";
            out += "</div>";
            
            if (input.name.length > max_name_length) max_name_length = input.name.length;
            if (input.description.length > max_description_length) max_description_length = input.description.length;
            if (String(updated).length > max_time_length) max_time_length = String(updated).length;
            if (String(value).length > max_value_length) max_value_length = String(value).length;            
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
        $('#table [data-node="'+node+'"] .device-last-updated').html(itemUpdateFormat(latest_update[node]));
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
    
    if (out=="") {
        $("#input-header").hide();
        $("#input-controls").hide();
        $("#input-footer").show();
        $("#input-none").show();
    } else {
        $("#input-header").show();
        $("#input-controls").show();
        $("#input-footer").show();
        $("#input-none").hide();
    }
    
    if(typeof $.fn.collapse == 'function'){
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
    
    if (device_module) {
        device_dialog.loadConfig(device_templates, device);
    } else {
        alert("Please install the device module to enable this feature");
    }
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
// Interface responsive
//
// The following implements the showing and hiding of the device fields depending on the available width
// of the container and the width of the individual fields themselves. It implements a level of responsivness
// that is one step more advanced than is possible using css alone.
// -------------------------------------------------------------------------------------------------------

// watchResize(onResize,50) // only call onResize() after delay (similar to debounce)

// debouncing causes odd rendering during resize - run this at all resize points...
$(window).on("resize",onResize);

$(function(){
    $(document).on('hide show', '#table', function(event){
        // cache state in cookie
        if(!firstLoad) {
            nodes_display[event.target.dataset.node] = event.type === 'show';
            docCookies.setItem(local_cache_key, JSON.stringify(nodes_display));
            firstLoad = false;
        }
        console.log(event.target.dataset.node,nodes_display)
    })
})
