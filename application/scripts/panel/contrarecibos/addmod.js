$(function(){
	cuentas.init();
});


var cuentas = (function($){
	var objr = {};
	var jumpIndex = 0;

	function init(){
		$('#formprovee').keyJump();

		$("#tableCuentas").on('change', '.facturas_importe', calculaTotal);
		$("#tableCuentas").on('click', '.delProd', onClickDeleteCuenta);
		$("#tableCuentas").on('keypress', '.facturas_observacion', onKeypressAddRow);

		// Autocomplete Empresas
		$("#fempresa").autocomplete({
			source: base_url + 'panel/empresas/ajax_get_empresas/',
			minLength: 1,
			selectFirst: true,
			select: function( event, ui ) {
				$("#did_empresa").val(ui.item.id);
				$("#fempresa").val(ui.item.label).css({'background-color': '#99FF99'});
			}
		}).keydown(function(e){
			if (e.which === 8) {
				$(this).css({'background-color': '#FFD9B3'});
				$('#did_empresa').val('');
			}
		});

		$("#fproveedor").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#did_empresa").val()) > 0)
          params.did_empresa = $("#did_empresa").val();
        $.ajax({
            url: base_url + 'panel/proveedores/ajax_get_proveedores/',
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
        var $proveedor =  $(this);

        $proveedor.val(ui.item.id);
        $("#did_proveedor").val(ui.item.id);
        $proveedor.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#fproveedor").css("background-color", "#FFD071");
        $("#did_proveedor").val('');
      }
    });
	}

	function onClickDeleteCuenta(e){
		var $this = $(this), $tr = $this.parent().parent();
		$tr.remove();
		calculaTotal();
	}

	function onKeypressAddRow(event){

		if (event.which === 13) {
		  var $tr = $(this).parent().parent();
			event.preventDefault();

		  if (valida_agregar_cuenta($tr)) {
		    $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);
		    addRowCuenta();
		  } else {
		    $tr.find('.facturas_folio').focus();
		    $tr.find('td').effect("highlight", {'color': '#da4f49'}, 500);
		    noty({"text": 'Verifique los datos.', "layout":"topRight", "type": 'error'});
		  }
		}
	}


	function addRowCuenta(){
		var $tbody = $("#tableCuentas"),
		indexJump = jumpIndex + 1,
		trhtml = '<tr>'+
								'<td><input type="text" name="facturas_folio[]" value="" class="facturas_folio jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'"></td>'+
								'<td><input type="date" name="facturas_fecha[]" value="" class="facturas_fecha jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'"></td>'+
								'<td><input type="text" name="facturas_importe[]" value="" class="facturas_importe vnumeric jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'"></td>'+
								'<td><input type="text" name="facturas_observacion[]" value="" class="facturas_observacion jump'+jumpIndex+'" maxlength="200"></td>'+
								'<td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>'+
							'</tr>';

		$(trhtml).appendTo($tbody);
		$(".vnumeric").removeNumeric().numeric({ decimal: true, negative: false }); //Numero entero positivo

		for (i = indexJump, max = jumpIndex; i <= max; i += 1)
			$.fn.keyJump.setElem($('.jump'+i));

		$('.jump'+(indexJump)).focus();
		calculaTotal();
	}

	function calculaTotal() {
		var dtotal = 0;
		$("#tableCuentas input.facturas_importe").each(function(index, el) {
			dtotal += parseFloat($(this).val())||0;
		});
		$("#dtotal").val(dtotal.toFixed(2));
	}


	function valida_agregar_cuenta ($tr) {
		if ($tr.find(".facturas_folio").val() === '' || $tr.find(".facturas_fecha").val() == '' || $tr.find(".facturas_importe").val() == '') {
			return false;
		}
		else return true;
	}

	objr.init = init;
	return objr;
})(jQuery);