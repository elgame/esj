$(function(){
  // Autocomplete Empresas
  $("#ftrabajador").autocomplete({
    source: base_url + 'panel/usuarios/ajax_get_usuarios/?empleados=si',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_trabajador").val(ui.item.id);
      $("#ftrabajador").val(ui.item.label).css({'background-color': '#99FF99'});

      if ($('#fsalario_real').length > 0) {
        $('#fsalario_real').val(ui.item.item.salario_diario_real);
      }

      if ($('#ffecha1').length > 0) {
        $('#ffecha1').val(ui.item.item.fecha_entrada);
      }

      if ($('#ffecha2').length > 0 && ui.item.item.fecha_salida !== null) {
        $('#ffecha2').val(ui.item.item.fecha_salida);
      }
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#fid_trabajador').val('');

      if ($('#fsalario_real').length > 0) {
        $('#fsalario_real').val('');
      }

      if ($('#ffecha1').length > 0) {
        $('#ffecha1').val('');
      }

      if ($('#ffecha2').length > 0) {
        $('#ffecha2').val('');
      }
    }
  });

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

});