(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {
    $('#form').keyJump();
    asignaAutocomplets();

    //Asigna evento para los checks de los rendimientos
    $(document).on("click", ".cajasdisponibles", addFacturaSel);
    $(document).on("click", ".deleteFacturaSel", deleteFacturaSel);
    $(document).on("click", ".deleteTblSel", deleteTblSel);

    $("#ffolio, #fcliente").on('keypress', function(event) {
      if ( event.which == 13 ) {
        getFacturasLibres();
        event.preventDefault();
      }
    });

    autocompleteEmpresas();

    addProdsCancelados();
  });

  var addProdsCancelados = function () {
    $("#addProdsCancelados").on('click', function(event) {
      if( $("#costo").val() != '' && $("#fid_clasificacion").val() != '' && $("#fid_cliente").val() != '' && $("#fecha").val() != '') {
        var html = '<tr id="row_sel">'+
                  '  <td style="width:70px;">'+$("#fecha").val()+
                  '    <input type="hidden" name="idclasif[]" class="idclasif" value="'+$("#fid_clasificacion").val()+'">'+
                  '    <input type="hidden" name="idfactura[]" class="idfactura" value="">'+
                  '    <input type="hidden" name="fecha[]" class="fecha" value="'+$("#fecha").val()+'">'+
                  '    <input type="hidden" name="costo[]" class="costo" value="'+$("#costo").val()+'">'+
                  '  </td>'+
                  '  <td></td>'+
                  '  <td>'+$("#fcliente").val()+
                  '    <input type="hidden" name="id_cliente[]" class="id_cliente" value="'+$("#fid_cliente").val()+'">'+
                  '  </td>'+
                  '  <td><buttom class="btn deleteFacturaSel"><i class="icon-remove"></i></buttom></td>'+
                  '</tr>';
      $("#tblcanceladas .tblfacturasligadas").append(html);
      } else
        noty({"text": 'El cliente, la clasificacion y el costo son requeridos.', "layout":"topRight", "type": 'error'});
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
        $("#id_empresa").val(ui.item.id);
        $empresa.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#empresa").css("background-color", "#FFD071");
        $("#id_empresa").val('');
      }
    });
  };


  var getFacturasLibres = function(){
    var datavar = {
      id_clasificacion: $("#fid_clasificacion").val(),
      id_empresa: $("#id_empresa").val(),
      id_compra: $("#id_compra").val(),
      id_cliente: $("#fid_cliente").val(),
      folio: $("#ffolio").val(),
      fechaf: $("#fechaf").val(),
    };
    if (datavar.id_clasificacion !== '') {
      $.getJSON(base_url+"panel/gastos/ajax_get_facturas", datavar, function(resp){
        var html = '', idrow;
        if (resp.length > 0) {
          for (var i = 0; i < resp.length; i++) {
            idrow = datavar.id_clasificacion+'_'+datavar.id_compra+'_'+resp[i].id_factura;
            html += '<tr id="row_rend'+idrow+'">'+
              '<td class="fecha">'+resp[i].fecha+'</td>'+
              '<td class="folio">'+resp[i].serie+resp[i].folio+'</td>'+
              '<td class="cliente">'+resp[i].cliente+'</td>'+
              '<td><buttom class="btn rendimientos cajasdisponibles"'+
              '  data-id="'+idrow+'" data-idFactura="'+resp[i].id_factura+'"><i class="icon-angle-right"></i></buttom></td>'+
            '</tr>';
          }
          $("#tblfacturaslibres").html(html);
        }else
        {
          $("#tblfacturaslibres").html("");
          noty({"text":"No hay Facturas libres en la clasificacion seleccionada.", "layout":"topRight", "type":"error"});
        }
      });
    }
  };

  var deleteFacturaSel = function(e){
    $(this).parents("tr[id^=row_sel]").remove();
  };
  var deleteTblSel = function(e){
    $(this).parents("table[id^=tbl]").remove();
  };

  var addFacturaSel = function(e){
    var $vthis = $(this), $clasificacion = $("#fid_clasificacion");
    if( $("#tbl"+$clasificacion.val()).length === 0 )
    {
      renderTableDatos();
    }
    renderRowDatos($clasificacion.val(), $vthis);
  };

  var renderTableDatos = function(){
    var html = '<table id="tbl'+$("#fid_clasificacion").val()+'" class="table table-striped table-bordered bootstrap-datatable">'+
                '  <caption>'+$("#fclasificacion").val()+' - <buttom class="btn deleteTblSel"><i class="icon-remove"></i></buttom></caption>'+
                '  <thead>'+
                '    <tr>'+
                '      <th style="width:70px;">Fecha</th>'+
                '      <th>Folio</th>'+
                '      <th>Cliente</th>'+
                '     <th>Opciones</th>'+
                '    </tr>'+
                '  </thead>'+
                '  <tbody class="tblfacturasligadas">'+
                '  </tbody>'+
                '</table>';
    $("#tblsligadas").append(html);
  };

  var renderRowDatos = function(idClasif, $factura){
    var tbl = $("#tbl"+idClasif+" .tblfacturasligadas"),
    data_factura = $("#row_rend"+$factura.attr('data-id')),
    idrow = idClasif+'_'+$("#id_compra").val()+'_'+$factura.attr('data-idFactura');
    if($("#row_sel"+idrow).length === 0)
    {
      var html = '<tr id="row_sel'+idrow+'">'+
                  '  <td style="width:70px;">'+data_factura.find('.fecha').text()+
                  '    <input type="hidden" name="idclasif[]" class="idclasif" value="'+idClasif+'">'+
                  '    <input type="hidden" name="idfactura[]" class="idfactura" value="'+$factura.attr('data-idFactura')+'">'+
                  '    <input type="hidden" name="fecha[]" class="fecha" value="">'+
                  '    <input type="hidden" name="costo[]" class="costo" value="">'+
                  '  </td>'+
                  '  <td>'+data_factura.find('.folio').text()+'</td>'+
                  '  <td>'+data_factura.find('.cliente').text()
                  '    <input type="hidden" name="id_cliente[]" class="id_cliente" value="">'+
                  '  </td>'+
                  '  <td><buttom class="btn deleteFacturaSel"><i class="icon-remove"></i></buttom></td>'+
                  '</tr>';
      tbl.append(html);
    }else
      noty({"text": 'Ya esta agregada esa factura', "layout":"topRight", "type": 'error'});
  };

  var asignaAutocomplets = function(){
    // Autocomplete clasificaciones
    $("#fclasificacion").autocomplete({
      source: base_url + 'panel/areas/ajax_get_clasificaciones/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#fid_clasificacion").val(ui.item.id);
        $("#fclasificacion").val(ui.item.label).css({'background-color': '#99FF99'});

        getFacturasLibres();
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        $("#fid_clasificacion").val('');
      }
    });
    // Autocomplete Clientes
    $("#fcliente").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#id_empresa").val()) > 0)
          params.did_empresa = $("#id_empresa").val();
        $.ajax({
            url: base_url+'panel/clientes/ajax_get_proveedores/',
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
        $("#fid_cliente").val(ui.item.id);
        $("#fcliente").css("background-color", "#B0FFB0");
        getFacturasLibres();
      }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $("#fcliente").val("").css("background-color", "#FFD9B3");
          $("#fid_cliente").val("");
        }
    });
  };

});

