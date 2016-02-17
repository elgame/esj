$(function(){

  $('#form-search').keyJump({
    'next': 13,
  });

  addpallets.init();


});

var addpallets = (function($){
  var objr = {}, tbody, tbodysel, total_cajas_sel, fcajas,
  fid_clasificacion, fclasificacion,
  funidad, fidunidad,
  fcalibre, fidcalibre,
  fetiqueta, fidetiqueta, contadorInputs;

  function init(){
    contadorInputs = 0;

    formPallet();

    tbody             = $("#tblrendimientos");
    tbodysel          = $("#tblrendimientossel");
    total_cajas_sel   = $("#total_cajas_sel");
    fcajas            = $("#fcajas");
    fid_clasificacion = $("#fid_clasificacion");
    fclasificacion    = $("#fclasificacion");
    funidad           = $("#funidad");
    fidunidad         = $("#fidunidad");
    fcalibre          = $("#fcalibre");
    fidcalibre        = $("#fidcalibre");
    fetiqueta         = $("#fetiqueta");
    fidetiqueta       = $("#fidetiqueta");

    asignaAutocomplets();
    changeAreas();

    modalEvent();
    changePapel();

    addNewProductsSalida();
  }

  function addNewProductsSalida () {
    $(".btnAddM").on('click', function(event) {
      var $this = $(this), $tr = $this.parents('tr'),
      $trnew = $tr.clone(), $trInput = $trnew.find(".prod_salida");
      $trnew.find('.btnAddM').parents('td').html('<button type="button" class="btn btn-danger btnRemoveM"><i class="icon-remove"></i></button> ');
      // $trnew.find('.vpositive').numeric({ negative: false });
      $trnew.find('[id^='+$trInput.attr('id')+']').not('input[name="ps_row[]"]').each(function(index, el) {
        $(this).val('');
      });
      $tr.after($trnew);
      $trInput.focus();

      $.fn.removeNumeric();
      $('#form-search').keyJump.off();
      $('#form-search').keyJump({
        'next': 13,
      });
      $.fn.setNumericDefault();
      ++contadorInputs;
    });

    // Eliminar
    $('#modalProdutosSal').on('click', '.btnRemoveM', function(event) {
      $('#form-search').keyJump.off();
      $(this).parents('tr').remove();
      $('#form-search').keyJump({
        'next': 13,
      });
    });
  }

  function modalEvent() {
    $('#modalProdutosSal').on('shown', function () {
      $("#ps_caja").focus();
    });
  }

  function changePapel() {
    $("#fhojaspapel").on('change', function(event) {
      if(parseFloat($("#fcajas").val()) > 0)
        $("#ps_papel_num").val(parseFloat($(this).val()) * parseFloat($("#fcajas").val()));
      else
        noty({"text": 'Asigne las cajas del Pallet', "layout":"topRight", "type": 'error'});
    });
  }

  function changeAreas () {
    $("#parea").on('change', function(event) {
      $.getJSON(base_url+'panel/rastreabilidad_pallets/ajax_get_folio',
        {darea: $("#parea").val()},
        function(json, textStatus) {
          if(json.folio != null)
            $("#ffolio").val(json.folio);
          else
            noty({"text": 'No se puede obtener el folio del area', "layout":"topRight", "type": 'error'});
      });
    });
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
    // Autocomplete de productos dar de baja
    $("#modalProdutosSal").on('keyup.autocomplete', '.prod_salida', function(event) {
      event.preventDefault();
      /* Act on the event */
      $(".prod_salida").autocomplete({
        source: function (request, response) {
          $.ajax({
            url: base_url + 'panel/compras_ordenes/ajax_producto/',
            dataType: 'json',
            data: {
              term : request.term,
              ide: '2',
              tipo: 'p'
            },
            success: function (data) {
              response(data)
            }
          });
        },
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this), $fcajas = $("#fcajas"), $tr = $this.parents('tr');

          $this.css("background-color", "#B6E7FF");
          $("#"+$this.attr('id')+"_id", $tr).val(ui.item.id);

          // Verifica si ese campo multimipla por una cantidad, lo coloca en la cantidad el resultado
          if ($this.attr('data-xcajas') != undefined) {
            if( $.isNumeric($fcajas.val()) ) {
              $("#"+$this.attr('id')+"_num", $tr).val( parseFloat($this.attr('data-xcajas'))*parseFloat($fcajas.val()) );
            }else {
              noty({"text": 'Asigne las cajas del Pallet', "layout":"topRight", "type": 'error'});

              $this.css("background-color", "#FDFC9A");
              setTimeout(function(){
                $this.val("");
              }, 200);
              $("#"+$this.attr('id')+"_id", $tr).val("");
            }
          }
        }
      }).on("keydown", function(event) {
        var $this = $(this), $tr = $this.parents('tr');
        if(event.which == 8 || event.which == 46) {
          $this.css("background-color", "#FDFC9A");
          $("#"+$this.attr('id')+"_id", $tr).val("");
          $("#"+$this.attr('id')+"_num:not(.noclear)", $tr).val( "" );

        }
      });
    });

    $("input#fcalibre_fijo").autocomplete({
      source: base_url + 'panel/rastreabilidad/ajax_get_calibres/',
      minLength: 1,
      selectFirst: true,
      select: function(event, ui) {
        $("#fcalibre_fijo").val(ui.item.label);//.css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
        // $(this).css({'background-color': '#FFD9B3'});
      }
    });

    // Autocomplete clasificaciones
    $("#fclasificacion").autocomplete({
      source: function (request, response) {
        if ($('#did_empresa').val()!='') {
          $.ajax({
            url: base_url + 'panel/areas/ajax_get_clasificaciones/',
            dataType: 'json',
            data: {
              term : request.term,
              type : $('#parea').val()
            },
            success: function (data) {
              response(data)
            }
          });
        } else {
          noty({"text": 'Seleccione una area para mostrar sus productos.', "layout":"topRight", "type": 'error'});
        }
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        fid_clasificacion.val(ui.item.id);
        fclasificacion.val(ui.item.label).css({'background-color': '#99FF99'});

        getRendimientosLibres();
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        fid_clasificacion.val('');
      }
    });

    // Autocomplete unidad
    funidad.autocomplete({
      source: base_url + 'panel/rastreabilidad/ajax_get_unidades/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        fidunidad.val(ui.item.id);
        funidad.val(ui.item.label).css({'background-color': '#99FF99'});

        getRendimientosLibres();
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        fidunidad.val('');
      }
    });

    // Autocomplete calibre
    fcalibre.autocomplete({
      source: base_url + 'panel/rastreabilidad/ajax_get_calibres/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        fidcalibre.val(ui.item.id);
        fcalibre.val(ui.item.label).css({'background-color': '#99FF99'});

        getRendimientosLibres();
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        fidcalibre.val('');
      }
    });

    // Autocomplete etiqueta
    fetiqueta.autocomplete({
      source: base_url + 'panel/rastreabilidad/ajax_get_etiquetas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        fidetiqueta.val(ui.item.id);
        fetiqueta.val(ui.item.label).css({'background-color': '#99FF99'});

        getRendimientosLibres();
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        fidetiqueta.val('');
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
      source: function(request, response) {
        $.ajax({
            url: base_url + 'panel/bascula/ajax_get_clientes/',
            dataType: "json",
            data: {
              term : request.term,
              did_empresa : '2,3,4,5,7',
              empresa: 'si'
            },
            success: function(data) {
                response(data);
            }
        });
      },
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
    var row_rendsel = $('#row_rendsel'+vthis.attr("data-id"), tbodysel),
        //ids = id_rendimiento, id_unidad, id_calibre, id_etiqueta
        ids = vthis.attr("data-id").split('_');

    if(parseInt(total_cajas_sel.text()) < parseInt(fcajas.val()) )
    {
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

  function getRendimientosLibres(){
    var datavar = {
      id: fid_clasificacion.val(),
      idunidad: fidunidad.val(),
      idcalibre: fidcalibre.val(),
      idetiqueta: fidetiqueta.val()
    }
    $.getJSON(base_url+"panel/rastreabilidad_pallets/ajax_get_rendimientos", datavar, function(resp){
      var html = '', idrow;
      if (resp.rendimientos.length > 0) {
        for (var i = 0; i < resp.rendimientos.length; i++) {
          idrow = resp.rendimientos[i].id_rendimiento+'_'+resp.rendimientos[i].id_unidad+'_'+resp.rendimientos[i].id_calibre+'_'+resp.rendimientos[i].id_etiqueta+'_'+
                  resp.rendimientos[i].id_clasificacion+'_'+resp.rendimientos[i].id_size+'_'+resp.rendimientos[i].kilos.replace('.', '-');
          html += '<tr id="row_rend'+idrow+'">'+
            '<td class="fecha">'+resp.rendimientos[i].fecha+'</td>'+
            '<td class="lote">'+resp.rendimientos[i].lote+'</td>'+

            '<td class="unidad">'+resp.rendimientos[i].unidad+'</td>'+
            '<td class="calibre">'+resp.rendimientos[i].calibre+'</td>'+
            '<td class="etiqueta">'+resp.rendimientos[i].etiqueta+'</td>'+
            '<td class="etiqueta">'+resp.rendimientos[i].kilos+'</td>'+

            '<td class="libres">'+resp.rendimientos[i].libres+'</td>'+
            '<td><buttom class="btn rendimientos cajasdisponibles"'+
            '  data-id="'+idrow+'" data-libres="'+resp.rendimientos[i].libres+'" data-totales="'+resp.rendimientos[i].rendimiento+'"><i class="icon-angle-right"></i></buttom></td>'+
          '</tr>';
        };
        tbody.html(html);
      }else
      {
        tbody.html("");
        noty({"text":"No hay cajas libres en la clasificacion seleccionada.", "layout":"topRight", "type":"error"});
      }
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


