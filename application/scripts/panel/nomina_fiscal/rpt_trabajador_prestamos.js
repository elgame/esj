$(function(){
  // Autocomplete Empresas
  $("#ftrabajador").autocomplete({
    source: base_url + 'panel/usuarios/ajax_get_usuarios/?empleados=si',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_trabajador").val(ui.item.id);
      $("#ftrabajador").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#fid_trabajador').val('');
    }
  });
});