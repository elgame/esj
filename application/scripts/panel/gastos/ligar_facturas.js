(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {
    asignaAutocomplets();

    //Asigna evento para los checks de los rendimientos
    $(document).on("click", ".cajasdisponibles", addFacturaSel);
    $(document).on("click", ".deleteFacturaSel", deleteFacturaSel);
    $(document).on("click", ".deleteTblSel", deleteTblSel);
  });


  var getFacturasLibres = function(){
    var datavar = {
      id_clasificacion: $("#fid_clasificacion").val(),
      id_empresa: $("#id_empresa").val(),
      id_compra: $("#id_compra").val(),
    };
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
                  '  </td>'+
                  '  <td>'+data_factura.find('.folio').text()+'</td>'+
                  '  <td>'+data_factura.find('.cliente').text()+'</td>'+
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
  };

});

