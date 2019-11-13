$(function(){

  $('#frmverform').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          dempresa: $("#dempresa").val(),
          did_empresa: $("#did_empresa").val(),
          dtipo_factura: $("#dtipo_factura").val(),
          con_saldo: ($("#con_saldo").is(':checked')? 'si': ''),

          ids_clientes: [],
        };
    $("#lista_clientes .ids_clientes").each(function(index, el) {
      url.ids_clientes.push($(this).val());
    });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

  $('#frmventclin').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          dtipo_factura: $("#dtipo_factura").val(),
          dcon_mov: ($("#dcon_mov").is(':checked')? 'si': ''),

          did_empresa: [],
          ids_clientes: [],
        };
    $("#lista_clientes .ids_clientes").each(function(index, el) {
      url.ids_clientes.push($(this).val());
    });
    $("#did_empresa option:selected").each(function() {
      url.did_empresa.push($(this).val());
    });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

  $('#frmventprovee').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          dtipo_cuenta: $("#dtipo_cuenta").val(),
          dcon_mov: ($("#dcon_mov").is(':checked')? 'si': ''),
          dsin_mov: ($("#dsin_mov").is(':checked')? 'si': ''),

          did_empresa: [],
          ids_proveedores: [],
        };
    $("#lista_clientes .ids_proveedores").each(function(index, el) {
      url.ids_proveedores.push($(this).val());
    });
    $("#did_empresa option:selected").each(function() {
      url.did_empresa.push($(this).val());
    });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
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


  // Autocomplete cliente
  $("#dcliente").autocomplete({
    source: function(request, response) {
        var vales = $("#did_empresa").val();
        if (vales.length == 1) {
          $.ajax({
              url: base_url + 'panel/clientes/ajax_get_proveedores/',
              dataType: "json",
              data: {
                  term : request.term,
                  did_empresa : vales[0]
              },
              success: function(data) {
                  response(data);
              }
          });
        } else
          response([]);
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

  // Autocomplete proveedor
  $("#dproveedor").autocomplete({
    source: function(request, response) {
      $.ajax({
          url: base_url + 'panel/proveedores/ajax_get_proveedores/',
          dataType: "json",
          data: {
              term : request.term,
              lbempresa : 'true'
          },
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

  $("#frmverform").submit(function(){
    if($("#did_empresa").val() == ''){
      noty({"text":"Selecciona una Empresa", "layout":"topRight", "type":"error"});
      return false;
    }

    // if ($(".ids_clientes").length > 0) {

    //   return true;
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
      $("#lista_clientes").append('<li id="liprovee'+did_cliente.val()+'"><a class="btn btn-link remove_proveedor" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
              '<input type="hidden" name="ids_clientes[]" class="ids_clientes" value="'+did_cliente.val()+'"> '+dcliente.val()+'</li>');
    }else
      noty({"text":"El Cliente ya esta seleccionado", "layout":"topRight", "type":"error"});
    did_cliente.val("");
    dcliente.val("").css({'background-color': '#fff'}).focus();
  }else
    noty({"text":"Selecciona un Cliente", "layout":"topRight", "type":"error"});
}

function addProveedor(event){
  var $this = $(this), did_proveedor = $("#did_proveedor"), dproveedor = $("#dproveedor");
  if (did_proveedor.val() != '') {
    if ( $('#liprovee'+did_proveedor.val()).length == 0) {
      $("#lista_clientes").append('<li id="liprovee'+did_proveedor.val()+'"><a class="btn btn-link remove_proveedor" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
              '<input type="hidden" name="ids_proveedores[]" class="ids_proveedores" value="'+did_proveedor.val()+'"> '+dproveedor.val()+'</li>');
    }else
      noty({"text":"El Proveedor ya esta seleccionado", "layout":"topRight", "type":"error"});
    did_proveedor.val("");
    dproveedor.val("").css({'background-color': '#fff'}).focus();
  }else
    noty({"text":"Selecciona un Proveedor", "layout":"topRight", "type":"error"});
}

function removeProveedor(event){
  $(this).parent('li').remove();
}

