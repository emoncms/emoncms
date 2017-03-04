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