$(function(){
	cuentas.init();
});


var cuentas = (function($){
	var objr = {};
	var jumpIndex = 0;

	function init(){
		$('#formprovee').keyJump();

		$("#tableCuentas").on('change', '.chk_banamex', onChangeBanamex);
		$("#tableCuentas").on('click', '.delProd', onClickDeleteCuenta);
		$("#tableCuentas").on('keypress', '.cuentas_cuenta', onKeypressAddRow);

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

    centros_costos();
	}

	function onChangeBanamex(e){
		var $this = $(this), $tr = $this.parent().parent();
		if ($this.is(":checked")) {
			$tr.find('input.cuentas_banamex').val('true');
			$tr.find('input.cuentas_sucursal').removeAttr('readonly');
      $tr.find('input.cuentas_ref').attr('maxlength', '7');
		}else{
			$tr.find('input.cuentas_banamex').val('false');
			$tr.find('input.cuentas_sucursal').val('').attr('readonly', 'readonly');
      $tr.find('input.cuentas_ref').attr('maxlength', '10');
		}
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
		    noty({"text": 'Verifique los datos de la cuenta.', "layout":"topRight", "type": 'error'});
		  }
		}
	}


	function addRowCuenta(){
		var $tbody = $("#tableCuentas"),
		indexJump = jumpIndex + 1,
		trhtml = '<tr>'+
				    '<td><input type="checkbox" class="chk_banamex jump'+(++jumpIndex)+'" value="si" checked data-uniform="false" data-next="jump'+(++jumpIndex)+'">'+
				    '	<input type="hidden" name="cuentas_banamex[]" value="true" class="cuentas_banamex">'+
				    '	<input type="hidden" name="cuentas_id[]" value="" class="cuentas_id">'+
				    '</td>'+
				    '<td><select name="fbanco[]" class="fbanco">';
                    $(".fbanco:first option").each(function(index, val) {
                    	trhtml += '<option value="'+$(val).attr('value')+'">'+$(val).text()+'</option>';
                    });
        trhtml +=   '</select></td>'+
				    '<td><input type="text" name="cuentas_alias[]" value="" class="cuentas_alias jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'"></td>'+
				    '<td><input type="text" name="cuentas_sucursal[]" value="" class="cuentas_sucursal vpos-int jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'"></td>'+
				    '<td><input type="text" name="cuentas_cuenta[]" value="" class="cuentas_cuenta vpos-int jump'+jumpIndex+'"></td>'+
            '<td><input type="text" name="cuentas_ref[]" value="" class="cuentas_ref vpos-int jump'+jumpIndex+'" maxlength="7"></td>'+
				    '<td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>'+
				'</tr>';
		$(trhtml).appendTo($tbody);
		$(".vpos-int").removeNumeric().numeric({ decimal: false, negative: false }); //Numero entero positivo

		for (i = indexJump, max = jumpIndex; i <= max; i += 1)
			$.fn.keyJump.setElem($('.jump'+i));

		$('.jump'+(indexJump+1)).focus();

	}


	function valida_agregar_cuenta ($tr) {
		if ($tr.find(".cuentas_alias").val() === '' || $tr.find(".cuentas_cuenta").val() == '') {
			return false;
		}
		else return true;
	}

  function centros_costos() {
    // Autocomplete Empresas
    $("#acentro_costo").autocomplete({
      // source: base_url + 'panel/centro_costo/ajax_get_centro_costo/',
      source: function(request, response) {
        var params = {term : request.term};
        params.tipo = 'gasto';
        $.ajax({
            url: base_url + 'panel/centro_costo/ajax_get_centro_costo/',
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
        var valida = $('#list_centros_costos .centros_costos_id[value='+ui.item.item.id_centro_costo+']');
        if (valida.length == 0) {
          var vhtml = '<li class="new"><i class="icon-minus-sign delete_costo" style="cursor: pointer;" title="Quitar"></i> '
                        +ui.item.item.nombre+
                        '<input type="hidden" name="centros_costos[]" class="centros_costos_id" value="'+ui.item.item.id_centro_costo+'">'+
                        '<input type="hidden" name="centros_costos_del[]" class="centros_costos_del" value="false">'+
                      '</li>';
          $("#list_centros_costos").append(vhtml);
        } else {
          alert('El centro de costo ya esta agregado.')
        }
        setTimeout(function() {
          $("#acentro_costo").val('');
        }, 100)
      }
    }).keydown(function(e){
      if (e.which === 8) {
        // $(this).css({'background-color': '#FFD9B3'});
      }
    });

    $('#list_centros_costos').on('click', '.delete_costo', function(event) {
      var $li = $(this).parent();
      if ($li.is('.new')) {
        $li.remove();
      } else {
        $li.find('.centros_costos_del').val('true');
      }
    });
  }

	objr.init = init;
	return objr;
})(jQuery);