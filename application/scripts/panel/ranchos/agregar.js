$(function(){
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

  // Autocomplete areas
  $("#farea").autocomplete({
    source: base_url + 'panel/areas/ajax_get_areas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_area").val(ui.item.id);
      $("#farea").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e) {
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_area').val('');
    }
  });

});
