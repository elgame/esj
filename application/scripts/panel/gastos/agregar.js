(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {
    //Autocomplete cuentas contpaq
    $("#dcuenta_cpi").autocomplete({
        source: function(request, response) {
          var params = {term : request.term};
          if(parseInt($("#empresaId").val()) > 0)
            params.did_empresa = $("#empresaId").val();
          $.ajax({
              url: base_url+'panel/banco/get_cuentas_contpaq/',
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
          $("#did_cuentacpi").val(ui.item.id);
          $("#dcuenta_cpi").css("background-color", "#B0FFB0");
        }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $("#dcuenta_cpi").css("background-color", "#FFD9B3");
          $("#did_cuentacpi").val("");
        }
    });

    $('#form').keyJump();

    $('#subtotal').on('keyup', function(event) {
      var key = event.which;
      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        total();
      }
    });

    $('#iva').on('keyup', function(event) {
      var key = event.which;
      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        total();
      }
    });

    $('#ret_iva').on('keyup', function(event) {
      var key = event.which;
      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        total();
      }
    });

    $('#ret_isr').on('keyup', function(event) {
      var key = event.which;
      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        total();
      }
    });

    $("#dlitros, #dprecio").on('keyup', function(event) {
      var key = event.which;
      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        var litros = parseFloat($("#dlitros").val()) || 0,
        precio = parseFloat($("#dprecio").val()) || 0;
        $('#subtotal').val(litros*precio);
        $("#iva").val((litros*precio)*0.16);
        total();
      }
    });

    $("#empresa").autocomplete({
        source: base_url+'panel/empresas/ajax_get_empresas/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $("#empresaId").val(ui.item.id);
          $(this).css("background-color", "#B0FFB0");

          $('#groupCatalogos').show();
          $('#area').val('');
          $('#areaId').val('');
          $('#rancho').val('');
          $('#ranchoId').val('');
          $('#activos').val('');
          $('#activoId').val('');
        }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).css("background-color", "#FFD9B3");
          $("#empresaId").val("");

          $('#area').val('');
          $('#areaId').val('');
          $('#rancho').val('');
          $('#ranchoId').val('');
          $('#activos').val('');
          $('#activoId').val('');
          $('#groupCatalogos').hide();
        }
    });

    // Autocomplete para los Proveedores.
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
        $("#condicionPago").val(ui.item.item.condicion_pago);
        $("#plazoCredito").val(ui.item.item.dias_credito);

        $.get(base_url + 'panel/gastos/ajax_get_cuentas_proveedor/?idp=' + ui.item.id, function(data) {
          var htmlOptions = '';
          for (var i in data) {
            htmlOptions += '<option value="' + data[i].id_cuenta + '">' + data[i].full_alias + '</option>';
          }

          $('#fcuentas_proveedor').html(htmlOptions);
        }, 'json');

      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#proveedor").css("background-color", "#FFD071");
        $("#proveedorId").val('');
        $('#fcuentas_proveedor').html('');
      }
    });

    // Autocomplete para los Vehiculos.
    $("#vehiculo").autocomplete({
      source: base_url + 'panel/vehiculos/ajax_get_vehiculos/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $vehiculo =  $(this);

        $vehiculo.val(ui.item.id);
        $("#vehiculoId").val(ui.item.id);
        $vehiculo.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#vehiculo").css("background-color", "#FFD071");
        $("#vehiculoId").val('');
      }
    });

    $("#es_vehiculo").on('change', function(event) {
      var $this = $(this);
      if($this.is(":checked")){
        $("#groupVehiculo").show();
        if($("#tipo_vehiculo").val() == 'g')
          $("#group_gasolina").show();
      }else{
        $("#groupVehiculo").hide();
        $("#group_gasolina").hide();
      }
    });

    $("#tipo_vehiculo").on('change', function(event) {
      var $this = $(this);
      if($this.val() == 'g')
        $("#group_gasolina").show();
      else
        $("#group_gasolina").hide();
    });

    $('#condicionPago').on('change', function(event) {
      var $this = $(this);
      if ($this.val() == 'cr')
      {
        $("#grup_plazo_credito").show();
        $("#group_pago_contado").hide();
      }
      else
      {
        $("#grup_plazo_credito").hide();
        $("#group_pago_contado").show();
      }
    });


    /**
     * Ligar ordenes
     */
    $("#btnCargarOrdenesGasto").on('click', cargarOrdenesGasto);
    $("#ordenesSeleccionadas").on('click', '.ordenremove', quitarOrdenGasto);

    autocompleteCultivo();
    autocompleteRanchos();
    autocompleteCentroCosto();
    autocompleteActivos();

  });

  var total = function () {
    var $total = $('#total'),
        $subtotal = $('#subtotal'),
        $ret_iva = $('#ret_iva'),
        $ret_isr = $('#ret_isr'),
        $iva = $('#iva');

    $total.val( util.trunc2Dec(parseFloat($subtotal.val()||0) +
                               parseFloat($iva.val()||0) -
                               parseFloat($ret_iva.val()||0) -
                               parseFloat($ret_isr.val()||0))
    );
  };



  var autocompleteCultivo = function () {
    $("#area").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
        $.ajax({
            url: base_url + 'panel/areas/ajax_get_areas/',
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
        var $area =  $(this);

        $area.val(ui.item.id);
        $("#areaId").val(ui.item.id);
        $area.css("background-color", "#A1F57A");

        $("#rancho").val('').css("background-color", "#FFD071");
        $('#tagsRanchoIds').html('');
        // $("#ranchoId").val('');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#area").css("background-color", "#FFD071");
        $("#areaId").val('');
        $("#rancho").val('').css("background-color", "#FFD071");
        $('#tagsRanchoIds').html('');
        // $("#ranchoId").val('');
      }
    });
  };

  var autocompleteRanchos = function () {
    $("#rancho").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
        if(parseInt($("#areaId").val()) > 0)
          params.area = $("#areaId").val();
        $.ajax({
            url: base_url + 'panel/ranchos/ajax_get_ranchos/',
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
        var $rancho =  $(this);

        addRanchoTag(ui.item);
        setTimeout(function () {
          $rancho.val('');
        }, 200);
        // $rancho.val(ui.item.id);
        // $("#ranchoId").val(ui.item.id);
        // $rancho.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#rancho").css("background-color", "#FFD071");
        // $("#ranchoId").val('');
      }
    });

    function addRanchoTag(item) {
      if ($('#tagsRanchoIds .ranchoId[value="'+item.id+'"]').length === 0) {
        $('#tagsRanchoIds').append('<li><span class="tag">'+item.value+'</span>'+
          '<input type="hidden" name="ranchoId[]" class="ranchoId" value="'+item.id+'">'+
          '<input type="hidden" name="ranchoText[]" class="ranchoText" value="'+item.value+'">'+
          '</li>');
      } else {
        noty({"text": 'Ya esta agregada el Areas, Ranchos o Lineas.', "layout":"topRight", "type": 'error'});
      }
    };

    $('#tagsRanchoIds').on('click', 'li:not(.disable)', function(event) {
      $(this).remove();
    });
  };

  var autocompleteCentroCosto = function () {
    $("#centroCosto").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};

        params.tipo = ['gasto', 'gastofinanciero', 'servicio'];

        $.ajax({
            url: base_url + 'panel/centro_costo/ajax_get_centro_costo/',
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
        var $centroCosto =  $(this);

        addCCTag(ui.item);
        setTimeout(function () {
          $centroCosto.val('');
        }, 200);
        // $centroCosto.val(ui.item.id);
        // $("#centroCostoId").val(ui.item.id);
        // $centroCosto.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#centroCosto").css("background-color", "#FFD071");
        // $("#centroCostoId").val('');
      }
    });

    function addCCTag(item) {
      if ($('#tagsCCIds .centroCostoId[value="'+item.id+'"]').length === 0) {
        $('#tagsCCIds').append('<li><span class="tag">'+item.value+'</span>'+
          '<input type="hidden" name="centroCostoId[]" class="centroCostoId" value="'+item.id+'">'+
          '<input type="hidden" name="centroCostoText[]" class="centroCostoText" value="'+item.value+'">'+
          '</li>');
      } else {
        noty({"text": 'Ya esta agregada el Centro de costo.', "layout":"topRight", "type": 'error'});
      }
    };

    $('#tagsCCIds').on('click', 'li:not(.disable)', function(event) {
      $(this).remove();
    });
  };

  var autocompleteActivos = function () {
    $("#activos").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        // if(parseInt($("#empresaId").val()) > 0)
        //   params.did_empresa = $("#empresaId").val();
        params.tipo = 'a'; // activos
        $.ajax({
            url: base_url + 'panel/productos/ajax_aut_productos/',
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
        var $activos =  $(this);

        $activos.val(ui.item.id);
        $("#activoId").val(ui.item.id);
        $activos.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#activos").css("background-color", "#FFD071");
        $("#activoId").val('');
      }
    });
  };



  /**
   * Licar ordenes
   */
  var cargarOrdenesGasto = function (){
    event.preventDefault();
    var selecteds = false, data=[];
    $(".addToFactura:checked").each(function(index, val) {
      data.push({
        'id': $(this).val(),
        'folio': $(this).attr('data-folio')
      });
       selecteds = true;
    });

    if (selecteds) {
      parent.setOrdenesGastos(data);
    }else
      noty({"text":"Selecciona al menos una orden", "layout":"topRight", "type":"error"});
  };

  var quitarOrdenGasto = function(event) {
      event.preventDefault();
      $(this).parent("span.label").remove();
    }

});


function setOrdenesGastos(data){
  var html = '';
  for (var i in data) {
    if($('#ordenes'+data[i].id).length == 0)
      html += '<span class="label" style="margin-left:4px">'+data[i].folio+' <i class="icon-remove ordenremove" style="cursor: pointer"></i>'+
              '  <input type="hidden" name="ordenes[]" value="'+data[i].id+'" id="ordenes'+data[i].id+'">'+
              '  <input type="hidden" name="ordenes_folio[]" value="'+data[i].folio+'">'+
              '</span>';
  };
  $("#ordenesSeleccionadas").append(html);
  $("#supermodal").modal('hide');
}

function validaParamsGasto ($button, $modal) {
  var idp   = $('#proveedorId').val(),
      ide   = $('#empresaId').val(),
      exist = false,
      ids   = [];

  if(idp != '' && ide != '') {
    $button.attr('href', base_url + 'panel/gastos/ligar/?fstatus=a&did_proveedor='+idp+'&did_empresa='+ide );
    $modal.modal('show');
  } else {
    noty({"text":"Seleccione una Empresa y un Proveedor", "layout":"topRight", "type":"error"});
  }
}