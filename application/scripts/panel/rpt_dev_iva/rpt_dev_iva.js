$(function(){

  $('#frmrptcproform').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
      url = {
        ffecha1: $("#ffecha1").val(),
        ffecha2: $("#ffecha2").val(),
        dempresa: $("#dempresa").val(),
        did_empresa: $("#did_empresa").val(),
        exportacion: $("#exportacion").val(),
        tasa_iva: $("#tasa_iva").val(),

        ids_clientes: [],
      };

    $("input.ids_clientes").each(function(index, el) {
      url.ids_clientes.push($(this).val());
    });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));

    // if (url.dareas.length == 0) {
    //   noty({"text": 'Seleccione una area', "layout":"topRight", "type": 'error'});
    //   return false;
    // }
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


  // Autocomplete proveedores
  $("#dproveedor").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
      $.ajax({
          url: base_url + 'panel/facturacion/ajax_get_clientes/',
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
      $("#did_proveedor").val(ui.item.id);
      $("#dproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
      setTimeout(addProveedor, 200);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_proveedor').val('');
    }
  });

  $("#btnAddProveedor").on('click', addProveedor);
  $(document).on('click', '.remove_cliente', removeProveedor);



});


function addProveedor(event){
  var $this = $(this), did_proveedor = $("#did_proveedor"), dproveedor = $("#dproveedor");
  if (did_proveedor.val() != '') {
    if ( $('#liprovee'+did_proveedor.val()).length == 0) {
      $("#lista_proveedores").append('<li id="liprovee'+did_proveedor.val()+'"><a class="btn btn-link remove_cliente" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
              '<input type="hidden" name="ids_clientes[]" class="ids_clientes" value="'+did_proveedor.val()+'"> '+dproveedor.val()+'</li>');
    }else
      noty({"text":"El Cliente ya esta seleccionado", "layout":"topRight", "type":"error"});
    did_proveedor.val("");
    dproveedor.val("").css({'background-color': '#fff'}).focus();
  }else
    noty({"text":"Selecciona un Cliente", "layout":"topRight", "type":"error"});
}

function removeProveedor(event){
  $(this).parent('li').remove();
}
