$(function(){
	// Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});

      loadSerieFolio(ui.item.id, true, ui.item);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

  // Autocomplete Cliente
  $("#dcliente").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
      $.ajax({
          url: base_url + 'panel/clientes/ajax_get_proveedores/',
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
      $("#id_cliente").val(ui.item.id);
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#fid_cliente').val('');
      $("#id_cliente").val('');
    }
  });

  // Autocomplete Proveedores
  $("#dproveedor").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val(), 10) > 0)
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
      $("#did_proveedor").val(ui.item.id);
      $("#dproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#did_proveedor').val('');
    }
  });

  abonom.init();

  modalAbonos.init();

  comPagos.init();

  if($('#did_empresa').length > 0) {
    loadSerieFolio($('#did_empresa').val());
  }
});

function loadSerieFolio (ide, forceLoad) {
  if (ide > 0){
    var objselect = $('#fserie');

    var url = 'panel/facturacion/get_series/?tipof=&ide=';

    loader.create();
      $.getJSON(base_url+url+ide,
        function(res){
          if(res.data) {
            var html_option = '<option value=""></option>',
                selected = '', serieSelected = '',
                loadDefault = false;

            let ser = '';
            console.log($('#fserie1').val());
            for (var i in res.data){
              selected = res.data[i].serie == $('#fserie1').val()? ' selected': '';
              html_option += '<option value="'+res.data[i].serie+'" '+selected+'>'+res.data[i].serie+' - '+(res.data[i].leyenda || '')+'</option>';
            }
            objselect.html(html_option);
          } else {
            noty({"text":res.msg, "layout":"topRight", "type":res.ico});
          }
          loader.close();
        });
  }
}

//complemento de pagos
var comPagos = (function($){
  var objs = {};

  function init(){
    if ($("#btnRegComPago").length > 0)
    {
      $("#formCompago").on('submit', function () {
        setTimeout(function () {
          $("#btnRegComPago").attr('disabled', true);
        }, 100);
        $("#btnRegComPago .loader").show();
      });

      // Autocomplete
      $("#addComplemento").autocomplete({
        source: function(request, response) {
          var params = {term : request.term};
          if(parseInt($("#empresaId").val(), 10) > 0)
            params.did_empresa = $("#empresaId").val();
          if(parseInt($("#clienteId").val(), 10) > 0)
            params.did_cliente = $("#clienteId").val();
          $.ajax({
              url: base_url + 'panel/cuentas_cobrar/ajax_get_com_pagos/',
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
          var hhtml = '<li>'+ui.item.value+
                    '<input type="hidden" name="cfdiRel[uuids][]" value="'+ui.item.id+'">'+
                    ' <button type="button" title="Quitar" class="removeComPago">X</button>'+
                  '</li>';
          $("#listaComPago").append(hhtml);
          setTimeout(function () {
            $('#addComplemento').val('');
          }, 250);
        }
      });

      $('#listaComPago').on('click', '.removeComPago', function(event) {
        $(this).parent().remove();
      });

      $('#moneda').on('change', function(event) {
          $('#tipoCambio').val('').show();
        // if ($(this).val() == 'MXN') {
        //   $('#tipoCambio').val('').hide();
        // } else {
        //   $('#tipoCambio').val('').show();
        // }
      });
    }
  }

  objs.init = init;
  return objs;
})(jQuery);


//Abonos masivos
var abonom = (function($){
  var objs = {},
  btn_abonos_masivo;

  function init(){
    $(".sel_abonom").on('click', selabono);
    btn_abonos_masivo = $(".btn_abonos_masivo");

    if ($("#btnGuardarAbono").length > 0)
    {
      $("#form").on('submit', function () {
        $("#btnGuardarAbono").prop('disabled', true);
        // setTimeout(function () {
        //   $("#btnGuardarAbono").prop('disabled', true);
        // }, 100);
      });
    }
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
    $("#suma_monto").text(util.darFormatoNum(monto));
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
        msb.confirm('El monto de una o más facturas es mayor al saldo, se saldaran y el resto se cargara a pagos adicionales.', 'Alerta', this, function(){
          $enviar = true;
          $("#form").submit();
        }, function (){}, 'top: 60% !important;');
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
