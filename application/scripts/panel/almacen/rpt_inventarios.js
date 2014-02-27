$(function(){
	// Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});
      cargaListaFamlias(ui.item.id);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

  //Autocomplete productos
  $("#fproducto").autocomplete({
    source: function (request, response) {
      if ($('#did_empresa').val()!='') {
        $.ajax({
          url: base_url + 'panel/compras_ordenes/ajax_producto/',
          dataType: 'json',
          data: {
            term : request.term,
            ide: $('#did_empresa').val(),
            tipo: 'p'
          },
          success: function (data) {
            response(data)
          }
        });
      } else {
        noty({"text": 'Seleccione una empresa para mostrar sus productos.', "layout":"topRight", "type": 'error'});
      }
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_producto").val(ui.item.id);
      $("#fproducto").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $(this).css("background-color", "#FDFC9A");
      $("#fid_producto").val("");
    }
  });


  // Autocomplete unidad
  $("#dunidad").autocomplete({
    source: base_url + 'panel/rastreabilidad/ajax_get_unidades/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_unidad").val(ui.item.id);
      $("#dunidad").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_unidad').val('');
    }
  });

  // Autocomplete etiqueta
  $("#detiqueta").autocomplete({
    source: base_url + 'panel/rastreabilidad/ajax_get_etiquetas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_etiqueta").val(ui.item.id);
      $("#detiqueta").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_etiqueta').val('');
    }
  });

  // Autocomplete calibre
  $("#dcalibre").autocomplete({
    source: base_url + 'panel/rastreabilidad/ajax_get_calibres/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_calibre").val(ui.item.id);
      $("#dcalibre").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_calibre').val('');
    }
  });

});

function cargaListaFamlias ($empresaId) {
  $.getJSON(base_url+'panel/inventario/ajax_get_familias/', {'fid_empresa': $empresaId}, 
    function(data){
      var html = '';
      for (var i in data.familias) {
        html += '<li><label><input type="checkbox" name="ffamilias[]" value="'+data.familias[i].id_familia+'" checked> '+data.familias[i].nombre+'</label></li>';
      };
      $("#lista_familias").html(html);
  });
}