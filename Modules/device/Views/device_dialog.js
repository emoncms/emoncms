var device_dialog =
{
    templates: null,
    deviceType: null,
    device: null,
    
    'loadConfig':function(templates, device) {
        this.templates = templates;
        
        if (device != null) {
            this.deviceType = device.type;
            this.device = device;
        }
        else {
            this.deviceType = null;
            this.device = null;
        }
        
        this.drawConfig();
        
        // Initialize callbacks
        this.registerConfigEvents();
    },

    'drawConfig':function() {
        
        $("#device-config-modal").modal('show');
        this.adjustConfigModal();
        this.clearConfigModal();

        var out = "";
        out += "<colgroup>";
        out += "<col span='1'>";
        out += "</colgroup>";

        var groups = [];
        var devicesByGroup = {};
        for (var id in this.templates) {
            var device = this.templates[id];
            device['id'] = id;
            
            if (devicesByGroup[device.group] == undefined) {
                devicesByGroup[device.group] = [];
                groups.push(device.group);
            }
            devicesByGroup[device.group].push(device);
        }
        for (var i in groups.sort()) {
            var group = groups[i];
            
            out += "<tr class='group-header' group='"+group+"' style='background-color:#aaa; cursor:pointer'>";
            out += "<td style='font-size:12px; padding:4px; padding-left:8px; font-weight:bold'>"+group+"</td>";
            out += "</tr>";
            out += "<tbody class='group-body' group='"+group+"' style='display:none'>";
            
            var origins = [];
            var devicesByOrigin = {};
            for (var i in devicesByGroup[group]) {
                var origin = devicesByGroup[group][i].origin;
                if (devicesByOrigin[origin] == undefined) {
                    devicesByOrigin[origin] = [];
                    origins.push(origin);
                }
                devicesByOrigin[origin].push(devicesByGroup[group][i]);
            }
            for (var i in origins.sort()) {
                var origin = origins[i];
                
                out += "<tr class='device-header' origin='"+origin+"' group='"+group+"' style='background-color:#ccc; cursor:pointer'>";
                out += "<td style='font-size:12px; padding:4px; padding-left:16px; font-weight:bold'>"+origin+"</td>";
                out += "</tr>";
                out += "<tbody class='device-body' origin='"+origin+"' group='"+group+"' style='display:none'>";
                
                for (var i in devicesByOrigin[origin]) {
                    var id = devicesByOrigin[origin][i].id;
                    var name = devicesByOrigin[origin][i].name;
                    if (name.length > 25) {
                        name = name.substr(0, 25) + "...";
                    }
                    
                    out += "<tr class='device-row' type='"+id+"' style='cursor:pointer'>";
                    out += "<td>"+name+"</td>";
                    out += "</tr>";
                }
                out += "</tbody>";
            }
            out += "</tbody>";
        }
        $("#template-table").html(out);
        
        if (this.deviceType != null && this.deviceType != '') {
            var template = this.templates[this.deviceType]
            
            $(".group-body[group='"+template.group+"']").show();
            $(".device-body[group='"+template.group+"'][origin='"+template.origin+"']").show();
            $(".device-row[type='"+this.deviceType+"']").addClass("device-selected");
            
            $('#template-description').html('<em style="color:#888">'+template.description+'</em>');
            $('#template-info').show();
        }
    },

    'clearConfigModal':function() {
        var tooltip = "Defaults, like inputs and associated feeds will be automaticaly configured together with the device." +
                "<br><br>" +
                "Initializing a device usualy should only be done once on installation.<br>" +
                "If the configuration was already applied, only missing inputs and feeds will be created.";
        
        $('#template-tooltip').attr("title", tooltip).tooltip({html: true});
        
        if (this.device != null) {
            $('#device-config-node').val(this.device.nodeid);
            $('#device-config-name').val(this.device.name);
            $('#device-config-description').val(this.device.description);

            $('#device-config-delete').show();
        }
        else {
            $('#device-config-node').val('');
            $('#device-config-name').val('');
            $('#device-config-description').val('');

            $('#device-config-delete').hide();
        }
    },

    'adjustConfigModal':function() {

        var width = $(window).width();
        var height = $(window).height();
        
        if ($("#device-config-modal").length) {
            var h = height - $("#device-config-modal").position().top - 180;
            $("#device-config-body").height(h);
        }

        $("#content-wrapper").css("transition","0");
        $("#sidebar-wrapper").css("transition","0");
        if (width < 1024) {
            $("#content-wrapper").css("margin-left","0");
            $("#sidebar-wrapper").css("width","0");
            $("#sidebar-open").show();

            $("#content-wrapper").css("transition","0.5s");
            $("#sidebar-wrapper").css("transition","0.5s");
        } else {
            $("#content-wrapper").css("margin-left","250px");
            $("#sidebar-wrapper").css("width","250px");
            $("#sidebar-open").hide();
            $("#sidebar-close").hide();
        }
    },

    'registerConfigEvents':function() {

        $('#template-table .group-header').off('click').on('click', function() {
            var group = $(this).attr("group");
            
            var e = $(".group-body[group='"+group+"']");
            if (e.is(":visible")) {
                $(".device-body[group='"+group+"']").hide();
                e.hide();
            }
            else e.show();
        });

        $('#template-table .device-header').off('click').on('click', function() {
            var origin = $(this).attr("origin");
            var group = $(this).attr("group");
            
            var e = $(".device-body[origin='"+origin+"'][group='"+group+"']");
            if (e.is(":visible")) e.hide(); else e.show();
        });

        $("#template-table .device-row").off('click').on('click', function () {
            var type = $(this).attr("type");
            
            $(".device-row[type='"+device_dialog.deviceType+"']").removeClass("device-selected");
            if (device_dialog.deviceType !== type) {
                $(this).addClass("device-selected");
                device_dialog.deviceType = type;
                
                var template = device_dialog.templates[type];
                $('#template-description').html('<em style="color:#888">'+template.description+'</em>');
                $('#template-info').show();
            }
            else {
                device_dialog.deviceType = null;
                $('#template-description').text('');
                $('#template-info').hide();
            }
        });

        $("#sidebar-open").off('click').on('click', function () {
            $("#sidebar-wrapper").css("width","250px");
            $("#sidebar-close").show();
        });

        $("#sidebar-close").off('click').on('click', function () {
            $("#sidebar-wrapper").css("width","0");
            $("#sidebar-close").hide();
        });

        $("#device-save").off('click').on('click', function () {

            var node = $('#device-config-node').val();
            var name = $('#device-config-name').val();
            
            if (name && node) {
                var desc = $('#device-config-description').val();
                
                if (device_dialog.device != null) {
                    var fields = {};
                    if (device_dialog.device.nodeid != node) fields['node'] = node;
                    if (device_dialog.device.name != name) fields['name'] = name;
                    if (device_dialog.device.description != desc) fields['description'] = desc;
                    if (device_dialog.device.type != device_dialog.deviceType) {
                        if (device_dialog.deviceType != null) {
                            fields['type'] = device_dialog.deviceType;
                        }
                        else fields['type'] = '';
                    }
                    
                    var result = device.set(device_dialog.device.id, fields);
                    if (typeof result.success !== 'undefined' && !result.success) {
                        alert('Unable to update device fields:\n'+result.message);
                        return false;
                    }
                    update();
                    
                    if (device_dialog.device.type != device_dialog.deviceType
                            && device_dialog.deviceType != null) {
                        
                        var result = device.inittemplate(device_dialog.device.id);
                        if (typeof result.success !== 'undefined' && !result.success) {
                            alert('Unable to initialize device:\n'+result.message);
                            return false;
                        }
                    }
                }
                else {
                    var id = device.create(node, name, desc, device_dialog.deviceType);
                    update();
                    
                    if (id && device_dialog.deviceType != null) {
                        var result = device.inittemplate(id);
                        if (typeof result.success !== 'undefined' && !result.success) {
                            alert('Unable to initialize device:\n'+result.message);
                            return false;
                        }
                    }
                }
                $('#device-config-modal').modal('hide');
            }
            else {
                alert('Device needs to be configured first.');
                return false;
            }
        });

		$("#device-delete").off('click').on('click', function () {
			
			$('#device-config-modal').modal('hide');
			
			device_dialog.loadDelete(device_dialog.device, null);
		});
    },

    'loadInit': function(device) {
        this.device = device;
        
        $('#device-init-modal').modal('show');
        $('#device-init-modal-label').html('Initialize Device: <b>'+device.name+'</b>');
        
        // Initialize callbacks
        this.registerInitEvents();
    },

    'registerInitEvents':function() {
        
        $("#device-init-confirm").off('click').on('click', function() {
            var result = device.inittemplate(device_dialog.device.id);

            if (typeof result.success !== 'undefined' && !result.success) {
                alert('Unable to initialize device:\n'+result.message);
                return false;
            }
            $('#device-init-modal').modal('hide');
            
            return true;
        });
    },

    'loadDelete': function(device, tablerow) {
        this.device = device;

        $('#device-delete-modal').modal('show');
        $('#device-delete-modal-label').html('Delete Device: <b>'+device.name+'</b>');
        
        // Initialize callbacks
        this.registerDeleteEvents(tablerow);
    },

    'registerDeleteEvents':function(row) {
        
        $("#device-delete-confirm").off('click').on('click', function() {
            device.remove(device_dialog.device.id);
            if (row != null) {
                table.remove(row);
                update();
            }
            else if (typeof device_dialog.device.inputs != undefined) {
                // If the table row is undefined and an input list exists, the config dialog
            	// was opened in the input view and all corresponding inputs will be deleted
                var inputIds = [];
                for (var i in device_dialog.device.inputs) {
                    var inputId = device_dialog.device.inputs[i].id;
                    inputIds.push(parseInt(inputId));
                }
                input.delete_multiple(inputIds);
            }
            $('#device-delete-modal').modal('hide');
        });
    }
}
