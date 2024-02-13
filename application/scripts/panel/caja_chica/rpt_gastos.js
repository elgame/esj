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

  $('#frmverformgastosc').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          dempresa: $("#dempresa").val(),
          did_empresa: $("#did_empresa").val(),
          dprov_clien: $("#dprov_clien").val(),
          fno_caja: $("#fno_caja").val(),
          fnomenclatura: $("#fnomenclatura").val(),
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

      getSucursales(ui.item.item.id_empresa);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

});


var getSucursales = function (did_empresa) {
  var params = {
    did_empresa: did_empresa
  };

  hhtml = '<option value=""></option>';
  if (params.did_empresa > 0) {
    $.ajax({
        url: base_url + 'panel/empresas/ajax_get_sucursales/',
        dataType: "json",
        data: params,
        success: function(data) {
          if(data.length > 0) {
            let idSelected = $('#sucursalId').data('selected'), selected = '';
            for (var i = 0; i < data.length; i++) {
              selected = (idSelected == data[i].id_sucursal? ' selected': '');
              hhtml += '<option value="'+data[i].id_sucursal+'" '+selected+'>'+data[i].nombre_fiscal+'</option>';
            }

            $('#sucursalId').html(hhtml); //.attr('required', 'required');
            $('.sucursales').show();
          } else {
            $('#sucursalId').html(hhtml).removeAttr('required');
            $('.sucursales').hide();
          }
        }
    });
  } else {
    $('#sucursalId').html(hhtml).removeAttr('required');
    $('.sucursales').hide();
  }
};
