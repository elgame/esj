$(function(){
	// Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

  // Autocomplete empleados
  $("#dempleado").autocomplete({
    source: function(request, response) {
      var params = {term : request.term, empleados: 'true'};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
      $.ajax({
          url: base_url + 'panel/empleados/ajax_get_usuarios2/',
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
      $("#did_empleado").val(ui.item.id);
      $("#dempleado").val(ui.item.label).css({'background-color': '#99FF99'});
      setTimeout(addProveedor, 200, ui.item);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empleado').val('');
    }
  });

  // $("#btnAddProveedor").on('click', addProveedor);
  $(document).on('click', '.remove_proveedor', removeProveedor);

  $("#form").submit(function(){
    if ($("#lista tr").length > 0) {
      return true;
    }else{
      noty({"text":"Selecciona al menos un Trabajador", "layout":"topRight", "type":"error"});
      return false;
    }
  });

  // Eventos para cambiar el salarios
  $("#sueldo_sdi").on('keyup', function(event) {
    if (event.which !== 13 && $(this).val()!=='')
      $(".change_sdi").val($(this).val());
  }).on('keydown', function(event) {
    if (event.which == 13)
      event.preventDefault();
  });
  $("#sueldo_sr").on('keyup', function(event) {
    if (event.which !== 13 && $(this).val()!=='')
      $(".change_sr").val($(this).val());
  }).on('keydown', function(event) {
    if (event.which == 13)
      event.preventDefault();
  });
  $("#remove_all").on('click', function(event) {
    $("#lista").html("");
  });

  $('#form').keyJump();
});

function addProveedor(data){
  var $this = $(this), did_proveedor = $("#did_empleado"), dproveedor = $("#dempleado"),
    tipo, salario_diario=0, factor_integracion = 0, clase_sdi = '';
  if (did_proveedor.val() !== '') {
    if ( $('#empleado'+data.item.id).length === 0) {
      if (data.item.esta_asegurado=='f'){
        tipo = 'No asegurado';
      }else{
        tipo = 'Asegurado';
        salario_diario = data.nomina.nomina.salario_diario_integrado;
        factor_integracion = data.nomina.factor_integracion;
        clase_sdi = 'change_sdi';
      }
      $("#lista").append('<tr id="empleado'+data.item.id+'">'+
                        '<td>'+data.item.nombre+' '+data.item.apellido_paterno+' '+data.item.apellido_materno+'<input type="hidden" name="id_empledo[]" value="'+data.item.id+'">'+
                        '   <input type="hidden" name="factor_integracion[]" value="'+factor_integracion+'">'+
                        '</td>'+
                        '<td>'+tipo+'<input type="hidden" name="tipo[]" value="'+data.item.esta_asegurado+'"></td>'+
                        '<td><span class="span3 pull-left">'+salario_diario+'</span> <input type="text" name="sueldo_diario[]" value="'+salario_diario+'" class="span5 pull-left vpositive '+clase_sdi+'" maxlength="9" '+(data.item.esta_asegurado=='f'?'readonly':'')+'></td>'+
                        '<td><span class="span3 pull-left">'+data.item.salario_diario_real+'</span> <input type="text" name="sueldo_real[]" value="'+data.item.salario_diario_real+'" class="span5 pull-left vpositive change_sr" maxlength="9"></td>'+
                        '<td><a class="btn btn-link remove_proveedor" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a></td>'+
                      '</tr>');
      $('#form').keyJump();
      $("#empleado"+data.item.id+" .vpositive").numeric({ negative: false });
    }else
      noty({"text":"El Trabajador ya esta seleccionado", "layout":"topRight", "type":"error"});
    did_proveedor.val("");
    dproveedor.val("").css({'background-color': '#fff'}).focus();
  }else
    noty({"text":"Selecciona un Trabajador", "layout":"topRight", "type":"error"});
}

function removeProveedor(event){
  $(this).parents('tr').remove();
}

