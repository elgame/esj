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

  // Autocomplete Cliente
  $("#dcliente").autocomplete({
    source: base_url + 'panel/clientes/ajax_get_proveedores/',
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
    url = url[0]+"?id="+ids+"&tipo="+tipos+"&total="+total;
    btn_abonos_masivo.attr("href", url);
    if($(".sel_abonom.active").length > 0)
      btn_abonos_masivo.show();
    else
      btn_abonos_masivo.hide();
  }

  objs.init = init;
  return objs;
})(jQuery);

//Modal Abonos
var modalAbonos = (function($){
  var objs = {},
  btn_abonos_masivo,
  $enviar = false;

  function init()
  {
    if ($("#abonomasivo").length > 0) 
    { 
      $("#abonomasivo .monto_factura").on('change', calculaMonto);
      $("#form").on('submit', sendFormMasivo);
    }
  }

  function calculaMonto()
  {
    var monto = 0;
    $("#abonomasivo .monto_factura").each(function(index, val) {
      monto += parseFloat($(this).val());
    });
    $("#dmonto").val(monto);
  }

  function sendFormMasivo(){
    var pass=false;
    if($enviar == false){
      $(".monto_factura").each(function(index, val) {
        var $this = $(this);
        if(parseFloat($this.val()) > parseFloat($this.attr('data-max')) )
          pass = true;
      });
      if(pass){ //es mayor el cargo a pagar
        msb.confirm('El monto de una o más facturas es mayor al saldo, se saldaran y el resto se cargara a pagos adicionales.', 'dd', this, function(){
          $enviar = true;
          $("#form").submit();
        });
        return false;
      }else{ //es igual o menor el cargo
        return true;
      }
    }else{
      $enviar = false;
      return true;
    }
  }

  objs.init = init;
  return objs;
})(jQuery);
