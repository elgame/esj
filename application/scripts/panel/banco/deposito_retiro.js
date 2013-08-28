$(function(){
	//si es trapaso
	$("#ftraspaso").change(function(){
		if($(this).is(":checked")){
			$("#campos_traspaso").show();
		}else{
			$("#campos_traspaso").hide();

			$("#txtBanco_destino option:first").attr("selected", true);
			$("#txtCuenta_destino").html("");
		}
	});

	//Evento al cambiar un banco
	$("#fbanco").change(function(){
		changeBanco("#fcuenta", $(this));
	});
	//Evento al cambiar un banco destino traspaso
	$("#txtBanco_destino").change(function(){
		changeBanco("#txtCuenta_destino", $(this));
	});

	//cuando cambian de cuenta que actualize el numero de referencia en cheques
	$("#fcuenta").change(function(){
		chageValMonto();
		// getRefCheque($("#fmetodo_pago"));
	});

	// //cuando cambian el metodo pago, si es cheque carga el numero de referencia
	// $("#fmetodo_pago").change(function(){
	// 	getRefCheque($(this));
	// });

	//Autocomplete de Productores
	if($("#did_proveedor").length > 0){
		$("#dempresa").autocomplete({
	      source: base_url+'panel/empresas/ajax_get_empresas/',
	      minLength: 1,
	      selectFirst: true,
	      select: function( event, ui ) {
	        $("#did_empresa").val(ui.item.id);
	        $("#dempresa").css("background-color", "#B0FFB0");
	      }
	  }).on("keydown", function(event){
	      if(event.which == 8 || event == 46){
	        $("#dempresa").css("background-color", "#FFD9B3");
	        $("#did_empresa").val("");
	      }
	  });
	}else if($("#did_cliente").length > 0){
		//Autocomplete clientes
		$("#dcliente").autocomplete({
	      source: base_url+'panel/clientes/ajax_get_proveedores/',
	      minLength: 1,
	      selectFirst: true,
	      select: function( event, ui ) {
	        $("#did_cliente").val(ui.item.id);
	        $("#dcliente").css("background-color", "#B0FFB0");
	      }
	  }).on("keydown", function(event){
	      if(event.which == 8 || event == 46){
	        $("#dcliente").css("background-color", "#FFD9B3");
	        $("#did_cliente").val("");
	      }
	  });
	}

	$("#fmetodo_pago").change(function(){
		var vvth = $(this);
		$("#autcom_proveedor, #id_proveedor").removeAttr("required");
		if (vvth.val() == 'cheque'){
			$("#autcom_proveedor, #id_proveedor").attr("required", "required");
		};
	});

	if(document.getElementById('linkcheque')!=null){
		$("#linkcheque").click(function(){
			var win = window.open($(this).attr('href'), '_blank');
			win.focus();
			return false;
		}).click();
	}

});

function changeBanco(cuenta, vthis){
	$.getJSON(base_url+'panel/banco/get_cuentas_banco/', {'id_banco': vthis.val() }, function(resp){
		$(cuenta).html("");
		if (resp.cuentas.length > 0) {
			var selcuentas = '';
			for (var i = 0; i < resp.cuentas.length; i++) {
				selcuentas += '<option value="'+resp.cuentas[i].id_cuenta+'" data-saldo="'+resp.cuentas[i].saldo+'">'+resp.cuentas[i].alias+' - '+util.darFormatoNum(resp.cuentas[i].saldo)+'</option>';
			};
			$(cuenta).append(selcuentas);

			chageValMonto();
			// getRefCheque($("#fmetodo_pago")); //cambia el numer referencia cheque
		}else
			noty({"text":"No hay cuentas para el banco seleccionado", "layout":"topRight", "type":"error"});
	});
}

function chageValMonto(){
	if($("#did_proveedor").length > 0)
		$("#fmonto").attr("max", $("#fcuenta option:selected").attr("data-saldo"));
}

/**
 * obtiene el numero de referencia en los cheques cuando se hace un retiro
 * @param  {[type]} vthis [description]
 * @return {[type]}       [description]
 */
function getRefCheque(vthis){
	$("#txtNumeroRef").val('');
	if(vthis.val() == 'cheque'){
		var cuenta = $("#fcuenta").val();
		if(cuenta){
			$.getJSON(URLB+"index.php/admin/banco/getRefRetirosCheque/"+cuenta, function(resp){
				$("#txtNumeroRef").val(resp.num_ref);
			});
		}else
			alert("Selecciona una cuenta");
	}
}