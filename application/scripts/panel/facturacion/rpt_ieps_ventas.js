$(function(){
  $('#frmrptcproform').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
      url = {
        ffecha1: $("#ffecha1").val(),
        ffecha2: $("#ffecha2").val(),
        dempresa: $("#dempresa").val(),
        did_empresa: $("#did_empresa").val(),
        fproducto: $("#fproducto").val(),
        fid_producto: $("#fid_producto").val(),
        dcon_mov: $("#dcon_mov:checked").val(),

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

  // Autocomplete clientes
  $("#dcliente").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
      $.ajax({
          url: base_url + 'panel/clientes/ajax_get_proveedores/',
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
      $("#did_cliente").val(ui.item.id);
      $("#dcliente").val(ui.item.label).css({'background-color': '#99FF99'});
      setTimeout(addCliente, 200);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_cliente').val('');
    }
  });

  $("#btnAddCliente").on('click', addCliente);
  $(document).on('click', '.remove_cliente', removeCliente);

  $("#frmverform").submit(function(){
    // if ($(".ids_clientes").length > 0) {

      return true;
    // }else{
    //   noty({"text":"Selecciona al menos un Proveedor", "layout":"topRight", "type":"error"});
    //   return false;
    // }
  });

});


function addCliente(event){
  var $this = $(this), did_cliente = $("#did_cliente"), dcliente = $("#dcliente");
  if (did_cliente.val() != '') {
    if ( $('#liprovee'+did_cliente.val()).length == 0) {
      $("#lista_proveedores").append('<li id="liprovee'+did_cliente.val()+'"><a class="btn btn-link remove_cliente" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
              '<input type="hidden" name="ids_clientes[]" class="ids_clientes" value="'+did_cliente.val()+'"> '+dcliente.val()+'</li>');
    }else
      noty({"text":"El Cliente ya esta seleccionado", "layout":"topRight", "type":"error"});
    did_cliente.val("");
    dcliente.val("").css({'background-color': '#fff'}).focus();
  }else
    noty({"text":"Selecciona un Cliente", "layout":"topRight", "type":"error"});
}

function removeCliente(event){
  $(this).parent('li').remove();
}
