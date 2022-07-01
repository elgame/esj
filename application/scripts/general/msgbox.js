var msb = {
	confirm: function(msg, title, obj, callback, callback2, style){
		$("body").append('<div class="modal hide fade" id="myModal" style="'+style+'">'+
			'	<div class="modal-header">'+
			'		<button type="button" class="close" data-dismiss="modal">×</button>'+
			'		<h3>'+title+'</h3>'+
			'	</div>'+
			'	<div class="modal-body">'+
			'		<p>'+msg+'</p>'+
			'	</div>'+
			'	<div class="modal-footer">'+
			'		<a href="#" class="btn btncancel" data-dismiss="modal">No</a>'+
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
		$('#myModal .btncancel').on('click', function(){
			if($.isFunction(callback2))
				callback2.call(this, obj);
		});
		return false;
	},

  confirmCancel: function(msg, title, obj, callback, callback2){
    $("body").append('<div class="modal hide fade" id="myModal">'+
      '  <div class="modal-header">'+
      '    <button type="button" class="close" data-dismiss="modal">×</button>'+
      '    <h3>'+title+'</h3>'+
      '  </div>'+
      '  <div class="modal-body">'+
      '    <p>'+msg+'</p>'+
      '    <label for="confirmCancelMotivo">Motivo</label>'+
      '    <select class="input-large" id="confirmCancelMotivo">'+
      '      <option value="02">Comprobante emitido con errores sin relación</option>'+
      '      <option value="01">Comprobante emitido con errores de relación</option>'+
      '      <option value="03">No se llevó acabo la operación</option>'+
      '      <option value="04">Operación nominativa relacionada en una factura global</option>'+
      '    </select>'+
      '    <label for="confirmCancelFolioSustitucion" class="folioSustitucion">Folio Sustitucion</label>'+
      '    <input type="text" class="span4 folioSustitucion" id="confirmCancelFolioSustitucion" value="">'+
      '    <input type="file" id="fileCfdiRelConfirmCancel" placeholder="XML Factura" accept="text/xml">'+
      '  </div>'+
      '  <div class="modal-footer">'+
      '    <a href="#" class="btn btncancel" data-dismiss="modal">No</a>'+
      '    <a href="#" class="btn btn-primary">Si</a>'+
      '  </div>'+
      '</div>');

    $('.folioSustitucion, #fileCfdiRelConfirmCancel').hide();
    $('#confirmCancelMotivo').on('change', () => {
      $('.folioSustitucion, #fileCfdiRelConfirmCancel').hide();
      $('.folioSustitucion').val('');

      if($('#confirmCancelMotivo').val() == '01'){
        $('.folioSustitucion, #fileCfdiRelConfirmCancel').show();
      }
    });

    $("#fileCfdiRelConfirmCancel").change(function(){
      var file = document.getElementById("fileCfdiRelConfirmCancel").files[0]
      var reader = new FileReader();
      reader.readAsText(file, 'UTF-8');

      reader.onloadend = function(){
        var uuid = reader.result.match(/UUID="([A-Z0-9\-]){35,38}"/g);
        if (uuid && uuid.length > 0) {
          var $uuid = uuid[0].replace(/(UUID=|")/g, '');
          $('#confirmCancelFolioSustitucion').val($uuid);
        } else {
          alert('No se encontró el UUID en el archivo XML.');
        }
      };

      reader.onerror = function (error) {
        alert('No se pudo cargar el archivo XML.')
      };
    });

    $('#myModal').modal().on('hidden', function(){
      $(this).remove();
    });
    $('#myModal .btn-primary').on('click', function(){
      if($.isFunction(callback)) {
        const dataa = {
          motivo: $('#confirmCancelMotivo').val(),
          folioSustitucion: $('#confirmCancelFolioSustitucion').val()
        };
        callback.call(this, obj, dataa);

        $('#myModal').modal("hide");
      }
    });
    $('#myModal .btncancel').on('click', function(){
      if($.isFunction(callback2))
        callback2.call(this, obj);
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