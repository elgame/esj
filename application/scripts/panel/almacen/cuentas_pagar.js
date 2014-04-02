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
  $("#dcliente").autocomplete({
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
      $("#fid_cliente").val(ui.item.id);
      $("#dcliente").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#fid_cliente').val('');
    }
  });

  abonom.init();

  modalAbonos.init();
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
    var vthis = $(this), ids="", tipos="", total=0
    url="";
    if(vthis.is(".active")){
      vthis.css("background-color", "transparent").removeClass("active");
    }else{
      vthis.css("background-color", "red").addClass("active");
    }

    $(".sel_abonom.active").each(function(){
      var vttis = $(this);
      ids += ","+vttis.attr("data-id");
      tipos += ","+vttis.attr("data-tipo");
      total += parseFloat( util.quitarFormatoNum(vttis.text()) );
    });
    url = btn_abonos_masivo.attr("href").split("?");
    url = url[0]+"?id="+ids+"&tipo="+tipos+"&total="+total.toFixed(2);
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
    if($this.is(':checked')){
      msb.confirm("Se agregara la compra al listado de pagos, esta seguro?", "", $this, function(){
        $.post(base_url + 'panel/banco_pagos/set_compra/',
          {id_compra: $this.attr("data-idcompra"), id_proveedor: $this.attr("data-idproveedor"), monto: $this.attr("data-monto")},
          function(data, textStatus, xhr) {
            noty({"text": 'Se agrego correctamente a la lista', "layout":"topRight", "type": 'success'});
        }).fail(function(){ noty({"text": 'No se agrego a la lista', "layout":"topRight", "type": 'error'}); });
      }, function(){ $this.removeAttr('checked') });
    }else{
      msb.confirm("Se quitara la compra al listado de pagos, esta seguro?", "", $this, function(){
        $.post(base_url + 'panel/banco_pagos/set_compra/',
          {id_compra: $this.attr("data-idcompra"), id_proveedor: $this.attr("data-idproveedor"), monto: $this.attr("data-monto")},
          function(data, textStatus, xhr) {
            noty({"text": 'Se quito correctamente de la lista', "layout":"topRight", "type": 'success'});
        }).fail(function(){ noty({"text": 'No se quito de la lista', "layout":"topRight", "type": 'error'}); });
      }, function(){ $this.attr('checked', 'true') });
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
      monto += parseFloat($(this).val());
    });
    $("#dmonto").val(monto);
    $("#suma_monto").text(util.darFormatoNum(monto));
  }

  objs.init = init;
  return objs;
})(jQuery);
