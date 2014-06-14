$(function(){
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

  // Autocomplete cliente
  $("#dcliente").autocomplete({
    source: function(request, response) {
        $.ajax({
            url: base_url + 'panel/clientes/ajax_get_proveedores/',
            dataType: "json",
            data: {
                term : request.term,
                did_empresa : $("#did_empresa").val()
            },
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

  $("#btnAddProveedor").on('click', addCliente);
  $(document).on('click', '.remove_proveedor', removeProveedor);

  $("#frmverform").submit(function(){
    if($("#did_empresa").val() === ''){
      noty({"text":"Selecciona una Empresa", "layout":"topRight", "type":"error"});
      return false;
    }

    if($("#pproducto_id").val() === ''){
      noty({"text":"Selecciona un Producto", "layout":"topRight", "type":"error"});
      return false;
    }

    // if ($(".ids_clientes").length > 0) {

    //   return true;
    // }else{
    //   noty({"text":"Selecciona al menos un Proveedor", "layout":"topRight", "type":"error"});
    //   return false;
    // }
  });

  // Autocomplete Proveedor
  $("#pproveedor").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
      $.ajax({
          url: base_url + 'panel/bascula/ajax_get_proveedores/',
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
      $("#pid_proveedor").val(ui.item.id);
      $("#pproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#pid_proveedor').val('');
    }
  });

  $("#pproducto").autocomplete({
    source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      var $this = $(this);

      $this.css("background-color", "#B0FFB0");
      $('#pproducto_id').val(ui.item.id);
    }
  }).keydown(function(event){
      if(event.which == 8 || event == 46) {
        var $tr = $(this).parent().parent();
        $(this).css("background-color", "#FFD9B3");
        $tr.find('#pproducto_id').val('');
      }
  });

});

function addCliente(event){
  var $this = $(this), did_cliente = $("#did_cliente"), dcliente = $("#dcliente");
  if (did_cliente.val() != '') {
    if ( $('#liprovee'+did_cliente.val()).length == 0) {
      $("#lista_clientes").append('<li id="liprovee'+did_cliente.val()+'"><a class="btn btn-link remove_proveedor" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
              '<input type="hidden" name="ids_clientes[]" class="ids_clientes" value="'+did_cliente.val()+'"> '+dcliente.val()+'</li>');
    }else
      noty({"text":"El Cliente ya esta seleccionado", "layout":"topRight", "type":"error"});
    did_cliente.val("");
    dcliente.val("").css({'background-color': '#fff'}).focus();
  }else
    noty({"text":"Selecciona un Cliente", "layout":"topRight", "type":"error"});
}

function removeProveedor(event){
  $(this).parent('li').remove();
}

