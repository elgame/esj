$(function(){

  $('#form-search').keyJump({
    'next': 13,
  });

  addpallets.init();

  
});

var addpallets = (function($){
  var objr = {}, tbody, tbodysel, total_cajas_sel, fcajas,
  fid_clasificacion, fclasificacion;

  function init(){
    asignaAutocomplets();
    formPallet();

    tbody             = $("#tblrendimientos");
    tbodysel          = $("#tblrendimientossel");
    total_cajas_sel   = $("#total_cajas_sel");
    fcajas            = $("#fcajas");
    fid_clasificacion = $("#fid_clasificacion");
    fclasificacion    = $("#fclasificacion");
  }

  function formPallet(){
     $('#form-search').on('submit', function(){
      if(parseInt(fcajas.val()) < parseInt(total_cajas_sel.text())){
        noty({"text": "Las cajas seleccionadas son mayor a las cajas del pallet.", "layout":"topRight", "type":"error"});
        return false;
      }
     });
  }

  function asignaAutocomplets(){
    // Autocomplete clasificaciones
    $("#fclasificacion").autocomplete({
      source: base_url + 'panel/areas/ajax_get_clasificaciones/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        fid_clasificacion.val(ui.item.id);
        fclasificacion.val(ui.item.label).css({'background-color': '#99FF99'});

        getRendimientosLibres(ui.item.id);
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        fid_clasificacion.val('');
      }
    });

    //Asigna evento para los checks de los rendimientos
    $(document).on("click", ".cajasdisponibles", addCajaSel);
    //Remove una caja seleccionada
    $(document).on("click", ".remove_cajassel", quitCajaSel);
    //Recalcula el total de cajas al editarce
    $(document).on("change", ".cajasel", calculaCajasSel);

    //Clientes
    $("#fcliente").autocomplete({
      source: base_url + 'panel/bascula/ajax_get_clientes/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#fid_cliente").val(ui.item.id);
        $("#fcliente").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
       $(this).css({'background-color': '#FFD9B3'});
        $('#fid_cliente').val('');
      }
    });
  }

  function addCajaSel(){
    var vthis = $(this), idrow = "#row_rend"+vthis.attr("data-id"), html;

    var row_rendsel = $('#row_rendsel'+vthis.attr("data-id")+'_'+fid_clasificacion.val(), tbodysel);
    if(parseInt(total_cajas_sel.text()) < parseInt(fcajas.val()) ) 
    {
      if(row_rendsel.length == 0){
        html = '<tr id="row_rendsel'+vthis.attr("data-id")+'_'+fid_clasificacion.val()+'">'+
            '<td class="fecha">'+$(idrow+" .fecha").text()+'</td>'+
            '<td class="lote">'+$(idrow+" .lote").text()+'</td>'+
            '<td class="clsif">'+fclasificacion.val()+'</td>'+
            '<td><input type="number" class="span12 cajasel" name="rendimientos[]" value="'+calcRestaCajasSel($(idrow+" .libres").text())+'" min="1" max="'+calcRestaCajasSel($(idrow+" .libres").text())+'"></td>'+
            '<td><input type="hidden" class="span5" name="idrendimientos[]" value="'+vthis.attr("data-id")+'">'+
            '   <input type="hidden" class="span5" name="idclasificacion[]" value="'+fid_clasificacion.val()+'">'+
            '   <buttom class="btn btn-danger remove_cajassel" data-idrow="'+vthis.attr("data-id")+'_'+fid_clasificacion.val()+'"><i class="icon-remove"></i></buttom></td>'+
          '</tr>';
        tbodysel.append(html);
        row_rendsel = $('#row_rendsel'+vthis.attr("data-id")+'_'+fid_clasificacion.val(), tbodysel);
      }else{
          $(".cajasel", row_rendsel).val( calcRestaCajasSel($(idrow+" .libres").text()) );
      }
      calculaCajasSel();
    }else
      noty({"text":"El pallet esta completo.", "layout":"topRight", "type":"error"});
    $("input.cajasel", row_rendsel).focus();
  }
  function quitCajaSel(){
    var vthis = $(this);
    $("#row_rendsel"+vthis.attr("data-idrow")).remove();
    calculaCajasSel();
  }
  function calculaCajasSel(res){
    res = typeof res === 'boolean'? res : false;
    var num_cajas = 0;
    $(".cajasel", tbodysel).each(function(){
      num_cajas += parseInt($(this).val());
    });

    if(res)
      return num_cajas;
    else
      total_cajas_sel.text(num_cajas);
  }

  function getRendimientosLibres($clasificacion){
    $.getJSON(base_url+"panel/rastreabilidad_pallets/ajax_get_rendimientos", {id: $clasificacion}, function(resp){
      var html = '';
      if (resp.rendimientos.length > 0) {
        for (var i = 0; i < resp.rendimientos.length; i++) {
          html += '<tr id="row_rend'+resp.rendimientos[i].id_rendimiento+'">'+
            '<td class="fecha">'+resp.rendimientos[i].fecha+'</td>'+
            '<td class="lote">'+resp.rendimientos[i].lote+'</td>'+
            '<td class="libres">'+resp.rendimientos[i].libres+'</td>'+
            '<td><buttom class="btn rendimientos cajasdisponibles"'+ 
            '  data-id="'+resp.rendimientos[i].id_rendimiento+'" data-libres="'+resp.rendimientos[i].libres+'"><i class="icon-angle-right"></i></buttom></td>'+
          '</tr>';
        };
        tbody.html(html);
      }else
        noty({"text":"No hay cajas libres en la clasificacion seleccionada.", "layout":"topRight", "type":"error"});
    });
  }

  function calcRestaCajasSel (cagregar) {
    var cajas = calculaCajasSel(true),
    total     = parseInt(fcajas.val());
    cagregar  = parseInt(cagregar),
    resta     = total - cajas;
    if(cagregar < resta)
      resta = cagregar;
    return resta;
  }

  objr.init = init;
  return objr;
})(jQuery);


