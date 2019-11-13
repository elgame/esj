$(function(){
  autocompleteCatalogos();

});

// Autocomplete para los catalogos.
var autocompleteCatalogos = function () {
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

  // Autocomplete Producto
  $("#dproducto").autocomplete({
    source: function (request, response) {
      if (isEmpresaSelected()) {
        $.ajax({
          url: base_url + 'panel/compras_ordenes/ajax_producto/',
          dataType: 'json',
          data: {
            term : request.term,
            ide: $('#did_empresa').val(),
            tipo: '',
          },
          success: function (data) {
            response(data);
          }
        });
      } else {
        noty({"text": 'Seleccione una empresa para mostrar sus productos.', "layout":"topRight", "type": 'error'});
      }
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_producto").val(ui.item.id);
      $("#dproducto").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_producto').val('');
    }
  });

  $("#dentrego").autocomplete({
    source: base_url + 'panel/usuarios/ajax_get_usuarios/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      var $dentrego =  $(this);

      $dentrego.css("background-color", "#A1F57A");
      $("#did_entrego").val(ui.item.id);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#dentrego").css("background-color", "#FFD071");
      $("#did_entrego").val('');
    }
  });

  $("#drecibio").autocomplete({
    source: base_url + 'panel/usuarios/ajax_get_usuarios/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      var $drecibio =  $(this);

      $drecibio.css("background-color", "#A1F57A");
      $("#did_recibio").val(ui.item.id);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#drecibio").css("background-color", "#FFD071");
      $("#did_recibio").val('');
    }
  });

  $("#dregistro").autocomplete({
    source: base_url + 'panel/usuarios/ajax_get_usuarios/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      var $dregistro =  $(this);

      $dregistro.css("background-color", "#A1F57A");
      $("#did_registro").val(ui.item.id);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#dregistro").css("background-color", "#FFD071");
      $("#did_registro").val('');
    }
  });

};


// Regresa true si esta seleccionada una empresa si no false.
var isEmpresaSelected = function () {
  return $('#did_empresa').val() !== '';
};