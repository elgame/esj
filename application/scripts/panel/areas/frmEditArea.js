$(function(){
	edit_calidades.init();
	edit_clasificacion.init();
	edit_calidades_ventas.init();
	edit_tamanio_ventas.init();
});

var edit_calidades = (function($){
	var objr = {};

	function init(){
		$("#frm_fcalidades").submit(function(){
			getCalidades();
			return false;
		});
	}

	function getCalidades(pag){
		loader.create();

		var param = {
			"id":      $("#id_calidad").val(),
			"fnombre": $("#calidades_fnombre").val(),
			"fstatus": $("#calidades_fstatus").val(),
			"pag":     (pag!=undefined? pag: 0 ),
		};

		$.getJSON(base_url+"panel/areas/calidades/", param, function(data){
			if(data.response.ico == 'success'){
				$("#content_calidades").html(data.data);
			}
		}).always(function() { loader.close(); });
	}

	function changePage(pag){
		getCalidades( (pag? pag: 0) );
	}

	objr.init = init;
	objr.page = changePage;

	return objr;
})(jQuery);



var edit_clasificacion = (function($){
	var objr = {};

	function init(){
		$("#frm_clasificaciones").submit(function(){
			getCalidades();
			return false;
		});

    autocompleteProdServ();
    autocompleteUnidad();
    autocompleteEmpresa();
    addCuenta();
	}

	function getCalidades(pag){
		loader.create();

		var param = {
			"id":      $("#id_calidad").val(),
			"fnombre": $("#clasificaciones_fnombre").val(),
			"fstatus": $("#clasificaciones_fstatus").val(),
			"pag":     (pag!=undefined? pag: 0 ),
		};

		$.getJSON(base_url+"panel/areas/clasificaciones/", param, function(data){
			if(data.response.ico == 'success'){
				$("#content_clasificacion").html(data.data);
			}
		}).always(function() { loader.close(); });
	}

	function changePage(pag){
		getCalidades( (pag? pag: 0) );
	}

  function autocompleteProdServ() {
    $("#dclave_producto").autocomplete({
      source: base_url+'panel/catalogos33/claveProdServ/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#dclave_producto_cod").val(ui.item.id);
        $("#dclave_producto").css("background-color", "#B0FFB0");
      }
    }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $("#dclave_producto").css("background-color", "#FFD9B3");
        $("#dclave_producto_cod").val("");
      }
    });
  }

  function autocompleteUnidad() {
    $("#dclave_unidad").autocomplete({
      source: base_url+'panel/catalogos33/claveUnidad/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#dclave_unidad_cod").val(ui.item.id);
        $("#dclave_unidad").css("background-color", "#B0FFB0");
      }
    }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $("#dclave_unidad").css("background-color", "#FFD9B3");
        $("#dclave_unidad_cod").val("");
      }
    });
  }

  function autocompleteEmpresa() {
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
    }).attr('autocomplete', 'nope');;
  }

  var keyCuentas = 0;
  function addCuenta() {
    keyCuentas = $('#listasCuentas li').length;

    $('#btnAddCuenta').click(function(event) {
      if (validaCuenta()) {
        var html = '<li>'+ $('#fcuenta_cpi2').val() +' - '+ $('#fempresa').val() + ' <i class="icon-remove" style="cursor:pointer"></i>'+
                        '<input type="hidden" name="fcuentas['+ keyCuentas +'][id]" value="'+ $('#did_empresa').val() +'" class="id">'+
                        '<input type="hidden" name="fcuentas['+ keyCuentas +'][empresa]" value="'+ $('#fempresa').val() +'" class="empresa">'+
                        '<input type="hidden" name="fcuentas['+ keyCuentas +'][cuenta]" value="'+ $('#fcuenta_cpi2').val() +'" class="cuenta">'+
                      '</li>';
        $('#listasCuentas ul').append(html);
        ++keyCuentas;
        $('#did_empresa').val('');
        $('#fempresa').val('');
        $('#fcuenta_cpi2').val('').focus();
      }
    });

    $(document).on('click', '#listasCuentas .icon-remove', function(event) {
      $(this).parent().remove();
    });
  }

  function validaCuenta() {
    if ($.trim($('#fcuenta_cpi2').val()) == '' || $.trim($('#did_empresa').val()) == '') {
      alert('Ingresa los datos de la cuenta');
      return false;
    }

    var msg = '';
    $('#listasCuentas li').each(function(index, el) {
      var cuenta = $(this).find('.cuenta').val();
      var id = $(this).find('.id').val();
      console.log('test', $(this));

      if ($.trim($('#fcuenta_cpi2').val()) == cuenta) {
        msg = 'La cuenta ya esta agregada en una empresa';
      }

      if ($.trim($('#did_empresa').val()) == id) {
        msg += (msg.length>0? "\n": '') + 'La empresa ya tiene asignada una cuenta.';
      }

      if (msg.length > 0) return false;
    });

    if (msg.length > 0) {
      alert(msg);
      return false;
    }

    return true;
  };

	objr.init = init;
	objr.page = changePage;

	return objr;
})(jQuery);


var edit_calidades_ventas = (function($){
	var objr = {};

	function init(){
		$("#frm_fcalidades_ventas").submit(function(){
			getCalidades();
			return false;
		});
	}

	function getCalidades(pag){
		loader.create();

		var param = {
			"id":      $("#id_calidad").val(),
			"fnombre": $("#calidades_ventas_fnombre").val(),
			"fstatus": $("#calidades_ventas_fstatus").val(),
			"pag":     (pag!=undefined? pag: 0 ),
		};

		$.getJSON(base_url+"panel/areas_otros/calidades/", param, function(data){
			if(data.response.ico == 'success'){
				$("#content_calidades_ventas").html(data.data);
			}
		}).always(function() { loader.close(); });
	}

	function changePage(pag){
		getCalidades( (pag? pag: 0) );
	}

	objr.init = init;
	objr.page = changePage;

	return objr;
})(jQuery);


var edit_tamanio_ventas = (function($){
	var objr = {};

	function init(){
		$("#frm_tamanios").submit(function(){
			getCalidades();
			return false;
		});
	}

	function getCalidades(pag){
		loader.create();

		var param = {
			"id":      $("#id_calidad").val(),
			"fnombre": $("#tamanios_fnombre").val(),
			"fstatus": $("#tamanios_fstatus").val(),
			"pag":     (pag!=undefined? pag: 0 ),
		};

		$.getJSON(base_url+"panel/areas_otros/tamanos/", param, function(data){
			if(data.response.ico == 'success'){
				$("#content_tamanios").html(data.data);
			}
		}).always(function() { loader.close(); });
	}

	function changePage(pag){
		getCalidades( (pag? pag: 0) );
	}

	objr.init = init;
	objr.page = changePage;

	return objr;
})(jQuery);

