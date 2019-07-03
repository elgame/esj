$(function(){

  $('#form-search').keyJump({
    'next': 13,
  });

  addpaletas.init();


});

var addpaletas = (function($){
  var objr = {}, tbody, tbodysel, total_cajas_sel, fcajas,
  fid_clasificacion, fclasificacion,
  funidad, fidunidad,
  fcalibre, fidcalibre,
  fetiqueta, fidetiqueta, contadorInputs;

  function init(){
    contadorInputs = 0;

    eventLigarBoletasSalida();
    setBoletasSel();

    eventOnChangeMedida();

    autocompleteClasifiLive();
    autocompleteClientes();

    nuevoRegistro();
  }

  // --------------------------------
  // Buscar boletas
  function eventLigarBoletasSalida() {
    $("#show-boletasSalidas").on('click', function(event) {
      $("#filBoleta").val("");

      getBoletas(['en', 'sa', 'p', 'b']);
      $("#modal-boletas").modal('show');
      $("#modal-boletas #BtnAddBoleta").addClass('entrada');
    });

    $("#filBoleta").on('change', function(event) {
      getBoletas(['en', 'sa', 'p', 'b']);
    });
  }

  function getBoletas(accion){
    var params = {
      tipoo: 'sa',
      accion: (accion? accion: ['en', 'p', 'b']),
      filtro: $("#filBoleta").val()
    };
    $.getJSON(base_url+"panel/compras_ordenes/ajaxGetBoletas/", params, function(json, textStatus) {
      var html = '';
      for (var i in json) {
        html += '<tr class="radioBoleta" data-id="'+json[i].id_bascula+'" data-folio="'+json[i].folio+'" '+
          'data-idempresa="'+json[i].id_empresa+'" data-empresa="'+json[i].empresa+'" style="cursor: pointer;">'+
        '  <td>'+json[i].fecha+'</td>'+
        '  <td>'+json[i].folio+'</td>'+
        '  <td>'+json[i].cliente+'</td>'+
        '  <td>'+json[i].area+'</td>'+
        '</tr>';
      }
      $("#table-boletas tbody").html(html);
    });
  }

  function setBoletasSel() {
    $("#table-boletas").on('dblclick', 'tr.radioBoleta', function(event) {
      var $this = $(this);

      $('#boletasSalidasFolio').val($this.attr('data-folio'));
      $('#boletasSalidasId').val($this.attr('data-id'));
      $('#empresa').val($this.attr('data-empresa'));
      $('#empresaId').val($this.attr('data-idempresa'));

      $("#modal-boletas").modal('hide');
    });
  }


  // --------------------------------
  // Eventos
  function eventOnChangeMedida() {
    $('#table_prod').on('change', 'select#prod_dmedida', function(event) {
      calculaKilos($(this));
    });

    $('#table_prod').on('keyup', 'input#prod_dcantidad', function(event) {
      calculaKilos($(this));
    });
  };

  function calculaKilos($this) {
    var $parent = $this.parents('tr'),
        $select = $parent.find('#prod_dmedida'),
        $medidaId = $parent.find('#prod_dmedida_id'),
        $cantidad = $parent.find('#prod_dcantidad'),
        $kilosInput = $parent.find('#prod_dmedida_kilos'),
        $kilosInputTxt = $parent.find('#prod_dmedida_kilos_text'),
        $optionSel = $select.find('option:selected');
    var kilos = (parseFloat($optionSel.attr('data-cantidad'))||0)*(parseFloat($cantidad.val())||0);

    $medidaId.val($optionSel.attr('data-id'));
    $kilosInput.val( kilos );
    $kilosInputTxt.text( kilos );
  }

  function eventOnChangeTipo() {
    $('#tipo').on('change', function(event) {
      var $this = $(this);
      if ($this.val() === 'lo' || $this.val() === 'na') {
        $('#show-table-prod').show();
        $('#show-table-pallets').hide();
      } else {
        $('#show-table-prod').hide();
        $('#show-table-pallets').show();
      }
    });
  }

  // ---------------------------------------
  // Agregar clasificaciones
  function autocompleteClasifiLive () {
    $('#table_prod').on('focus', 'input#prod_ddescripcion:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('#prod_did_prod').val(ui.item.id);

          $tr.find('#prod_dmedida').find('[data-id="'+ui.item.item.id_unidad+'"]').attr('selected', 'selected');
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#prod_did_prod').val('');
        }
      });
    });
  }

  function autocompleteClientes () {
    $('#table_prod').on('focus', 'input#prod_cliente:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: function(request, response) {
          if ($("#empresaId").val() != '') {
            $.ajax({
                url: base_url+'panel/facturacion/ajax_get_clientes/',
                dataType: "json",
                data: {
                    term : request.term,
                    did_empresa : $("#empresaId").val()
                },
                success: function(data) {
                    response(data);
                }
            });
          } else {
            noty({"text": 'No se a seleccionado la boleta de bascula.', "layout":"topRight", "type": 'error'});
            response([]);
          }
        },
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#B0FFB0");
          $tr.find("#prod_id_cliente").val(ui.item.id);
        }
      }).on("keydown", function(event){
          if(event.which == 8 || event == 46){
            var $tr = $(this).parent().parent();
            $(this).css("background-color", "#FFD9B3");
            $tr.find("#prod_id_cliente").val("");
          }
      });
    });
  }

  // -------------------------------------
  // Agregar registros a la tabla de clasificaciones
  function nuevoRegistro() {
    $('#table_prod').on('keypress', 'input#prod_dcantidad', function(event) {

      if (event.which === 13) {
        event.preventDefault();
        var $tr = $(this).parent().parent();

        if (valida_agregar($tr)) {
          $tr.find('td').not('.cporte').effect("highlight", {'color': '#99FF99'}, 500);
          $.get(base_url + 'panel/facturacion/ajax_get_unidades', function(unidades) {
            addProducto(unidades);
          }, 'json');
        } else {
          console.log('test');
          $tr.find('#prod_cliente').focus();
          $tr.find('td').not('.cporte').effect("highlight", {'color': '#da4f49'}, 500);
          noty({"text": 'Verifique los datos del producto.', "layout":"topRight", "type": 'error'});
        }
      }
    });
  }

  function valida_agregar ($tr) {
    if ($tr.find("#prod_id_cliente").val() === '' || $tr.find("#prod_did_prod").val() == '' ||
      $tr.find("#prod_dcantidad").val() == '') {
      return false;
    }
    else return true;
  }

  var jumpIndex = 0;
  function addProducto(unidades, prod = {}) {
    var $tabla = $('#table_prod'),
        trHtml    = '',
        indexJump = jumpIndex + 1,
        existe    = false,
        $tr, addInputPalletId = true;

    Object.assign(prod, {
      cliente: '',
      id_cliente: '',
      clasificacion: '',
      id_clasificacion: '',
      unidad: '',
      id_unidad: '',
      cantidad: '0',
      kilos: '0',
    });

    var unidadesHtml = '';
    for (var i in unidades) {
      selectedUnidad = (unidades[i].id_unidad == prod.id_unidad ? 'selected' : '');
      unidadesHtml += '<option value="'+unidades[i].nombre+'" '+selectedUnidad+' data-id="'+unidades[i].id_unidad+'" data-id="'+unidades[i].cantidad+'">'+unidades[i].nombre+'</option>';
    }

    trHtml =
      '<tr data-pallets="" data-remisiones="">'+
        '<td>'+
          '<input type="text" name="prod_cliente[]" value="'+prod.cliente+'" id="prod_cliente" class="span12 jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">'+
          '<input type="hidden" name="prod_id_cliente[]" value="'+prod.id_cliente+'" id="prod_id_cliente" class="span12">'+
        '</td>'+
        '<td>'+
          '<input type="text" name="prod_ddescripcion[]" value="'+prod.clasificacion+'" id="prod_ddescripcion" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
          '<input type="hidden" name="prod_did_prod[]" value="'+prod.id_clasificacion+'" id="prod_did_prod" class="span12">'+
        '</td>'+
        '<td>'+
          '<select name="prod_dmedida[]" id="prod_dmedida" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
            unidadesHtml+
          '</select>'+
          '<input type="hidden" name="prod_dmedida_id[]" value="'+prod.id_unidad+'" id="prod_dmedida_id" class="span12 vpositive">'+
        '</td>'+
        '<td>'+
          '<input type="text" name="prod_dcantidad[]" value="'+prod.cantidad+'" id="prod_dcantidad" class="span12 vpositive jump'+jumpIndex+'">'+
        '</td>'+
        '<td>'+
          '<span id="prod_dmedida_kilos_text">'+prod.kilos+'</span>'+
          '<input type="hidden" name="prod_dmedida_kilos[]" value="'+prod.kilos+'" id="prod_dmedida_kilos" class="span12 vpositive" readonly="readonly">'+
        '</td>'+
        '<td><button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button></td>'+
      '</tr>'+

      console.log('test', trHtml);
    $(trHtml).appendTo($tabla.find('tbody'));

    // console.log('test', $tabla.find('tbody tr:last'));

    for (i = indexJump, max = jumpIndex; i <= max; i += 1)
      $.fn.keyJump.setElem($('.jump'+i));

    $('.jump'+(indexJump)).focus();
    $(".vpositive").numeric({ negative: false });
    $(".vnumeric").numeric();
  }


  objr.init = init;
  return objr;
})(jQuery);

