$(function(){
	calidades.init();
	clasificaciones.init();
});

var calidades = (function($){
	var objr = {},
	acal_nombre, acal_precio, acal_agregar,
	contador=0;

	function init(){
		acal_nombre = $("#acal_nombre");
		acal_precio = $("#acal_precio");
		acal_agregar = $("#acal_agregar");

		acal_nombre.keydown(addCalidadTable_kdown);
		acal_precio.keydown(addCalidadTable_kdown);
		acal_agregar.on('touchstart click', addCalidadTable)

		$(document).on('touchstart click', '.cal_remove', removeCalidadTabla);
	}

	function addCalidadTable_kdown(e){
		if(e.keyCode == 13){
			addCalidadTable();

			event.preventDefault();
      return false;
		}
	}

	function addCalidadTable(){
		if (acal_nombre.val() != '' && acal_precio.val() != '') {
			$("#acal_body").append('<tr id="acal_r'+contador+'">'+
								  '	<td><input type="text" class="span12" name="cal_nombre[]" value="'+acal_nombre.val()+'" maxlength="40" required></td>'+
									'	<td><input type="text" class="span8 vpositive" name="cal_precio[]" value="'+acal_precio.val()+'" maxlength="11" required></td>'+
									'	<td><button type="button" class="btn btn-danger cal_remove" data-row="'+contador+'"><i class="icon-remove"></i></button></td>'+
								  '</tr>');
			$("#acal_r"+contador+" .vpositive").numeric({ negative: false });
			acal_nombre.val("").focus();
			acal_precio.val("");
		}else
			noty({"text":"El nombre y el precio son requeridos para agregarse a la lista", "layout":"topRight", "type":"error"});
	}

	function removeCalidadTabla(){
		$("#acal_r"+$(this).attr("data-row")).remove();
	}

	objr.init = init;

	return objr;
})(jQuery);


var clasificaciones = (function($){
	var objr = {},
	acla_nombre, acla_precio, acla_agregar, acla_cuenta,
	contador=0;

	function init(){
		acla_nombre = $("#acla_nombre");
		acla_precio = $("#acla_precio");
		acla_cuenta = $("#acla_cuenta");
		acla_agregar = $("#acla_agregar");

		acla_nombre.keydown(addClasificacionTable_kdown);
		acla_precio.keydown(addClasificacionTable_kdown);
		acla_cuenta.keydown(addClasificacionTable_kdown);
		acla_agregar.on('touchstart click', addClasificacionTable)

		$(document).on('touchstart click', '.cla_remove', removeClasificacionTabla);
	}

	function addClasificacionTable_kdown(e){
		if(e.keyCode == 13){
			addClasificacionTable();

			event.preventDefault();
      return false;
		}
	}

	function addClasificacionTable(){
		if (acla_nombre.val() != '' && acla_precio.val() != '') {
			$("#acla_body").append('<tr id="acla_r'+contador+'">'+
								  '	<td><input type="text" class="span12" name="cla_nombre[]" value="'+acla_nombre.val()+'" maxlength="40" required></td>'+
									'	<td><input type="text" class="span8 vpositive" name="cla_precio[]" value="'+acla_precio.val()+'" maxlength="11" required></td>'+
									'	<td><input type="text" class="span8 vpositive" name="cla_cuenta[]" value="'+acla_cuenta.val()+'" maxlength="11"></td>'+
									'	<td><button type="button" class="btn btn-danger cla_remove" data-row="'+contador+'"><i class="icon-remove"></i></button></td>'+
								  '</tr>');
			$("#acla_r"+contador+" .vpositive").numeric({ negative: false });
			acla_nombre.val("").focus();
			acla_precio.val("");
			acla_cuenta.val("");
		}else
			noty({"text":"El nombre y el precio son requeridos para agregarse a la lista", "layout":"topRight", "type":"error"});
	}

	function removeClasificacionTabla(){
		$("#acla_r"+$(this).attr("data-row")).remove();
	}

	objr.init = init;

	return objr;
})(jQuery);