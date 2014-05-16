(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {
    asignaAutocomplets();

    //Asigna evento para los checks de los rendimientos
    $(document).on("click", ".cajasdisponibles", addFacturaSel);
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
          idrow = datavar.id_clasificacion+'_'+datavar.id_clasificacion+'_'+resp[i].id_factura;
          html += '<tr id="row_rend'+idrow+'">'+
            '<td class="fecha">'+resp[i].fecha+'</td>'+
            '<td class="folio">'+resp[i].serie+resp[i].folio+'</td>'+
            '<td class="cliente">'+resp[i].cliente+'</td>'+
            '<td><buttom class="btn rendimientos cajasdisponibles"'+
            '  data-id="'+idrow+'"><i class="icon-angle-right"></i></buttom></td>'+
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

  var addFacturaSel = function(e){
    var vthis = $(this), idrow = "#row_rend"+vthis.attr("data-id"), html;
    var row_rendsel = $('#row_rendsel'+vthis.attr("data-id"), tbodysel),
        //ids = id_rendimiento, id_unidad, id_calibre, id_etiqueta
        ids = vthis.attr("data-id").split('_');

    if( $("#tbl"+$("#fid_clasificacion").val()).length === 0 )
    {
      renderTableDatos();
      if(row_rendsel.length == 0){
        html = '<tr id="row_rendsel'+vthis.attr("data-id")+'">'+
            '<td class="fecha">'+$(idrow+" .fecha").text()+'</td>'+
            '<td class="lote">'+$(idrow+" .lote").text()+'</td>'+
            '<td class="clsif">'+fclasificacion.val()+'</td>'+
            '<td class="mas">'+$(idrow+" .unidad").text()+'|'+$(idrow+" .calibre").text()+'|'+$(idrow+" .etiqueta").text()+'</td>'+
            '<td><input type="number" class="span12 cajasel" name="rendimientos[]" value="'+calcRestaCajasSel($(idrow+" .libres").text())+'" min="1" max="'+calcRestaCajasSel($(idrow+" .libres").text())+'"></td>'+
            '<td><input type="hidden" name="idrendimientos[]" value="'+ids[0]+'">'+
            '   <input type="hidden" name="idclasificacion[]" value="'+fid_clasificacion.val()+'">'+
            '   <input type="hidden" name="idunidad[]" value="'+ids[1]+'">'+
            '   <input type="hidden" name="idcalibre[]" value="'+ids[2]+'">'+
            '   <input type="hidden" name="idetiqueta[]" value="'+ids[3]+'">'+
            '   <input type="hidden" name="idsize[]" value="'+ids[5]+'">'+
            '   <input type="hidden" name="dkilos[]" value="'+(ids[6].replace('-', '.'))+'">'+

            '   <buttom class="btn btn-danger remove_cajassel" data-idrow="'+vthis.attr("data-id")+'"><i class="icon-remove"></i></buttom></td>'+
          '</tr>';
        tbodysel.append(html);
        row_rendsel = $('#row_rendsel'+vthis.attr("data-id"), tbodysel);
      }else{
          var cajas_agregadas = parseInt($(".cajasel", row_rendsel).val()) + parseInt($(idrow+" .libres").text());
          cajas_agregadas = parseInt($(".cajasel", row_rendsel).val()) + calcRestaCajasSel(cajas_agregadas);
          if( cajas_agregadas > parseInt(vthis.attr("data-totales")) )
            cajas_agregadas = parseInt(vthis.attr("data-totales"));

          $(".cajasel", row_rendsel).val( cajas_agregadas ).attr('max', cajas_agregadas);;
      }
      calculaCajasSel();
    }else
      noty({"text":"El pallet esta completo.", "layout":"topRight", "type":"error"});
    $("input.cajasel", row_rendsel).focus();
  };

  var renderTableDatos = function(){
    var html = '<table id="tbl" class="table table-striped table-bordered bootstrap-datatable">'+
                '  <thead>'+
                '    <caption>'+$("#fclasificacion").val()+'</caption>'+
                '    <tr>'+
                '      <th style="width:70px;">Fecha</th>'+
                '      <th>Folio</th>'+
                '      <th>Cliente</th>'+
                '     <th>Opciones</th>'+
                '    </tr>'+
                '  </thead>'+
                '  <tbody id="tblfacturasligadas">'+
                '  </tbody>'+
                '</table>';
    $("#tblsligadas").append(html);
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

