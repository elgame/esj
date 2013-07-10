$(function(){

  $('#form-search').keyJump({
    'next': 13,
  });

  addpallets.init();

  
});

var addpallets = (function($){
  var objr = {}, tbody, total_cajas_sel, fcajas;

  function init(){
    asignaAutocomplets();
    listaRendimientos();

    tbody = $("#tblrendimientos");
    total_cajas_sel = $("#total_cajas_sel");
    fcajas = $("#fcajas");
  }

  function listaRendimientos(){
    $(document).on('click', '.rendimientos', function(){
      if($.isNumeric(fcajas.val())){
        if($(this).is(":checked")){
          if( parseInt(total_cajas_sel.text()) >= parseInt(fcajas.val()) ){
            noty({"text":"Ya se acompleto el Pallet con las cajas seleccionadas.", "layout":"topRight", "type":"error"});
            return false;
          }
        }
        calculaCajasSel();
      }else{
        fcajas.focus();
        noty({"text":"Ingresa las cajas del Pallet.", "layout":"topRight", "type":"error"});
        return false;
      }
    });
  }
  function calculaCajasSel(){
    var num_cajas = 0;
    $("input[type=checkbox]:checked", tbody).each(function(){
      num_cajas += parseInt($(this).attr("data-libres"));
    });
    total_cajas_sel.text(num_cajas);
  }


  function asignaAutocomplets(){
    // Autocomplete clasificaciones
    $("#fclasificacion").autocomplete({
      source: base_url + 'panel/areas/ajax_get_clasificaciones/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#fid_clasificacion").val(ui.item.id);
        $("#fclasificacion").val(ui.item.label).css({'background-color': '#99FF99'});

        getRendimientosLibres(ui.item.id);
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        $('#fid_clasificacion').val('');
      }
    });
  }

  function getRendimientosLibres($clasificacion){
    $.getJSON(base_url+"panel/rastreabilidad_pallets/ajax_get_rendimientos", {id: $clasificacion}, function(resp){
      var html = '';
      if (resp.rendimientos.length > 0) {
        for (var i = 0; i < resp.rendimientos.length; i++) {
          html += '<tr id="row_rend'+resp.rendimientos[i].id_rendimiento+'">'+
            '<td>'+resp.rendimientos[i].fecha+'</td>'+
            '<td>'+resp.rendimientos[i].lote+'</td>'+
            '<td>'+resp.rendimientos[i].libres+'</td>'+
            '<td><input type="checkbox" name="rendimientos[]" value="'+resp.rendimientos[i].id_rendimiento+'|'+resp.rendimientos[i].libres+'"'+ 
            '  class="rendimientos" data-libres="'+resp.rendimientos[i].libres+'"></td>'+
          '</tr>';
        };
        tbody.html(html);
      }else
        noty({"text":"No hay cajas libres en la clasificacion seleccionada.", "layout":"topRight", "type":"error"});
    });
  }

  objr.init = init;
  return objr;
})(jQuery);


