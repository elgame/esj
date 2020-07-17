var empresaId = '';
$(function(){
	//marcar y desmarcar los checks box
	eventoCheck();

	// Autocomplete Empresas
	$("#fempresa").autocomplete({
		source: base_url + 'panel/empresas/ajax_get_empresas/',
		minLength: 1,
		selectFirst: true,
		select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#fempresa").val(ui.item.label).css({'background-color': '#99FF99'});
      cargaDepaPues();
		}
	}).keydown(function(e){
		if (e.which === 8) {
			$(this).css({'background-color': '#FFD9B3'});
			$('#did_empresa').val('');
		}
	});

	$("#festa_asegurado").on('change', function(){
		campos_requeridos($(this));
	});
	campos_requeridos($("#festa_asegurado"));

  getPrivilegiosEmpresa();
  autocompleteCultivo();

  getEmpresasIds($('#btnCopiar'));
});

function cargaDepaPues () {
	$.getJSON(base_url+'panel/empleados/ajax_get_depa_pues/', {'did_empresa': $("#did_empresa").val()},
		function(data){
      var html = '', i;
      // console.log(data);
      for (i in data.departamentos) {
        html += '<option value="'+data.departamentos[i].id_departamento+'">'+data.departamentos[i].nombre+'</option>';
      }
			$('#fdepartamente').html(html);

      html = '';
      for (i in data.puestos) {
        html += '<option value="'+data.puestos[i].id_puesto+'">'+data.puestos[i].nombre+'</option>';
      }
      $('#fpuesto').html(html);
	});
}

function campos_requeridos ($this) {
	if($this.is(':checked'))
	{
		$("#frfc, #fcurp, #ffecha_entrada, #fsalario_diario, #fsalario_diario_real, #fregimen_contratacion").attr("required", "required");
	}else
		$("#frfc, #fcurp, #ffecha_entrada, #fsalario_diario, #fsalario_diario_real, #fregimen_contratacion").removeAttr("required");
}

function getPrivilegiosEmpresa() {
  empresaId = $("#id_empresa").val();
  $("#id_empresa").change(function() {
    guardarPrivilegios();
  });
}

function guardarPrivilegios() {
  var params = {
    id_usuario: $("#usuarioId").val(),
    id_empresa: empresaId,
    dprivilegios: []
  };

  $("input[name='dprivilegios[]']:checked").each(function(index, el) {
    params.dprivilegios.push($(this).val());
  });

  $.post(base_url+'panel/usuarios/ajax_update_priv/',
    params, function(data, textStatus, xhr) {
      var params = {
        'id_usuario': $("#usuarioId").val(),
        'id_empresa': $("#id_empresa").val(),
      };
      $.get(base_url+'panel/usuarios/ajax_get_usuario_priv/', params,
        function(data){
          empresaId = $("#id_empresa").val();

          $('#list_privilegios').html(data);

          $(".treeview").treeview({
            persist: "location",
            unique: true
          });

          eventoCheck();

      });
  });

  console.log(params);
}

function eventoCheck() {
  $("#list_privilegios .treeview input:checkbox").on('click', function (){
    var elemento_padre = $($(this).parent().get(0)).parent().get(0);
    var numero_hijos = $("ul", elemento_padre).length;

    if($("#dmod_privilegios").length > 0)
      $("#dmod_privilegios").val('si');

    if(numero_hijos > 0){
      $("input:checkbox", elemento_padre).attr("checked", ($(this).attr("checked")? true: false));
    }
  });
}

var autocompleteCultivo = function () {
  $("#area").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
      $.ajax({
          url: base_url + 'panel/areas/ajax_get_areas/',
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
      var $area =  $(this);
      $area.css("background-color", "#A1F57A");
      $("#areaId").val(ui.item.id);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#area").css("background-color", "#FFD071");
      $("#areaId").val('');
    }
  });
};

function getEmpresasIds ($button, $modal) {
  console.log('test', $button, $modal);
  var ide   = $('#id_empresa').val(),
      idu   = $('#usuarioId').val(),
      exist = false,
      ids   = [];

  $button.attr('href', base_url + 'panel/usuarios/copiar_privilegios/?idu='+idu+'&ide='+ide);
  if ($modal) {
    $modal.modal('show');
  }
}