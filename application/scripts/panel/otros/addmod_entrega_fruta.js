$(function(){
	cuentas.init();
});


var cuentas = (function($){
	var objr = {};
	var jumpIndex = 0;

	function init(){
		$('#formprovee').keyJump();

		// $("#tableCuentas").on('change', '.chk_banamex', onChangeBanamex);
		$("#tableCuentas").on('click', '.delProd', onClickDeleteCuenta);
		$("#tableCuentas").on('keypress', '.prod_cantidad', onKeypressAddRow);

    // busca la boleta.
    $("#fboleta").on("change", function(event) {
      $.ajax({
          url: base_url + 'panel/bascula/ajax_load_folio/',
          dataType: "json",
          data: {
                folio: (parseInt($("#fboleta").val())||0),
                tipo: 'en',
                area: $("#fid_area").val(),
              },
          success: function(data) {
            if (parseFloat(data) > 0) {
              $("#fid_bascula").val(data);
              $("#fboleta").css("background-color", "#A1F57A");
            } else {
              $("#fid_bascula").val('');
              $("#fboleta").css("background-color", "#FFD071");
            }
          }
      });
    });

		// Autocomplete para los Vehiculos.
    $("#fvehiculo").autocomplete({
      source: base_url + 'panel/vehiculos/ajax_get_vehiculos/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $vehiculo =  $(this);

        // $vehiculo.val(ui.item.id);
        $("#vehiculoId").val(ui.item.id);
        $vehiculo.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#fvehiculo").css("background-color", "#FFD071");
        $("#vehiculoId").val('');
      }
    });

    $("#fchofer").autocomplete({
	    source: function(request, response) {
	      var params = {term : request.term};
	      // if(parseInt($("#fid_empresa").val()) > 0)
	      //   params.did_empresa = $("#fid_empresa").val();
	      params.empleados = 'true';
	      $.ajax({
	          url: base_url + 'panel/usuarios/ajax_get_usuarios/',
	          dataType: "json",
	          data: params,
	          success: function(data) {
	              response(data);
	          }
	      });
	    },
	    minLength: 1,
	    selectFirst: true,
	    select: function( event, ui ) {
	      $("#fchoferId").val(ui.item.id);
	      $("#fchofer").val(ui.item.label).css({'background-color': '#99FF99'});
	    }
	  }).keydown(function(e){
	    if (e.which === 8) {
	     $(this).css({'background-color': '#FFD9B3'});
	      $('#fchoferId').val('');
	    }
	  });

	  $("#fencargado").autocomplete({
	    source: function(request, response) {
	      var params = {term : request.term};
	      // if(parseInt($("#fid_empresa").val()) > 0)
	      //   params.did_empresa = $("#fid_empresa").val();
	      params.empleados = 'true';
	      $.ajax({
	          url: base_url + 'panel/usuarios/ajax_get_usuarios/',
	          dataType: "json",
	          data: params,
	          success: function(data) {
	              response(data);
	          }
	      });
	    },
	    minLength: 1,
	    selectFirst: true,
	    select: function( event, ui ) {
	      $("#fencargadoId").val(ui.item.id);
	      $("#fencargado").val(ui.item.label).css({'background-color': '#99FF99'});
	    }
	  }).keydown(function(e){
	    if (e.which === 8) {
	     $(this).css({'background-color': '#FFD9B3'});
	      $('#fencargadoId').val('');
	    }
	  });

    $("#frecibe").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        params.empleados = 'true';
        $.ajax({
            url: base_url + 'panel/usuarios/ajax_get_usuarios/',
            dataType: "json",
            data: params,
            success: function(data) {
                response(data);
            }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#frecibeId").val(ui.item.id);
        $("#frecibe").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
       $(this).css({'background-color': '#FFD9B3'});
        $('#frecibeId').val('');
      }
    });

	  $('#tableCuentas').on('focus', 'input#prod_ddescripcion:not(.ui-autocomplete-input)', function(event) {
	    $(this).autocomplete({
	      source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
	      minLength: 1,
	      selectFirst: true,
	      select: function( event, ui ) {
	        var $this = $(this),
	            $tr = $this.parent().parent();

	        $this.css("background-color", "#B0FFB0");

	        $tr.find('#prod_did_prod').val(ui.item.id);
	      }
	    }).keydown(function(event){
	      if(event.which == 8 || event == 46) {
	        var $tr = $(this).parent().parent();

	        $(this).css("background-color", "#FFD9B3");
	        $tr.find('#prod_did_prod').val('');
	      }
	    });
	  });

	}

	// function onChangeBanamex(e){
	// 	var $this = $(this), $tr = $this.parent().parent();
	// 	if ($this.is(":checked")) {
	// 		$tr.find('input.cuentas_banamex').val('true');
	// 		$tr.find('input.cuentas_sucursal').removeAttr('readonly');
 //      $tr.find('input.cuentas_ref').attr('maxlength', '7');
	// 	}else{
	// 		$tr.find('input.cuentas_banamex').val('false');
	// 		$tr.find('input.cuentas_sucursal').val('').attr('readonly', 'readonly');
 //      $tr.find('input.cuentas_ref').attr('maxlength', '10');
	// 	}
	// }

	function onClickDeleteCuenta(e){
		var $this = $(this), $tr = $this.parent().parent();
		$tr.remove();
	}

	function onKeypressAddRow(event){

		if (event.which === 13) {
		  var $tr = $(this).parent().parent();
			event.preventDefault();

		  if (valida_agregar_cuenta($tr)) {
		    $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);
		    addRowCuenta();
		  } else {
		    $tr.find('.cuentas_alias').focus();
		    $tr.find('td').effect("highlight", {'color': '#da4f49'}, 500);
		    noty({"text": 'Verifique los datos de la fruta.', "layout":"topRight", "type": 'error'});
		  }
		}
	}


	function addRowCuenta(){
		var $tbody = $("#tableCuentas"),
		indexJump = jumpIndex,
		trhtml =
					'<tr>'+
	            '<td>'+
	              '<input type="text" name="prod_ddescripcion[]" class="span12 jump'+(++jumpIndex)+'" value="" id="prod_ddescripcion" data-next="jump'+(++jumpIndex)+'">'+
	              '<input type="hidden" name="prod_did_prod[]" class="span12" value="" id="prod_did_prod">'+
	            '</td>'+
	            '<td><input type="text" name="prod_piso[]" value="" class="prod_piso vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'"></td>'+
	            '<td><input type="text" name="prod_estibas[]" value="" class="prod_estibas vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'"></td>'+
	            '<td><input type="text" name="prod_altura[]" value="" class="prod_altura jump'+jumpIndex+'" maxlength="30" data-next="jump'+(++jumpIndex)+'"></td>'+
	            '<td><input type="text" name="prod_cantidad[]" value="" class="prod_cantidad vpositive jump'+jumpIndex+'"></td>'+
	            '<td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>'+
	        '</tr>';
		$(trhtml).appendTo($tbody);
		$(".vpositive").removeNumeric().numeric({ decimal: true, negative: false }); //Numero entero positivo

		for (i = indexJump, max = jumpIndex; i <= max; i += 1)
			$.fn.keyJump.setElem($('.jump'+i));

		$('.jump'+(indexJump+1)).focus();

	}


	function valida_agregar_cuenta ($tr) {
		if ($tr.find(".prod_cantidad").val() === '' || $tr.find(".prod_estibas").val() == '') {
			return false;
		}
		else return true;
	}

	objr.init = init;
	return objr;
})(jQuery);