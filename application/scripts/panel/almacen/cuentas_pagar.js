$(function(){
	// Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

  // Autocomplete proveedores
  $("#dproveedor").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
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
      $("#fid_proveedor").val(ui.item.id);
      $("#id_proveedor").val(ui.item.id);
      $("#dproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#fid_proveedor').val('');
      $('#id_proveedor').val('');
    }
  });

  abonom.init();

  modalAbonos.init();

  $('#select-all-abonom').on('click', function(event) {
    var $this;
    if ($(this).is(':checked')) {
      $('.sel_abonom').each(function(index, el) {
        $this = $(this);
        if ( ! $this.hasClass('active')) {
          $(this).click();
        }
      });
    } else {
      $('.sel_abonom').each(function(index, el) {
        $this = $(this);
        if ($this.hasClass('active')) {
          $(this).click();
        }
      });
    }
  });
});


//Abonos masivos
var abonom = (function($){
  var objs = {},
  btn_abonos_masivo;

  function init(){
    $(".sel_abonom").on('click', selabono);
    btn_abonos_masivo = $(".btn_abonos_masivo");
    $("#fmetodo_pago").on('change', changeMetodoPago);

    $(".change_spago").on('click', clickPagoBanco);
  }

  function selabono(){
    var vthis = $(this), ids="", tipos="", total=0,
    url="", $tr = vthis.parent("tr");
    if(vthis.is(".active")){
      vthis.css("background-color", "transparent").removeClass("active");
      $tr.find('.change_spago').click();
    }else{
      vthis.css("background-color", "red").addClass("active");
      $tr.find('.change_spago').click();
    }

    $(".sel_abonom.active").each(function(){
      var vttis = $(this);
      ids += ","+vttis.attr("data-id");
      tipos += ","+vttis.attr("data-tipo");
      total += parseFloat( util.quitarFormatoNum(vttis.text()) );
    });
    url = btn_abonos_masivo.attr("href").split("?");
    url = url[0]+"?id="+ids+"&tipo="+tipos+"&total="+total.toFixed(2)+"&tcambio="+$("#tipo_cambio").val();
    btn_abonos_masivo.attr("href", url);
    if($(".sel_abonom.active").length > 0){
      btn_abonos_masivo.show();
      $("#sumaRowsSel").text(util.darFormatoNum( total.toFixed(2) )).show();
    }else{
      btn_abonos_masivo.hide();
      $("#sumaRowsSel").hide();
    }
  }

  function changeMetodoPago(event) {
    var $this = $(this);
    if($this.val() != 'transferencia')
      $("#group_metodo_pago").hide();
    else
      $("#group_metodo_pago").show();
  }

  function openCheque($id_movimiento) {
    window.open(base_url+'panel/banco/cheque?id='+$id_movimiento, 'Print cheque');
  }

  /**
   * asigna o quita la compra en los pagos de banco
   */
  function clickPagoBanco(event) {
    var $this = $(this);

    if($this.attr('data-status') == 'p')
    {
      var params = {
        id_compra: $this.attr("data-idcompra"), id_proveedor: $this.attr("data-idproveedor"),
        monto: $this.attr("data-monto"), folio: $this.attr("data-folio"), tcambio: $("#tipo_cambio").val()
      };

      if ((parseInt(params.id_proveedor)||0) > 0) {
        if($this.is(':checked')){
          $.post(base_url + 'panel/banco_pagos/set_compra/', params,
            function(data, textStatus, xhr) {
              // noty({"text": 'Se agrego correctamente a la lista', "layout":"topRight", "type": 'success'});
          }).fail(function(){ noty({"text": 'No se agrego a la lista', "layout":"topRight", "type": 'error'}); });
        }else{
          $.post(base_url + 'panel/banco_pagos/set_compra/', params,
            function(data, textStatus, xhr) {
              // noty({"text": 'Se quito correctamente de la lista', "layout":"topRight", "type": 'success'});
          }).fail(function(){ noty({"text": 'No se quito de la lista', "layout":"topRight", "type": 'error'}); });
        }
      } else {
        noty({"text": 'Se tiene que seleccionar un proveedor para marcar las compras.', "layout":"topRight", "type": 'error'});
        if($this.is(':checked')) {
          $this.prop('checked', false);
        } else {
          $this.prop('checked', true);
        }
      }
    }else
    {
      noty({"text": 'La factura '+$this.attr('data-folio')+' ya esta pagada en otro periodo', "layout":"topRight", "type": 'error'});
      return false;
    }
  }

  objs.init = init;
  objs.openCheque = openCheque;
  return objs;
})(jQuery);

//Modal Abonos
var modalAbonos = (function($){
  var objs = {},
  btn_abonos_masivo;

  function init()
  {
    if ($("#abonomasivo").length > 0)
    {
      $("#abonomasivo .monto_factura").on('change', calculaMonto);
    }
  }

  function calculaMonto()
  {
    var monto = 0;
    $("#abonomasivo .monto_factura").each(function(index, val) {
      var parent = $(this).parent().parent(),
      cant = parseFloat($(this).val()),
      cant2 = cant;
      monto += cant;

      //cant >= parseFloat($(this).attr('data-val')) ||
      if ( cant == 0  ) {
        cant2 = parent.find('.new_total').attr('data-val');
      }
      parent.find('.new_total').val( cant2 );
    });
    $("#dmonto").val(monto);
    $("#suma_monto").text(util.darFormatoNum(monto));
  }

  objs.init = init;
  return objs;
})(jQuery);
