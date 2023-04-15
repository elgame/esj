$(function(){

  $('#form-search').keyJump({
    'next': 13,
  });

  if($("#fcalidad").length > 0){
    $("#farea").on('change', function(){
      $.getJSON(base_url+'panel/areas/ajax_get_calidades', {'area': $(this).val()}, function(res){
        var calidades = '';
        if(res.calidades.length > 0){
          for (var i = 0; i < res.calidades.length; i++) {
            calidades += '<option value="'+res.calidades[i].id_calidad+'">'+res.calidades[i].nombre+'</option>';
          };
        }
        $("#fcalidad").html(calidades);
      });
    });
  }

  // Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});

      if ($('.comprasxproductos').length > 0) {
        getFamilias(ui.item.id);
      }
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });
});

