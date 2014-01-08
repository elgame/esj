(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {

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
        }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).css("background-color", "#FFD9B3");
          $("#empresaId").val("");
        }
    });

    // Autocomplete para los Proveedores.
    $("#proveedor").autocomplete({
      source: base_url + 'panel/proveedores/ajax_get_proveedores/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $proveedor =  $(this);

        $proveedor.val(ui.item.id);
        $("#proveedorId").val(ui.item.id);
        $proveedor.css("background-color", "#A1F57A");

        $.get(base_url + 'panel/gastos/ajax_get_cuentas_proveedor/?idp=' + ui.item.id, function(data) {
          var htmlOptions = '';
          for (var i in data) {
            htmlOptions += '<option value="' + data[i].id_cuenta + '">' + data[i].full_alias + '</option>'
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

  });

  var total = function () {
    var $total = $('#total'),
        $subtotal = $('#subtotal'),
        $iva = $('#iva');

    $total.val( util.trunc2Dec(parseFloat($subtotal.val()||0) + parseFloat($iva.val()||0)) );
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