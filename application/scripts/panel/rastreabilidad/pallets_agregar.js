$(function(){

  $('#form-search').keyJump({
    'next': 13,
  });

  // Autocomplete Empresas
  $("#fclasificacion").autocomplete({
    source: base_url + 'panel/areas/ajax_get_clasificaciones/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_clasificacion").val(ui.item.id);
      $("#fclasificacion").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#fid_clasificacion').val('');
    }
  });

});

