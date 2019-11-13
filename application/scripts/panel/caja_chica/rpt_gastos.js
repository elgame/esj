$(function(){

  $('#frmverformprod').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          dempresa: $("#dempresa").val(),
          did_empresa: $("#did_empresa").val(),
          dtipo_factura: $("#dtipo_factura").val(),
          ids_clientes: [],
        };
    $("#lista_clientes .ids_clientes").each(function(index, el) {
      url.ids_clientes.push($(this).val());
    });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

	// Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/caja_chica/ajax_get_categorias/',
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

