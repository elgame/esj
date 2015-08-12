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
      if ($('#did_empresa').val() !== '') {
        $.ajax({
          url: base_url + 'panel/compras_ordenes/ajax_producto/',
          dataType: 'json',
          data: {
            term : request.term,
            ide: $('#did_empresa').val(),
            tipo: 'p'
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
      $("#fid_producto").val(ui.item.id);
      $("#fproducto").val(ui.item.label).css({'background-color': '#99FF99'});
      if($("#fproducto").attr('data-add') === undefined)
        setTimeout(addProducto, 200);
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

  $("#btnAddProducto").on('click', addProducto);
  $(document).on('click', '.remove_producto', removeProducto);

  $('#form').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          did_empresa: $("#did_empresa").val(),
          fid_producto: $("#fid_producto").val(),
          con_existencia: $("#con_existencia").is(':checked')? 'si': '',
          con_movimiento: $("#con_movimiento").is(':checked')? 'si': '',

          ffamilias: [],
        };
    $("input.familiass[type=checkbox]:checked").each(function(index, el) {
      url.ffamilias.push($(this).val());
    });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
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

function addProducto(event){
  var $this = $(this), fid_producto = $("#fid_producto"), fproductor = $("#fproducto");
  if (fid_producto.val() != '') {
    if ( $('#liprovee'+fid_producto.val()).length == 0) {
      $("#lista_proveedores").append('<li id="liprovee'+fid_producto.val()+'"><a class="btn btn-link remove_producto" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
              '<input type="hidden" name="ids_productos[]" class="ids_productos" value="'+fid_producto.val()+'"> '+fproductor.val()+'</li>');
    }else
      noty({"text":"El Proveedor ya esta seleccionado", "layout":"topRight", "type":"error"});
    fid_producto.val("");
    fproductor.val("").css({'background-color': '#fff'}).focus();
  }else
    noty({"text":"Selecciona un Producto", "layout":"topRight", "type":"error"});
}

function removeProducto(event){
  $(this).parent('li').remove();
}