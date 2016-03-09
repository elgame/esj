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
		$("#tableCuentas").on('keypress', '.showCodigoAreaAuto', onKeypressAddRow);

		$("#fhorometro_ini, #fhorometro_fin").on('keyup', onKeyPressHorometro);
		$("#fhr_ini, #fhr_fin").on('keyup', onKeyPressHoras);

		// // Autocomplete para los Vehiculos.
  //   $("#fvehiculo").autocomplete({
  //     source: base_url + 'panel/vehiculos/ajax_get_vehiculos/',
  //     minLength: 1,
  //     selectFirst: true,
  //     select: function( event, ui ) {
  //       var $vehiculo =  $(this);

  //       // $vehiculo.val(ui.item.id);
  //       $("#vehiculoId").val(ui.item.id);
  //       $vehiculo.css("background-color", "#A1F57A");
  //     }
  //   }).on("keydown", function(event) {
  //     if(event.which == 8 || event.which == 46) {
  //       $("#fvehiculo").css("background-color", "#FFD071");
  //       $("#vehiculoId").val('');
  //     }
  //   });

    $("#foperador").autocomplete({
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
	      $("#foperadorId").val(ui.item.id);
	      $("#foperador").val(ui.item.label).css({'background-color': '#99FF99'});
	    }
	  }).keydown(function(e){
	    if (e.which === 8) {
	     $(this).css({'background-color': '#FFD9B3'});
	      $('#foperadorId').val('');
	    }
	  });

	  // $("#fencargado").autocomplete({
	  //   source: function(request, response) {
	  //     var params = {term : request.term};
	  //     // if(parseInt($("#fid_empresa").val()) > 0)
	  //     //   params.did_empresa = $("#fid_empresa").val();
	  //     params.empleados = 'true';
	  //     $.ajax({
	  //         url: base_url + 'panel/usuarios/ajax_get_usuarios/',
	  //         dataType: "json",
	  //         data: params,
	  //         success: function(data) {
	  //             response(data);
	  //         }
	  //     });
	  //   },
	  //   minLength: 1,
	  //   selectFirst: true,
	  //   select: function( event, ui ) {
	  //     $("#fencargadoId").val(ui.item.id);
	  //     $("#fencargado").val(ui.item.label).css({'background-color': '#99FF99'});
	  //   }
	  // }).keydown(function(e){
	  //   if (e.which === 8) {
	  //    $(this).css({'background-color': '#FFD9B3'});
	  //     $('#fencargadoId').val('');
	  //   }
	  // });

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

	  // Autocomplete labores live
    $('#tableCuentas').on('focus', 'input.showLabores:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/labores_codigo/ajax_get_labores/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $tr.find('#plaborId').val(ui.item.id);
          $this.css("background-color", "#B0FFB0");
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $this = $(this), $tr = $this.parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#plaborId').val('');
        }
      });
    });

	}

	function onKeyPressHorometro(e){
		var fhorometro_ini = parseFloat($("#fhorometro_ini").val())||0,
				fhorometro_fin = parseFloat($("#fhorometro_fin").val())||0,
				fhorometro_total = $("#fhorometro_total");
		fhorometro_total.val((fhorometro_fin - fhorometro_ini).toFixed(2));
	}

	function onKeyPressHoras(e){
		var fhr_ini = $("#fhr_ini").val(),
				fhr_fin = $("#fhr_fin").val(),
				fhr_total = $("#fhr_total"),
				res_hrs = util.restarHoras(fhr_ini, fhr_fin);
		fhr_total.val( (res_hrs? res_hrs: '') );
	}

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
		    noty({"text": 'Verifique los datos de la labor.', "layout":"topRight", "type": 'error'});
		  }
		}
	}


	function addRowCuenta(){
		var $tbody = $("#tableCuentas"),
		indexJump = jumpIndex,
		trhtml =
					'<tr>'+
              '<td>'+
                '<input type="time" name="ptiempo[]" class="span12 jump'+(++jumpIndex)+'" value="" id="ptiempo" data-next="jump'+(++jumpIndex)+'">'+
              '</td>'+
              '<td>'+
                '<input type="text" name="plabor[]" value="" class="span12 prod_piso showLabores jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
                '<input type="hidden" name="plaborId[]" id="plaborId" value="" class="span12 hideLabor">'+
              '</td>'+
              '<td>'+
                '<input type="text" name="ccosto[]" value="" id="codigoArea" class="span12 showCodigoAreaAuto jump'+jumpIndex+'">'+
                '<input type="hidden" name="ccostoId[]" value="" id="codigoAreaId" class="span12">'+
                '<input type="hidden" name="ccostoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12">'+
                '<i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>'+
              '</td>'+
              '<td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>'+
          '</tr>';
		$(trhtml).appendTo($tbody);
		// $(".vpositive").removeNumeric().numeric({ decimal: true, negative: false }); //Numero entero positivo

		for (i = indexJump, max = jumpIndex; i <= max; i += 1)
			$.fn.keyJump.setElem($('.jump'+i));

		$('.jump'+(indexJump+1)).focus();

	}


	function valida_agregar_cuenta ($tr) {
		// if ($tr.find(".prod_cantidad").val() === '' || $tr.find(".prod_estibas").val() == '') {
		// 	return false;
		// }
		// else
		return true;
	}

	objr.init = init;
	return objr;
})(jQuery);