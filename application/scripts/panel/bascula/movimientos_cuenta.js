$(function(){
  /**
   * asigna o quita la compra en los pagos de banco
   */
  $(".change_spago").on('click', function(event) {
    var $this = $(this);
    if($this.is(':checked')){
      msb.confirm("Se agregara la compra al listado de pagos, esta seguro?", "", $this, function(){
        $.post(base_url + 'panel/banco_pagos/set_bascula/',
          {id_bascula: $this.attr("data-idcompra"), id_proveedor: $this.attr("data-idproveedor"), monto: $this.attr("data-monto")},
          function(data, textStatus, xhr) {
            noty({"text": 'Se agrego correctamente a la lista', "layout":"topRight", "type": 'success'});
        }).fail(function(){ noty({"text": 'No se agrego a la lista', "layout":"topRight", "type": 'error'}); });
      }, function(){ $this.removeAttr('checked') });
    }else{
      msb.confirm("Se quitara la compra al listado de pagos, esta seguro?", "", $this, function(){
        $.post(base_url + 'panel/banco_pagos/set_bascula/',
          {id_bascula: $this.attr("data-idcompra"), id_proveedor: $this.attr("data-idproveedor"), monto: $this.attr("data-monto")},
          function(data, textStatus, xhr) {
            noty({"text": 'Se quito correctamente de la lista', "layout":"topRight", "type": 'success'});
        }).fail(function(){ noty({"text": 'No se quito de la lista', "layout":"topRight", "type": 'error'}); });
      }, function(){ $this.attr('checked', 'true') });
    }
  });


  $('#fechaini').datepicker({
    dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
    //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
    changeMonth: true, //permite modificar los meses (true o false)
    changeYear: true, //permite modificar los años (true o false)
    //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
    numberOfMonths: 1, //muestra mas de un mes en el calendario, depende del numero
  });

  $('#fechaend').datepicker({
    dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
    //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
    changeMonth: true, //permite modificar los meses (true o false)
    changeYear: true, //permite modificar los años (true o false)
    //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
    numberOfMonths: 1, //muestra mas de un mes en el calendario, depende del numero
  });

  // Autocomplete Proveedor
  setAutocomplet($("#ftipop").val(), 1);
  changeTipo($("#ftipop").val());

  $("#ftipop").on("change", function(){
    changeTipo($(this).val());

    setAutocomplet($(this).val());
  });

  $(function(){
    $('#checkPesadas').on('change', function(event) {
      var check = '';

      if ($(this).is(':checked')) check = "checked";
      else check = "";

      $('input#pesadas').each(function(index) {
        // $(this).prop('checked', check);
        $(this).trigger('click');
      });

    });
  });

  $('input#pesadas').on('change', function(event) {
    event.preventDefault();
    var $this = $(this),
        $monto = $('#pmonto');

    if ($this.is(':checked')) {
      $monto.val(parseFloat($monto.val()) + parseFloat($this.attr('data-monto')))
    } else {
      $monto.val(parseFloat($monto.val()) - parseFloat($this.attr('data-monto')))
    }

  });

  $('#btnModalPagos').on('click', function(event) {
    event.preventDefault();

    var $monto = $('#pmonto');

    if (parseFloat($monto.val()) !== 0) {
      $('#modalPagos').modal('toggle')
    } else {
      noty({"text": 'Seleccione al menos una pesada para pagar!', "layout":"topRight", "type": 'error'});
    }
  });

});

function changeTipo(tipo){
  $(".autocomplet_en").hide();
    $(".autocomplet_sa").hide();

    $(".autocomplet_"+tipo).show();
}
function setAutocomplet(tipo, first){
  if(first != 1){
    $("#fproveedor").autocomplete("destroy").val("");
    $("#fid_proveedor").val("");
  }
  if (tipo == "en") {
    $("#fproveedor").autocomplete({
      source: base_url + 'panel/bascula/ajax_get_proveedores/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#fid_proveedor").val(ui.item.id);
        $("#fproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
       $(this).css({'background-color': '#FFD9B3'});
        $('#fid_proveedor').val('');
      }
    });
  }else if(tipo == "sa"){
    $("#fproveedor").autocomplete({
      source: base_url + 'panel/bascula/ajax_get_clientes/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#fid_proveedor").val(ui.item.id);
        $("#fproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
       $(this).css({'background-color': '#FFD9B3'});
        $('#fid_proveedor').val('');
      }
    });
  }
}