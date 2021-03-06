(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    autocompleteProveedores();
    autocompleteEmpresas();
    autocompleteConcepto();
    marcaChecksLigar();
    verOrdenesPrev();
  });

  /*
   |------------------------------------------------------------------------
   | Autocompletes
   |------------------------------------------------------------------------
   */

  // Autocomplete para los Proveedores.
  var autocompleteProveedores = function () {
    $("#proveedor").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
        $.ajax({
            url: base_url + 'panel/proveedores/ajax_get_proveedores/',
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
        var $proveedor =  $(this);

        $proveedor.val(ui.item.id);
        $("#proveedorId").val(ui.item.id);
        $proveedor.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#proveedor").css("background-color", "#FFD071");
        $("#proveedorId").val('');
      }
    });
  };

  // Autocomplete para las empresas.
  var autocompleteEmpresas = function () {
    $("#empresa").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $empresa =  $(this);

        $empresa.val(ui.item.id);
        $("#empresaId").val(ui.item.id);
        $empresa.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#empresa").css("background-color", "#FFD071");
        $("#empresaId").val('');
      }
    });
  };

  // Autocomplete para el codigo.
  var autocompleteConcepto = function () {
    $("#fconcepto").autocomplete({
      source: function (request, response) {
        console.log($('#empresaId').val());
        if ($('#empresaId').val() != '') {
          $.ajax({
            url: base_url + 'panel/compras_ordenes/ajax_producto/',
            dataType: 'json',
            data: {
              term : request.term,
              ide: $('#empresaId').val()
              // tipo: ($('#tipoOrden').find('option:selected').val()=='oc'? 'd': $('#tipoOrden').find('option:selected').val()),
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
        var $fconcepto    = $(this),
            $fconceptoId   = $('#fconceptoId');


        $fconcepto.css("background-color", "#B6E7FF");
        $fconceptoId.val(ui.item.id);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $(this).css("background-color", "#FDFC9A");
        $('#fconceptoId').val('');
      }
    });
  };

  var marcaChecksLigar = function () {
    $('.addToFactura').on('click', function(event) {
        var total = 0;
        $("#sumaRowsSel").hide();

        $('.addToFactura').each(function(index, el) {
          var $check = $(this);
          if ($check.is(':checked')) {
            total += parseFloat($(this).attr("data-total"));
          }
        });

        if(total > 0)
          $("#sumaRowsSel").text(util.darFormatoNum(total, '')).show();
    });
  };

  var verOrdenesPrev = function () {
    $('.linkOrdenView').click(function(event) {
        $('#frmOrdenView').attr('src', base_url + 'panel/compras_ordenes/modificar/?id='+$(this).attr('data-idOrden')+'&w=c');
      // $('#modalOrden').modal('shown', function () {
      //   console.log('test', base_url + 'panel/compras_ordenes/modificar/?id='+$(this).attr('data-idOrden')+'&w=c');
      // });
    });
  };

});

function getOrdenesIds ($button, $modal) {
  var idp   = $('#proveedorId').val(),
      ide   = $('#empresaId').val(),
      exist = false,
      ids   = [];

  $('.addToFactura').each(function(index, el) {
    var $check = $(this);

    if ($check.is(':checked')) {
      ids.push($(this).val());
      exist = true;
    }
  });

  if (exist) {
    $button.attr('href', base_url + 'panel/compras_ordenes/ligar/?idp='+idp+'&ide='+ide+'&ids=' + ids.join(','));
    $modal.modal('show');
  } else {
    noty({"text": 'Seleccione una o mas ordenes de compras para ligarlas a una factura!', "layout":"topRight", "type": 'error'});
  }
}