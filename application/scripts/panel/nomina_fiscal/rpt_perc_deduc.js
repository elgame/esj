$(function(){
	// Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});
      cargaListaSemanas();
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

  //Autocomplete empleados
  $("#fempleado").autocomplete({
    source: function (request, response) {
      if ($('#did_empresa').val() !== '') {
        $.ajax({
          url: base_url + 'panel/empleados/ajax_get_usuarios/',
          dataType: 'json',
          data: {
            term : request.term,
            did_empresa: $('#did_empresa').val(),
            empleados: 'true'
          },
          success: function (data) {
            response(data);
          }
        });
      } else {
        noty({"text": 'Seleccione una empresa para mostrar sus empleados.', "layout":"topRight", "type": 'error'});
      }
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_empleado").val(ui.item.id);
      $("#fempleado").val(ui.item.label).css({'background-color': '#99FF99'});
      setTimeout(addEmpleado, 200);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $(this).css("background-color", "#FDFC9A");
      $("#fid_empleado").val("");
    }
  });


  $("#btnAddProducto").on('click', addEmpleado);
  $(document).on('click', '.remove_producto', removeProducto);

  $("#anio").on('change', function(event) {
    cargaListaSemanas();
  });
});

function cargaListaSemanas () {
  $.getJSON(base_url+'panel/nomina_fiscal/ajax_get_semana/', {'anio': $("#anio").val(), 'did_empresa': $("#did_empresa").val()},
    function(data){
      var html = '', html2 = '', i;
      for (i in data) {
        html += '<option value="'+data[i].semana+'">'+data[i].semana+' - Del '+data[i].fecha_inicio+' Al '+data[i].fecha_final+'</option>';
        html2 += '<option value="'+data[i].semana+'" '+(data.length-1==i? 'selected': '')+'>'+data[i].semana+' - Del '+data[i].fecha_inicio+' Al '+data[i].fecha_final+'</option>';
      }
      $('#fsemana1').html(html);
      $('#fsemana2').html(html2);
  });
}

function addEmpleado(event){
  var $this = $(this), fid_empleado = $("#fid_empleado"), fempleado = $("#fempleado");
  if (fid_empleado.val() !== '') {
    if ( $('#liempleado'+fid_empleado.val()).length === 0) {
      $("#lista_proveedores").append('<li id="liempleado'+fid_empleado.val()+'"><a class="btn btn-link remove_producto" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
              '<input type="hidden" name="ids_empleados[]" class="ids_empleados" value="'+fid_empleado.val()+'"> '+fempleado.val()+'</li>');
    }else
      noty({"text":"El Empleado ya esta seleccionado", "layout":"topRight", "type":"error"});
    fid_empleado.val("");
    fempleado.val("").css({'background-color': '#fff'}).focus();
  }else
    noty({"text":"Selecciona un Empleado", "layout":"topRight", "type":"error"});
}

function removeProducto(event){
  $(this).parent('li').remove();
}