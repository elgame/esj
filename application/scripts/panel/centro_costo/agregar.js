$(function(){
  // Autocomplete Empresas
  $("#fempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#fempresa").val(ui.item.label).css({'background-color': '#99FF99'});
      $("#id_cuenta").val('');
      $("#cuenta").val('');
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
      $("#id_cuenta").val('');
      $("#cuenta").val('');
    }
  });

  // // Autocomplete areas
  // $("#farea").autocomplete({
  //   source: base_url + 'panel/areas/ajax_get_areas/',
  //   minLength: 1,
  //   selectFirst: true,
  //   select: function( event, ui ) {
  //     $("#did_area").val(ui.item.id);
  //     $("#farea").val(ui.item.label).css({'background-color': '#99FF99'});
  //   }
  // }).keydown(function(e) {
  //   if (e.which === 8) {
  //     $(this).css({'background-color': '#FFD9B3'});
  //     $('#did_area').val('');
  //     $("#id_cuenta").val(ui.item.id);
  //     $("#cuenta").val(ui.item.label).css({'background-color': '#99FF99'});
  //   }
  // });

  $("#cuenta").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
      $.ajax({
          url: base_url + 'panel/banco/ajax_get_cuentas/',
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
      $("#id_cuenta").val(ui.item.id);
      $("#cuenta").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#id_cuenta').val('');
    }
  });

  $('#tipo').on('change', function(event) {
    var tipo = $(this).val();
    $("#farea, #hectareas, #no_plantas, #anios_credito").val('');

    if (tipo == 'melga' || tipo == 'tabla' || tipo == 'seccion') {
      $('#is_lotes').show();
    } else {
      $('#is_lotes').hide();
    }

    if (tipo == 'creditobancario') {
      $('#is_credito').show();
    } else {
      $('#is_credito').hide();
    }

    if (tipo == 'banco') {
      $('#is_cuenta').show();
    } else {
      $('#is_cuenta').hide();
    }
  });

});
