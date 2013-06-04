var msb = {
	confirm: function(msg, title, obj, callback){
		$("body").append('<div class="modal hide fade" id="myModal">'+
			'	<div class="modal-header">'+
			'		<button type="button" class="close" data-dismiss="modal">×</button>'+
			'		<h3>'+title+'</h3>'+
			'	</div>'+
			'	<div class="modal-body">'+
			'		<p>'+msg+'</p>'+
			'	</div>'+
			'	<div class="modal-footer">'+
			'		<a href="#" class="btn" data-dismiss="modal">No</a>'+
			'		<a href="#" class="btn btn-primary">Si</a>'+
			'	</div>'+
			'</div>');

		$('#myModal').modal().on('hidden', function(){
			$(this).remove();
		});
		$('#myModal .btn-primary').on('click', function(){
			if($.isFunction(callback))
				callback.call(this, obj);
			else
				window.location = obj.href;

			$('#myModal').modal("hide");
		});
		return false;
	},

	info: function(msg, obj, callback){
		// $.msgbox(msg, {
		//   type: "info"
		// }, function(result) {
		//   if (result) {
		// 	  if($.isFunction(callback))
		// 		  callback.call(this, obj);
		// 	  /*else
		// 		  window.location = obj.href;*/
		//   }
		// });
	},

	error: function(msg, obj, callback){
		// $.msgbox(msg, {
		// 	  type: "error"
		// 	}, function(result) {
		// 	  if (result) {
		// 		  if($.isFunction(callback))
		// 			  callback.call(this, obj);
		// 		  /*else
		// 			  window.location = obj.href;*/
		// 	  }
		// 	});
	}
};