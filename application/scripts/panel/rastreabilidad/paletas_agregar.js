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
    eventOnChangeTipo();
    eventOnSubmit();

    autocompleteClasifiLive();
    autocompleteClientes();

    nuevoRegistro();
    quitProducto();

    setEventsDragDrop();
    setEventBuscar();

    $('#tipo').change();
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
  function eventOnSubmit() {
    $('#form-papeleta').submit(function(event) {
      var $tr;
      $('#table_prod #prod_id_cliente').each(function(index, el) {
        $tr = $(this).parent().parent();
        if ($(this).val() == '' && $tr.find('#prod_did_prod').val() == '') {
          $tr.remove();
        }
      });
    });
  }
  function eventOnChangeMedida() {
    $('#table_prod').on('change', 'select#prod_dmedida', function(event) {
      console.log('1111');
      calculaKilos($(this));
    });

    $('#table_prod').on('keyup', 'input#prod_dcantidad', function(event) {
      console.log('5555');
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

    console.log($optionSel.attr('data-cantidad'), $cantidad.val(), kilos);
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

          var unidadd = $tr.find('#prod_dmedida').find('[data-id="'+ui.item.item.id_unidad+'"]');
          unidadd.attr('selected', 'selected');
          if ((parseFloat(unidadd.attr('data-cantidad'))||0) === 0) {
            unidadd.attr('data-cantidad', ui.item.item.unidad_cantidad);
          }
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

        if (validaAgregar($tr)) {
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

  function validaAgregar ($tr) {
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

    prod = Object.assign({
      cliente: '',
      id_cliente: '',
      clasificacion: '',
      id_clasificacion: '',
      unidad: '',
      id_unidad: '',
      cantidad: '0',
      kilos: '0',
      id_pallet: '',
    }, prod);

    var unidadesHtml = '';
    for (var i in unidades) {
      selectedUnidad = (unidades[i].id_unidad == prod.id_unidad ? 'selected' : '');
      unidadesHtml += '<option value="'+unidades[i].nombre+'" '+selectedUnidad+' data-id="'+unidades[i].id_unidad+'" data-cantidad="'+unidades[i].cantidad+'">'+unidades[i].nombre+'</option>';
    }

    trHtml =
      '<tr data-pallet="'+prod.id_pallet+'">'+
        '<td>'+
          '<input type="text" name="prod_cliente[]" value="'+prod.cliente+'" id="prod_cliente" class="span12 jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">'+
          '<input type="hidden" name="prod_id_cliente[]" value="'+prod.id_cliente+'" id="prod_id_cliente" class="span12">'+
          '<input type="hidden" name="prod_id_pallet[]" value="'+prod.id_pallet+'" id="prod_id_pallet" class="span12">'+
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

  function quitProducto() {
    $('#table_prod').on('click', 'button#delProd', function(e) {
      var $this = $(this),
          $tr = $this.parent().parent();

      $tr.remove(); // elimina el tr padre.
    });
  }


  //----------------------------------
  //-------------- PALLETS --------------
  // Funciones de Drag and Drop de pallets
  function setEventsDragDrop($reasign=false) {
    $('#table_pallets .pallet').draggable( {
      cancel: "i.quit",
      revert: "invalid",
      containment: 'document', //"#show-table-pallets",
      helper: "clone",
      cursor: "move"
    });

    if (!$reasign) {
      $('#select_pallets .slots').droppable({
        accept: '#table_pallets .pallet',
        classes: {
          "ui-droppable-active": "custom-state-active"
        },
        hoverClass: 'hovered',
        drop: function (event, ui) {
          handlePalletDrop($(this), ui.draggable);
        }
      });

      // Evento para quitar el pallet seleccionado
      $( "#select_pallets" ).on( "click", '.pallet', function( event ) {
        var $item = $( this ),
          $target = $( event.target );

        if ( $target.is( "i.quit" ) ) {
          handleQuitPallet($target.parents('.slots'), $item );
        }

        return false;
      });
    }

  }

  function handleQuitPallet( $slot, $item ) {
    $item.fadeOut(function() {
      var $list = $( "#table_pallets"),
      idPallet = $slot.find('.pallets_id').val(),
      isDraggable = ($slot.find('.pallet.post-draggable').length===0);

      $slot.find('.holder').show();
      $slot.find('.pallets_id').val('');
      $slot.find('.pallets_folio').val('');
      $slot.find('.pallets_fecha').val('');
      $slot.find('.pallets_cajas').val('');
      $slot.find('.pallets_cliente').val('');
      $slot.find('.pallets_idcliente').val('');
      $slot.find('.pallet.post-draggable').remove();

      $item.removeClass( 'correct' ).css({
        width: '',
        height: '',
      });
      $item.find('.holder').show();
      $item.find('.dataInSlot').hide();
      $item.find('.quit').remove();
      if (isDraggable) {
        $item.draggable( 'enable' );
        $item.appendTo( $list ).show();
      }

      $('#table_prod tbody tr[data-pallet="'+idPallet+'"]').remove();
    });
  }

  function handlePalletDrop( $slot, $item ) {
    var id = $item.data( 'id' ),
    folio = $item.data( 'folio' ),
    cajas = $item.data( 'cajas' ),
    fecha = $item.data( 'fecha' ),
    cliente = $item.data( 'cliente' ),
    idCliente = (parseFloat($item.data('idcliente'))||0)
    ;

    if (idCliente > 0) {
      if ($('#select_pallets .pallets_id[value="'+id+'"]').length == 0) {
        $slot.find('.holder').hide();
        $slot.find('.pallets_id').val(id);
        $slot.find('.pallets_folio').val(folio);
        $slot.find('.pallets_fecha').val(fecha);
        $slot.find('.pallets_cajas').val(cajas);
        $slot.find('.pallets_cliente').val(cliente);
        $slot.find('.pallets_idcliente').val(idCliente);

        $item.addClass( 'correct' ).css({
          width: $slot.css('width'),
          height: $slot.css('height'),
        });
        $item.find('.holder').hide();
        $item.find('.dataInSlot').show();
        $item.draggable( 'disable' );
        $item.append('<i class="icon-remove quit" title="Quitar"></i>');

        $slot.append($item);
        setDataPallet(id);
      } else {
        noty({"text": 'El pallet '+folio+' ya esta agregado al camión.', "layout":"topRight", "type": 'error'});
      }
    } else {
      noty({"text": 'El pallet no tiene asignado un cliente, no se puede agregar al camión.', "layout":"topRight", "type": 'error'});
    }
  }

  function setEventBuscar() {
    $('#fbtnFindPallet').on('click', function(event) {
      event.preventDefault();

      var param = {
        "fnombre"     : $("#fnombre").val(),
        "ffecha"      : $("#ffecha").val(),
        "empresaId"   : $("#empresaId").val(),
        "onlyCliente" : 1,
        "limit"       : 40,
      };
      $.getJSON(base_url+'panel/rastreabilidad_pallets/ajax_get_pallets/', param, function(data) {
        var html = '';

        for (var i = 0; i < data.pallets.length; i++) {
          html += '<div class="span12 pallet" data-id="'+data.pallets[i].id_pallet+'" data-folio="'+data.pallets[i].folio+'" '+
              'data-cajas="'+data.pallets[i].cajas+'" data-fecha="'+data.pallets[i].fecha+'" data-cliente="'+data.pallets[i].nombre_fiscal+'" '+
              'data-idcliente="'+data.pallets[i].id_cliente+'">'+
            '<span class="holder">Folio: '+data.pallets[i].folio+' | Fecha: '+data.pallets[i].fecha+' | '+
            'Cajas: '+data.pallets[i].cajas+' | Cliente: '+data.pallets[i].nombre_fiscal+'</span>'+
            '<span class="dataInSlot">Folio: '+data.pallets[i].folio+' | Cajas: '+data.pallets[i].cajas+'</span>'+
          '</div>';
        }
        $('#table_pallets').html(html);

        setEventsDragDrop(true);
      });
    });
  }

  function setDataPallet(id_pallet) {
    var param = { id_pallet: id_pallet };
    $.getJSON(base_url+'panel/rastreabilidad_pallets/ajax_get_info_pallet/', param, function(data) {
      var html = '', prod = {};
      console.log(data);

      if (data.rendimientos && data.rendimientos.length > 0) {
        $.get(base_url + 'panel/facturacion/ajax_get_unidades', function(unidades) {
          for (var i = 0; i < data.rendimientos.length; i++) {
            prod = {
              cliente          : data.cliente.nombre_fiscal,
              id_cliente       : data.cliente.id_cliente,
              clasificacion    : data.rendimientos[i].nombre,
              id_clasificacion : data.rendimientos[i].id_clasificacion,
              unidad           : data.rendimientos[i].unidad,
              id_unidad        : data.rendimientos[i].id_unidad,
              cantidad         : data.rendimientos[i].cajas,
              kilos            : ((parseFloat(data.rendimientos[i].cajas)||0) * (parseFloat(data.rendimientos[i].unidad_cantidad)||0)).toFixed(2),
              id_pallet        : data.info.id_pallet,
            };
            addProducto(unidades, prod);
          }
        }, 'json');
      }
    });
  }


  objr.init = init;
  return objr;
})(jQuery);


