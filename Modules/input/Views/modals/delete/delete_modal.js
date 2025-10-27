var input_dialog =
{
	inputid: null,
	
	'loadDelete': function(callback, inputid, tablerow){
		this.inputid = inputid;
		this.drawDelete(callback, tablerow);
	},
	
	'drawDelete':function(callback, row){
		$('#inputDeleteModal').modal('show');
		$('#inputDeleteModalLabel').html('Delete Input: <b>'+input_dialog.inputid+'</b>');
		$("#inputDelete-confirm").off('click').on('click', function(){
			$('#inputDelete-loader').show();
			var result = input.remove(input_dialog.inputid);
			$('#inputDelete-loader').hide();
			/* TBD requires API changes
			if (!result.success) {
				alert('Unable to delete input:\n'+result.message);
				return false;
			} else {
			*/
				if (row != null) table.remove(row);
				update();
				$('#inputDeleteModal').modal('hide');
				if (typeof callback === "function") {
					callback(true);
				}
			//}
			return true;
		});
	}
}

var delete_input = new Vue({
    el: '#inputDeleteModal',
    data: {
        hidden: true,
        buttonLabel: _('Delete'),
        buttonClass: 'btn-primary',
        success: false
    },
    computed: {
        total_inputs: function() {
            return app.total_inputs
        },
        total_devices: function() {
            return app.total_devices
        },
        selected: function() {
            return app.selected
        },
        inputs: function() {
            return app.inputs
        },
        devices: function() {
            return app.devices
        }
    },
    methods: {
        confirm: function(event) {
            var vm = this;
            input.delete_multiple_async(this.selected)
            .done(function() {
                // if all device inputs deleted, then delete the device
                vm.buttonLabel = _('Deleted')
                vm.buttonClass = 'btn-success'
                vm.success = true
                // wait for user to read response, then update & close modal
                setTimeout(function() {
                    update().done(function() {
                        // remove empty devices
                        vm.removeEmptyDevices()
                        .then(function() {
                            // all empty devices removed
                            vm.closeModal()
                        })
                    })
                }, 1000)
            })
            .fail(function(xhr, type, error){
                vm.buttonLabel = _('Error')
                vm.buttonClass = 'btn-warning'
                vm.success = false
            })
        },
        removeEmptyDevices: function() {
            // remove any devices without inputs
            var def = $.Deferred()
            var empty_devices = []
            var deleted_counter = 0
            var remove_responses = []

            // make list of empty devices
            for (n in devices) {
                let device = devices[n]
                if(device.inputs.length === 0) {
                    empty_devices.push(device.id)
                }
            }
            if (empty_devices.length === 0) {
                def.reject('no empty devices');
            }
            // delete each empty device
            for (n in empty_devices) {
                let deviceid = empty_devices[n]
                let ajax
                if(typeof device2 !== 'undefined') {
                    // use new async ajax call from device module js
                    ajax = device2.remove(deviceid)
                } else {
                    // remove this once device module change are in master
                    ajax = $.getJSON(path+"device/delete.json", "id="+deviceid)
                }
                ajax.done(function(remove_response) {
                    remove_response.deviceid = deviceid
                    remove_responses.push(remove_response)
                    deleted_counter ++
                    // on last loop resolve the promise
                    if (deleted_counter >= empty_devices.length) {
                        def.resolve(remove_responses)
                    }
                })
            }
            // return promise
            return def.promise()
        },
        getInputName: function(inputid) {
            var input = getInput(app.devices, inputid);
            return input.name;
        },
        getInputNode: function(inputid) {
            var input = getInput(app.devices, inputid);
            return input.nodeid;
        },
        closeModal: function(event) {
            app.paused = false;
            // clear selection if succesfully deleted
            if (this.success) {
                app.selected = []
            }
            this.hidden = true
            this.errors = {}
            this.message = ''
            // remove ESC keypress event
            document.removeEventListener('keydown', this.escape)
        },
        openModal: function(event) {
            this.hidden = false
            this.errors = {}
            this.message = ''
            this.buttonLabel = _('Delete'),
            this.buttonClass = 'btn-primary'
            app.paused = true
            document.addEventListener("keydown", this.escape);
        },
        escape: function(event) {
            // listen for ESC keypress and close modal
            if (event.keyCode == 27) {
                if(typeof this.closeModal !== 'undefined') {
                    this.closeModal();
                }
            }
        }
    }
});